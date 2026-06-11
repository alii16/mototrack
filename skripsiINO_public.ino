#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <PubSubClient.h>
#include <TinyGPS++.h>
#include <HardwareSerial.h>

// ============================================================
//  KONFIGURASI 
// ============================================================
const char* WIFI_SSID  = "NamaWiFi";
const char* WIFI_PASS  = "PasswordWiFi";
const char* SERVER_URL = "https://nama.domain.kamu";
const char* API_KEY    = "API_key_kamu";    // Samakan dengan file .env di Laravel

// -- EMQX Cloud Serverless --
// Host: ambil dari halaman Overview deployment EMQX, tanpa "mqtts://"
// Contoh: "r1234abc.ala.eu.emqxsl.com"
const char* MQTT_HOST   = "host-mqtt-kamu.emqxsl.com";
const int   MQTT_PORT   = 8883;             // TLS — untuk Serverless

// Credentials: buat di EMQX Cloud, lalu masukkan username & password di bawah
const char* MQTT_USER   = "Username_Kamu";  // EMQX Project -> Access Control -> Authentication -> Username
const char* MQTT_PASS   = "Password_Kamu";  // EMQX Project -> Access Control -> Authentication -> Password

// Client ID unik per device (boleh bebas, tidak boleh sama antar device)
const char* MQTT_CLIENT_ID = "esp32-mototrack-naurah";

// Topic
// Subscribe: perintah relay dari Laravel
const char* TOPIC_RELAY_CMD   = "mototrack/naurah/relay/command";
// Subscribe: sync state saat reconnect (Laravel publish retained message)
// Publish  : konfirmasi state relay aktual dari ESP32
const char* TOPIC_RELAY_STATE = "mototrack/naurah/relay/state";
// ============================================================

#define RELAY_PIN          2
#define GPS_RX_PIN         4
#define GPS_TX_PIN         5
#define GPS_BAUD           9600

#define GPS_PUSH_INTERVAL  5000
#define HTTP_TIMEOUT       4000
#define MQTT_RECONNECT_INTERVAL 5000

// ── GPS ──
TinyGPSPlus    gps;
HardwareSerial gpsSerial(1);

// ── HTTP (khusus GPS) ──
WiFiClientSecure gpsClient;
bool httpBusy = false;
ulong lastGpsPush = 0;

// ── MQTT ──
WiFiClientSecure mqttTlsClient;
PubSubClient     mqttClient(mqttTlsClient);
ulong lastMqttReconnect = 0;

// ── State ──
bool  relayState = false;

// ── Helper header HTTP ──
void addCommonHeaders(HTTPClient &http) {
  http.addHeader("X-ESP-Key",  API_KEY);
  http.addHeader("User-Agent", "ESP32HTTPClient/1.0");
  http.addHeader("Accept",     "application/json");
}

// ============================================================
//  MQTT: Callback — dipanggil saat ada pesan masuk
// ============================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  // Konversi payload ke string
  String msg = "";
  for (unsigned int i = 0; i < length; i++) msg += (char)payload[i];
  msg.trim();

  Serial.printf("[MQTT] Pesan masuk topic '%s': %s\n", topic, msg.c_str());

  // ── Topic: perintah relay (dari Laravel saat user tekan tombol) ──
  if (String(topic) == TOPIC_RELAY_CMD) {
    bool shouldOn = (msg == "1" || msg == "true" || msg == "on");
    setRelay(shouldOn);
  }
}

// ============================================================
//  Set relay + publish konfirmasi
// ============================================================
void setRelay(bool on) {
  relayState = on;
  digitalWrite(RELAY_PIN, on ? HIGH : LOW);
  Serial.printf("[RELAY] -> %s\n", on ? "ON" : "OFF");
}

// ============================================================
//  MQTT: Connect / Reconnect
// ============================================================
void mqttConnect() {
  Serial.printf("[MQTT] Menghubungkan ke %s:%d ...\n", MQTT_HOST, MQTT_PORT);

  // Coba connect dengan credentials
  bool connected = mqttClient.connect(
    MQTT_CLIENT_ID,
    MQTT_USER,
    MQTT_PASS
  );

  if (connected) {
    Serial.println("[MQTT] Terhubung!");

    // Subscribe topic perintah relay saja
    mqttClient.subscribe(TOPIC_RELAY_CMD, 1);
    Serial.printf("[MQTT] Subscribe: %s\n", TOPIC_RELAY_CMD);

  } else {
    Serial.printf("[MQTT] Gagal connect, rc=%d. Coba lagi dalam %d detik...\n",
                  mqttClient.state(), MQTT_RECONNECT_INTERVAL / 1000);
  }
}

