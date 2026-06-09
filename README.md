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

---

## ✨ Fitur Utama

- **Pelacakan GPS Real-time** — posisi ESP32 ditampilkan di peta dan diperbarui setiap beberapa detik tanpa reload halaman
- **Kendali Relay Jarak Jauh** — nyalakan/matikan relay yang terhubung ke ESP32 langsung dari dashboard web
- **Geofence Management** — buat, edit, dan hapus area geofence di peta secara interaktif
- **Notifikasi Telegram** — peringatan otomatis dikirim saat objek masuk atau keluar area geofence (event-based, tanpa spam)
- **Riwayat Sesi Perjalanan** — setiap perjalanan disimpan ke database dan dapat ditampilkan ulang di peta
- **Status ESP32** — indikator online/offline ESP32 ditampilkan secara real-time di dashboard
- **Autentikasi Pengguna** — sistem login untuk melindungi akses dashboard

---

## 🏗️ Arsitektur Sistem

```
┌─────────────┐        HTTP (push)         ┌──────────────────┐
│    ESP32    │ ─────────────────────────▶│                  │
│ GPS + Relay │ ◀──────────── perintah ── │  Laravel Server  │
└─────────────┘    API Key Authentication  │  (REST API)      │
                                           │                  │
┌─────────────┐        AJAX polling        │  MySQL Database  │
│   Browser   │ ◀────────────────────────▶│                  │
│  Dashboard  │    Session Authentication  └──────────────────┘
│ (Leaflet.js)│                                     │
└─────────────┘                                     ▼
                                           ┌──────────────────┐
                                           │  Telegram Bot    │
                                           │  (Notifikasi)    │
                                           └──────────────────┘
```

**Pola komunikasi:**
- ESP32 **mendorong (push)** data GPS ke Laravel tiap 5 detik — bukan Laravel yang menarik dari ESP32
- Browser melakukan **polling** ke Laravel tiap 2 detik untuk memperbarui tampilan
- Notifikasi Telegram dikirim **langsung dari server** saat ESP32 mengirim koordinat yang masuk/keluar geofence

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Blade, Leaflet.js, Vanilla JS |
| Database | MySQL |
| Mikrokontroler | ESP32 + Modul GPS (TinyGPS++) |
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
```

### 3. Migrasi Database

```bash
# Buat database terlebih dahulu di MySQL
mysql -u root -e "CREATE DATABASE esp32_tracker;"

php artisan migrate
php artisan db:seed  # opsional: data awal
```

### 4. Jalankan Server

```bash
php artisan serve
```

Buka browser → `http://localhost:8000`

### 5. Konfigurasi & Upload Firmware ESP32

Buka file `esp32_tracker.ino` di Arduino IDE, sesuaikan bagian konfigurasi:

```cpp
const char* WIFI_SSID   = "NamaWiFi";
const char* WIFI_PASS   = "PasswordWiFi";
const char* SERVER_URL  = "http://192.168.x.x:8000"; // IP komputer (lokal)
                        // atau "https://namadomain.com" (setelah deploy)
const char* API_KEY     = "API_key_kamu";   // sama dengan .env
```

Install library yang dibutuhkan di Arduino IDE (**Tools → Manage Libraries**):
- `TinyGPS++` by Mikal Hart

Upload sketch ke ESP32, buka Serial Monitor (baud 115200) — pastikan muncul `WiFi terhubung!`.

---

## 📡 API Endpoints

### Untuk ESP32 (header `X-ESP-Key` wajib)

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/device/gps` | Kirim data GPS dari ESP32 |
| GET  | `/api/device/command` | Ambil perintah relay terbaru |

### Untuk Browser (butuh login)

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/status` | Status ESP32, GPS, dan relay |
| POST | `/api/relay/on` | Nyalakan relay |
| POST | `/api/relay/off` | Matikan relay |
| GET | `/api/history` | Riwayat sesi perjalanan |
| POST | `/api/session/save` | Simpan sesi aktif |
| GET | `/api/geofences` | Daftar geofence |
| POST | `/api/geofences` | Tambah geofence |
| PUT | `/api/geofences/{id}` | Update geofence |
| DELETE | `/api/geofences/{id}` | Hapus geofence |

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

Setelah deploy, **hanya satu baris** yang perlu diubah di firmware ESP32:

```cpp
// Ganti:
const char* SERVER_URL = "http://192.168.x.x:8000";

// Menjadi:
const char* SERVER_URL = "https://namadomain.com";
```

Tambahkan `#define USE_HTTPS` di baris paling atas sketch untuk mengaktifkan koneksi HTTPS.

---

## 📝 Lisensi

Project ini dibuat untuk keperluan tugas akhir (skripsi). Silakan digunakan sebagai referensi dengan menyertakan atribusi yang sesuai.
