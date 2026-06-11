<p align="center">
  <img src="public/img/logo-footer.png" width="320" alt="Laravel Logo"/>
</p>

<h1 align="center">ESP32 Tracker</h1>

<p align="center">
  Sistem pemantauan lokasi GPS dan kendali relay berbasis ESP32 dengan notifikasi Telegram dan manajemen geofence — dibangun menggunakan Laravel 12.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red?logo=laravel" alt="Laravel 12"/>
  <img src="https://img.shields.io/badge/ESP32-Arduino-blue?logo=arduino" alt="ESP32"/>
  <img src="https://img.shields.io/badge/Maps-Leaflet.js-green?logo=leaflet" alt="Leaflet"/>
  <img src="https://img.shields.io/badge/Notifikasi-Telegram-2CA5E0?logo=telegram" alt="Telegram"/>
  <img src="https://img.shields.io/badge/MQTT-EMQX%20Cloud-purple?logo=mqtt" alt="MQTT"/>
  <img src="https://img.shields.io/badge/Database-MySQL-orange?logo=mysql" alt="MySQL"/>
</p>

<p align="center">
  <a href="https://mototrack.codingbyali.site" target="_blank">
    <img src="https://img.shields.io/badge/🌐%20Live%20Demo-mototrack.codingbyali.site-00bcd4?style=for-the-badge" alt="Live Demo"/>
  </a>
</p>

---

## 📖 Tentang Project

Project ini merupakan tugas akhir (skripsi) yang membangun sistem **pelacakan kendaraan/objek secara real-time** menggunakan mikrokontroler ESP32 yang dilengkapi modul GPS. Data lokasi dikirim ke server Laravel dan divisualisasikan di dashboard web menggunakan peta interaktif Leaflet.js.

Sistem ini juga dilengkapi fitur **geofencing** — pengguna dapat membuat area virtual di peta, dan sistem akan mengirimkan notifikasi otomatis ke Telegram setiap kali objek yang dipantau masuk atau keluar dari area tersebut.

Kendali relay menggunakan protokol **MQTT** untuk komunikasi real-time dengan latensi rendah, menggantikan mekanisme HTTP polling yang sebelumnya digunakan.

---

## ✨ Fitur Utama

- **Pelacakan GPS Real-time** — posisi ESP32 ditampilkan di peta dan diperbarui setiap beberapa detik tanpa reload halaman
- **Kendali Relay via MQTT** — nyalakan/matikan relay dari dashboard web dengan latensi rendah (~50–300ms) tanpa polling
- **Geofence Management** — buat, edit, dan hapus area geofence di peta secara interaktif
- **Notifikasi Telegram** — peringatan otomatis dikirim saat objek masuk atau keluar area geofence (event-based, tanpa spam)
- **Riwayat Sesi Perjalanan** — setiap perjalanan disimpan ke database dan dapat ditampilkan ulang di peta
- **Status ESP32** — indikator online/offline ESP32 ditampilkan secara real-time di dashboard
- **Autentikasi Pengguna** — sistem login untuk melindungi akses dashboard

---

## 🏗️ Arsitektur Sistem

```
  ┌──────────────┐  HTTP POST /api/device/gps (5 dtk)  ┌──────────────────────┐
  │    ESP32     │ ─────────────────────────────────►  │                      │
  │  GPS + Relay │                                     │   Laravel 12 Server  │
  │  (di motor)  │ ◄── MQTT subscribe relay/command ─┐ │   mototrack.domain   │
  └──────────────┘                                   │ │   MySQL + Cache      │
                                                     │ └──────────────────────┘
                               ┌─────────────────────┴───┐          │
                               │   MQTT Broker           │          ▼
                               │   Mosquitto (lokal)     │  ┌──────────────────┐
                               │   EMQX Cloud (prod)     │  │  Telegram Bot    │
                               └──────────────┬──────────┘  └──────────────────┘
  ┌─────────────┐  AJAX /api/status (5 dtk)   │
  │   Browser   │ ◄──────────────────────────►│
  │  Dashboard  │                             │
  │  Leaflet.js │ ◄── MQTT WS relay/state ────┘
  └─────────────┘     (instan, tanpa polling)
```