// ============================================================
//  Reconnect WiFi
// ============================================================
void checkWifi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.println("[WiFi] Terputus, reconnecting...");
  WiFi.disconnect();
  delay(1000);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t < 15000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("\n[WiFi] Terhubung: %s\n", WiFi.localIP().toString().c_str());
    gpsClient.stop();
    gpsClient.setInsecure();
    httpBusy = false;
    // Reset MQTT state agar reconnect berjalan
    mqttClient.disconnect();
  } else {
    Serial.println("\n[WiFi] Gagal reconnect, coba lagi nanti...");
  }
}

// ============================================================
//  Kirim GPS via HTTP (tidak berubah dari sebelumnya)
// ============================================================
void pushGps() {
  if (WiFi.status() != WL_CONNECTED) return;
  if (httpBusy) {
    lastGpsPush = 0;
    Serial.println("[GPS] Skip — HTTP sedang sibuk");
    return;
  }

  httpBusy = true;

  HTTPClient http;
  http.setConnectTimeout(HTTP_TIMEOUT);
  http.setTimeout(HTTP_TIMEOUT);

  if (!http.begin(gpsClient, String(SERVER_URL) + "/api/device/gps")) {
    httpBusy = false;
    return;
  }

  addCommonHeaders(http);
  http.addHeader("Content-Type", "application/json");

  bool   valid = gps.location.isValid();
  double lat   = valid ? gps.location.lat() : 0.0;
  double lng   = valid ? gps.location.lng() : 0.0;
  int    sats  = gps.satellites.isValid() ? gps.satellites.value() : 0;

  String body = "{";
  body += "\"lat\":"        + String(lat, 6) + ",";
  body += "\"lng\":"        + String(lng, 6) + ",";
  body += "\"satellites\":" + String(sats)   + ",";
  body += "\"gps_valid\":"  + String(valid ? "true" : "false");
  body += "}";

  int code = http.POST(body);
  if (code > 0) {
    Serial.printf("[GPS] HTTP %d\n", code);
  } else {
    Serial.printf("[GPS] Gagal: %s\n", http.errorToString(code).c_str());
  }

  http.end();
  httpBusy = false;
}

// ============================================================
//  SETUP
// ============================================================
void setup() {
  Serial.begin(115200);

  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  gpsSerial.begin(GPS_BAUD, SERIAL_8N1, GPS_RX_PIN, GPS_TX_PIN);

  // HTTP client untuk GPS — bypass SSL verify
  gpsClient.setInsecure();

  // MQTT TLS client — bypass SSL verify (cocok untuk EMQX Cloud Serverless)
  mqttTlsClient.setInsecure();

  // Konfigurasi PubSubClient
  mqttClient.setServer(MQTT_HOST, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setKeepAlive(60);
  mqttClient.setSocketTimeout(10);
  mqttClient.setBufferSize(512);

  // Konek WiFi
  Serial.printf("\nKoneksi ke WiFi: %s\n", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(true);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
  }
  Serial.println("\nWiFi terhubung!");
  Serial.printf("IP ESP32 : %s\n", WiFi.localIP().toString().c_str());
  Serial.printf("Server   : %s\n", SERVER_URL);
  Serial.printf("MQTT Host: %s:%d\n", MQTT_HOST, MQTT_PORT);

  // Konek MQTT pertama kali
  mqttConnect();
}

// ============================================================
//  LOOP
// ============================================================
void loop() {
  // ── Baca GPS terus menerus ──
  while (gpsSerial.available() > 0) gps.encode(gpsSerial.read());

  unsigned long now = millis();

  // ── Jaga koneksi WiFi ──
  if (WiFi.status() != WL_CONNECTED) {
    checkWifi();
    return;  // skip sisa loop, tunggu WiFi tersambung dulu
  }

  // ── Jaga koneksi MQTT ──
  if (!mqttClient.connected()) {
    if (now - lastMqttReconnect >= MQTT_RECONNECT_INTERVAL) {
      lastMqttReconnect = now;
      mqttConnect();
    }
  } else {
    mqttClient.loop();
  }

  // ── Push GPS via HTTP ──
  if (now - lastGpsPush >= GPS_PUSH_INTERVAL) {
    lastGpsPush = now;
    pushGps();
  }
}
