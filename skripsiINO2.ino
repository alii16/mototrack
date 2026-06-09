#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <TinyGPS++.h>
#include <HardwareSerial.h>

// ============================================================
const char* WIFI_SSID  = "Nama WiFi";
const char* WIFI_PASS  = "Sandi WiFi";
const char* SERVER_URL = "Server yang sudah dihosting (atau pakai ip jaringan lokal)";
const char* API_KEY    = "API Key (cocokkan dengan .env laravel)";

#define RELAY_PIN          2
#define GPS_RX_PIN         4
#define GPS_TX_PIN         5
#define GPS_BAUD           9600

#define GPS_PUSH_INTERVAL  5000   // kirim GPS tiap 5 detik
#define CMD_POLL_INTERVAL  2000   // poll relay tiap 2 detik
#define HTTP_TIMEOUT       4000   // timeout lebih pendek agar tidak blocking terlalu lama
// ============================================================

TinyGPSPlus    gps;
HardwareSerial gpsSerial(1);

// ── Dua client terpisah — GPS dan CMD tidak saling rebutan koneksi SSL ──
WiFiClientSecure gpsClient;
WiFiClientSecure cmdClient;

bool  relayState  = false;
ulong lastGpsPush = 0;
ulong lastCmdPoll = 0;

// ── Flag mutex — cegah dua request HTTP jalan bersamaan ──
// Kalau sedang ada request aktif, request berikutnya di-skip
// dan timer TIDAK di-reset supaya langsung dicoba di iterasi berikutnya
bool httpBusy = false;

// ── Helper header ──
void addCommonHeaders(HTTPClient &http) {
  http.addHeader("X-ESP-Key",  API_KEY);
  http.addHeader("User-Agent", "ESP32HTTPClient/1.0");
  http.addHeader("Accept",     "application/json");
}

// ── Kirim GPS ──
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
    if (code != 200)
      Serial.printf("[GPS] Response: %s\n", http.getString().c_str());
  } else {
    Serial.printf("[GPS] Gagal: %s\n", http.errorToString(code).c_str());
  }

  http.end();
  httpBusy = false;
}

// ── Poll relay ──
void pollCommand() {
  if (WiFi.status() != WL_CONNECTED) return;
  if (httpBusy) {
    lastCmdPoll = 0;
    Serial.println("[CMD] Skip — HTTP sedang sibuk");
    return;
  }

  httpBusy = true;

  HTTPClient http;
  http.setConnectTimeout(HTTP_TIMEOUT);
  http.setTimeout(HTTP_TIMEOUT);

  if (!http.begin(cmdClient, String(SERVER_URL) + "/api/device/command")) {
    httpBusy = false;
    return;
  }

  addCommonHeaders(http);

  int code = http.GET();
  if (code == 200) {
    String payload = http.getString();
    Serial.printf("[CMD] Payload: %s\n", payload.c_str());

    bool shouldOn = payload.indexOf("true") >= 0;
    if (shouldOn != relayState) {
      relayState = shouldOn;
      digitalWrite(RELAY_PIN, shouldOn ? HIGH : LOW);
      Serial.printf("[RELAY] -> %s\n", shouldOn ? "ON" : "OFF");
    }
  } else {
    Serial.printf("[CMD] Gagal HTTP %d: %s\n", code, http.errorToString(code).c_str());
    if (code > 0)
      Serial.printf("[CMD] Response: %s\n", http.getString().substring(0, 200).c_str());
  }

  http.end();
  httpBusy = false;
}

// ── Reconnect WiFi ──
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
    // Reset kedua client agar koneksi SSL lama tidak tersisa
    gpsClient.stop();
    gpsClient.setInsecure();
    cmdClient.stop();
    cmdClient.setInsecure();
    httpBusy = false;
  } else {
    Serial.println("\n[WiFi] Gagal reconnect, coba lagi nanti...");
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  gpsSerial.begin(GPS_BAUD, SERIAL_8N1, GPS_RX_PIN, GPS_TX_PIN);

  // Bypass verifikasi SSL certificate
  gpsClient.setInsecure();
  cmdClient.setInsecure();

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
}

void loop() {
  // Baca GPS terus menerus — tidak boleh terlewat
  while (gpsSerial.available() > 0) gps.encode(gpsSerial.read());

  unsigned long now = millis();

  // Poll relay
  if (now - lastCmdPoll >= CMD_POLL_INTERVAL) {
    lastCmdPoll = now;
    checkWifi();
    pollCommand();
  }

  // Push GPS
  if (now - lastGpsPush >= GPS_PUSH_INTERVAL) {
    lastGpsPush = now;
    checkWifi();
    pushGps();
  }
}