**Pola komunikasi:**
- ESP32 **mendorong (push)** data GPS ke Laravel tiap 5 detik via HTTP
- Kendali relay menggunakan **MQTT pub/sub** — browser publish perintah lewat Laravel, ESP32 subscribe dan langsung eksekusi tanpa polling
- Browser subscribe ke broker MQTT via WebSocket untuk menerima update relay secara instan
- Notifikasi Telegram dikirim **langsung dari server** saat ESP32 mengirim koordinat yang masuk/keluar geofence

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Blade, Leaflet.js, Vanilla JS, MQTT.js |
| Database | MySQL |
| Mikrokontroler | ESP32 + Modul GPS (TinyGPS++) |
| Protokol Relay | MQTT (PubSubClient) |
| Broker MQTT (hosted) | EMQX Cloud Serverless |
| Broker MQTT (lokal) | Mosquitto |
| Notifikasi | Telegram Bot API |
| Autentikasi | Laravel Breeze |
| Cache | Laravel Cache (file/redis) |

---

## ⚙️ Skema Wiring ESP32

| Perangkat | Pin ESP32 |
|---|---|
| Relay (IN) | D2 (GPIO2) |
| GPS (TX) | D4 (GPIO4) |
| GPS (RX) | D5 (GPIO5) |
| Relay (GND) | GND |
| Relay (VCC) | VIN |
| GPS (GND) | GND |
| GPS (VCC) | 3V3 |

---

## 🚀 Instalasi & Konfigurasi

### Prasyarat

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL
- Arduino IDE (untuk upload firmware ESP32)
- Mosquitto MQTT Broker (untuk development lokal)

### 1. Clone & Install Dependensi

```bash
git clone https://github.com/username/esp32-tracker.git
cd esp32-tracker

composer install
npm install && npm run build
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:

```env
APP_URL=http://localhost:8000

DB_DATABASE=esp32_tracker
DB_USERNAME=root
DB_PASSWORD=

# API Key untuk autentikasi ESP32
ESP32_API_KEY=YOUR_API_KEY

# Telegram Bot
TELEGRAM_BOT_TOKEN=token_bot_kamu
TELEGRAM_CHAT_ID=chat_id_kamu

# MQTT — versi lokal (Mosquitto)
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_TOPIC_RELAY_CMD=mototrack/naurah/relay/command
MQTT_TOPIC_RELAY_STATE=mototrack/naurah/relay/state

# MQTT — versi hosted (EMQX Cloud), aktifkan saat deploy
# EMQX_API_URL=https://xxxx.ala.us-east-1.emqx.cloud:8443/api/v5
# EMQX_API_KEY=your_api_key
# EMQX_API_SECRET=your_api_secret
# EMQX_TOPIC_RELAY_CMD=mototrack/naurah/relay/command
# EMQX_TOPIC_RELAY_STATE=mototrack/naurah/relay/state
```

### 3. Migrasi Database

```bash
mysql -u root -e "CREATE DATABASE esp32_tracker;"

php artisan migrate
php artisan db:seed  # opsional: data awal
```

### 4. Setup Mosquitto (lokal)

Download dan install [Mosquitto](https://mosquitto.org/download) untuk Windows.

Tambahkan konfigurasi berikut di bagian atas `C:\Program Files\mosquitto\mosquitto.conf` (buka Notepad as Administrator):

```conf
listener 1883 0.0.0.0
allow_anonymous true

listener 9001 0.0.0.0
protocol websockets
```

Mosquitto berjalan otomatis sebagai Windows Service. Untuk restart setelah edit config: buka `services.msc` → **Mosquitto Broker** → Restart.

### 5. Jalankan Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Flag `--host=0.0.0.0` wajib agar ESP32 bisa mengakses server dari jaringan lokal.

Buka browser → `http://localhost:8000`

### 6. Tambahkan MQTT.js ke Dashboard

Tambahkan baris berikut di `resources/views/dashboard.blade.php` sebelum tag `</body>`, **sebelum** script `dashboard.js`:

```html
<script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
```

### 7. Konfigurasi & Upload Firmware ESP32

Buka file `skripsiINO_lokal.ino` di Arduino IDE, sesuaikan bagian konfigurasi:

```cpp
const char* WIFI_SSID  = "NamaWiFi";
const char* WIFI_PASS  = "PasswordWiFi";
const char* SERVER_URL = "http://192.168.x.x:8000"; // IP komputer (cek: ipconfig)
const char* API_KEY    = "API_key_kamu";

// MQTT — Mosquitto lokal
const char* MQTT_HOST  = "192.168.x.x";  // IP komputer (sama dengan SERVER_URL)
const int   MQTT_PORT  = 1883;
```

Install library yang dibutuhkan di Arduino IDE (**Tools → Manage Libraries**):

| Library | Author |
|---|---|
| `TinyGPS++` | Mikal Hart |
| `PubSubClient` | Nick O'Leary |

Upload sketch ke ESP32, buka Serial Monitor (baud 115200). Pastikan muncul:
```
WiFi terhubung!
[MQTT] Terhubung!
[MQTT] Subscribe: mototrack/naurah/relay/command
```

---

## 📡 API Endpoints

### Untuk ESP32 (header `X-ESP-Key` wajib)

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/device/gps` | Kirim data GPS dari ESP32 |
| GET  | `/api/device/command` | Ambil state relay terbaru (fallback saat reconnect) |

### Untuk Browser (butuh login)

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/status` | Status ESP32, GPS, dan relay |
| POST | `/api/relay/on` | Nyalakan relay (publish MQTT) |
| POST | `/api/relay/off` | Matikan relay (publish MQTT) |
| GET | `/api/history` | Riwayat sesi perjalanan |
| POST | `/api/session/save` | Simpan sesi aktif |
| GET | `/api/geofences` | Daftar geofence |
| POST | `/api/geofences` | Tambah geofence |
| PUT | `/api/geofences/{id}` | Update geofence |
| DELETE | `/api/geofences/{id}` | Hapus geofence |

---

## 📨 Topik MQTT

| Topic | Publisher | Subscriber | Keterangan |
|---|---|---|---|
| `mototrack/naurah/relay/command` | Laravel | ESP32 | Perintah ON/OFF relay, payload `"1"` / `"0"` |
| `mototrack/naurah/relay/state` | Laravel | Browser | State relay terakhir, **retained** — sync saat reconnect |
| `mototrack/naurah/relay/confirm` | ESP32 | Browser (testing) | Konfirmasi relay sudah berubah secara fisik |

---

## 🗄️ Struktur Database

```
gps_logs
├── id
├── session_id
├── track (JSON — array koordinat)
├── started_at
└── ended_at

relay_logs
├── id
├── state (on/off)
├── triggered_by (web/device)
└── created_at

geofences
├── id
├── name
├── latitude
├── longitude
├── radius (meter)
├── status (active/inactive)
└── description
```

---

## 📦 Deploy ke Hosting

### 1. Ganti file yang digunakan

| File lokal | File hosted |
|---|---|
| `skripsiINO_lokal.ino` | `skripsiINO_public.ino` |
| `EspController.php` | `EspController_public.php` (paste  isinya ke app/Http/Controllers/Api/EspController.php) |
| `dashboard_lokal.js` | `dashboard_final.js` (dengan konfigurasi EMQX) |

### 2. Konfigurasi `.env` di server

```env
EMQX_API_URL=https://xxxx.ala.us-east-1.emqx.cloud:8443/api/v5
EMQX_API_KEY=your_api_key
EMQX_API_SECRET=your_api_secret
EMQX_TOPIC_RELAY_CMD=mototrack/naurah/relay/command
EMQX_TOPIC_RELAY_STATE=mototrack/naurah/relay/state
```

### 3. Update firmware ESP32

Sesuaikan nilai berikut di `skripsiINO_mqtt.ino`:

```cpp
const char* SERVER_URL = "https://namadomain.com";

const char* MQTT_HOST  = "xxxx.ala.us-east-1.emqx.cloud";
const int   MQTT_PORT  = 8883;   // TLS
const char* MQTT_USER  = "username_emqx";
const char* MQTT_PASS  = "password_emqx";
```

> **Catatan:** Pada versi hosted, Laravel mempublish perintah MQTT via **EMQX REST API** (HTTPS port 443) — bukan koneksi TCP langsung — sehingga kompatibel dengan shared hosting yang biasanya memblokir outbound port 8883.

---

## 📝 Lisensi

Project ini dibuat untuk keperluan tugas akhir (skripsi). Silakan digunakan sebagai referensi dengan menyertakan atribusi yang sesuai.