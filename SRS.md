# Software Requirements Specification (SRS)

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini menjelaskan spesifikasi kebutuhan fungsional dan non-fungsional untuk proyek "ESP32 Tracker".
Sistem ini menghubungkan perangkat ESP32 dengan aplikasi web Laravel untuk memonitor GPS kendaraan, relay mesin, dan geofencing.

### 1.2 Ruang Lingkup
Aplikasi ini menyediakan:
- Monitoring GPS real-time dari ESP32
- Manajemen status relay (on/off)
- Penyimpanan sesi GPS ketika ESP32 offline
- CRUD geofence pada antarmuka web
- Deteksi masuk/keluar area geofence
- Notifikasi Telegram untuk peristiwa geofence
- Dashboard web berbasis Laravel dengan otentikasi pengguna

### 1.3 Definisi, Akronim, dan Singkatan
- ESP32: Mikrokontroler yang mengirim data GPS ke server
- GPS: Global Positioning System
- Geofence: Zona geografis dalam bentuk radius yang dipantau
- Relay: Saklar elektronik yang mengendalikan mesin/kendaraan
- API: Application Programming Interface
- CRUD: Create, Read, Update, Delete
- WIT: Waktu Indonesia Timur

### 1.4 Referensi
- Laravel documentation
- Project source code dalam folder `app/`, `routes/`, dan `resources/views/`
- Konfigurasi Telegram di `config/services.php`

## 2. Deskripsi Umum

### 2.1 Perspektif Produk
Sistem adalah aplikasi web back-end Laravel yang bertindak sebagai pusat komunikasi antara:
- Perangkat fisik ESP32
- Browser pengguna yang terotentikasi
- Layanan Telegram untuk notifikasi

Produk berjalan di server PHP dengan database relasional, cache, dan session.

### 2.2 Fitur Produk
1. Terima data GPS dari ESP32 melalui endpoint `POST /api/device/gps`
2. Kembalikan perintah relay terakhir ke ESP32 via `GET /api/device/command`
3. Simpan status terakhir GPS ke cache untuk dashboard
4. Simpan data sesi GPS ke DB ketika ESP32 offline
5. Nyalakan/matikan relay dari browser dengan `POST /api/relay/on` dan `POST /api/relay/off`
6. Kelola geofence dari antarmuka web: `GET`, `POST`, `PUT`, `DELETE` pada `/api/geofences`
7. Kirim notifikasi Telegram saat kendaraan masuk/keluar geofence
8. Reset state geofence saat browser load/refresh dashboard via `POST /api/geofence/reset-state`

### 2.3 Karakteristik Pengguna
- Administrator/operator yang memiliki akun login Laravel
- Teknisi atau pemilik kendaraan yang memantau lokasi dan status mesin

### 2.4 Batasan Operasional
- Browser harus menggunakan session auth Laravel untuk akses web
- ESP32 harus menggunakan middleware `esp.auth` untuk mengakses endpoint device
- Notifikasi Telegram bergantung pada konfigurasi `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID`

### 2.5 Asumsi dan Ketergantungan
- Sistem berjalan dengan PHP 8.2.x dan Laravel 11
- Database tersedia dan migrasi dijalankan
- Cache driver tersedia untuk menyimpan status ESP32 dan geofence
- ESP32 mengirim data GPS valid secara periodik
- Browser mendukung JavaScript dan AJAX

## 3. Kebutuhan Spesifik

### 3.1 Antarmuka Eksternal

#### 3.1.1 Antarmuka Pengguna
- Halaman login dan dashboard Laravel standard
- Halaman `dashboard` untuk status ESP32 dan kontrol relay
- Halaman `geofence` untuk manajemen zona geofence
- Halaman `history` untuk melihat sesi GPS yang tersimpan

#### 3.1.2 Antarmuka Perangkat Keras
- ESP32 mengirim data GPS dan menerima perintah relay
- Format data GPS:
  - `lat` (numeric)
  - `lng` (numeric)
  - `satellites` (integer)
  - `gps_valid` (boolean)

#### 3.1.3 Antarmuka Perangkat Lunak
- Telegram API: `https://api.telegram.org/bot{token}/sendMessage`
- Database MySQL / MariaDB untuk tabel `gps_logs`, `relay_logs`, `geofences`
- Cache Laravel untuk status online/offline, track buffer, dan geofence state

#### 3.1.4 Antarmuka Komunikasi
- ESP32 menggunakan HTTP/HTTPS untuk request ke endpoint API
- Browser menggunakan AJAX/fetch untuk call API internal

### 3.2 Kebutuhan Fungsional

#### 3.2.1 Autentikasi dan Autentikasi Perangkat
- Sistem harus memaksa pengguna login untuk akses web
- Endpoint device dilindungi dengan middleware `esp.auth`

#### 3.2.2 Penerimaan GPS dari ESP32
- Endpoint `POST /api/device/gps` menerima data GPS yang tervalidasi
- Jika `gps_valid` true, data titik GPS ditambahkan ke cache `esp_track`
- Status `esp_online` diupdate setiap penerimaan GPS

#### 3.2.3 Pengambilan Perintah Relay oleh ESP32
- Endpoint `GET /api/device/command` mengembalikan state relay terakhir
- Field respons `relay` bernilai `true` atau `false`

#### 3.2.4 Manajemen Relay dari Browser
- `POST /api/relay/on` mengubah `relay_logs.state` menjadi `on`
- `POST /api/relay/off` mengubah `relay_logs.state` menjadi `off`
- Relay log disimpan dengan atribut `triggered_by`

#### 3.2.5 Pengelolaan Geofence
- `GET /api/geofences` mengembalikan daftar semua geofence
- `POST /api/geofences` membuat geofence baru dengan data:
  - `name`, `latitude`, `longitude`, `radius`, `status`, `description`
- `GET /api/geofences/{id}` menampilkan detail satu geofence
- `PUT /api/geofences/{id}` memperbarui geofence
- `DELETE /api/geofences/{id}` menghapus geofence

#### 3.2.6 Evaluasi Geofence dan Notifikasi
- Setiap data GPS valid dari ESP32 memicu fungsi `checkGeofenceAlerts`
- Sistem menghitung jarak antara lokasi GPS dan pusat geofence
- Jika titik berada di dalam radius dan sebelumnya belum di dalam area, kirim notifikasi `enter`
- Jika titik berada di luar dan sebelumnya di dalam area, kirim notifikasi `exit`
- Notifikasi dikirim melalui kelas `TelegramService`
- Reset state geofence via `POST /api/geofence/reset-state` untuk memungkinkan event kembali ter-trigger setelah browser refresh

#### 3.2.7 Penyimpanan Sesi GPS
- Jika ESP32 tidak mengirimkan update selama lebih dari 15 detik, sistem menganggap offline
- Saat offline, buffer `esp_track` disimpan ke tabel `gps_logs`
- Sesi GPS menyimpan `session_id`, `track`, `started_at`, dan `ended_at`
- `GET /api/history` menampilkan 20 sesi terakhir dengan informasi ringkas
- `DELETE /api/history/{id}` menghapus sesi tertentu
- `POST /api/session/save` memaksa penyimpanan sesi aktif saat ini

### 3.3 Kebutuhan Non-Fungsional

#### 3.3.1 Kinerja
- Respon API harus cepat untuk mendukung refresh dashboard dan polling status
- Cache dipakai untuk data GPS terkini dan state sementara

#### 3.3.2 Keandalan
- Sistem harus mendeteksi ESP32 offline bila tidak ada update selama 15 detik
- Data sesi harus disimpan sekali saat offline, tidak duplikat

#### 3.3.3 Keamanan
- Web harus dilindungi otentikasi Laravel bawaan
- Device endpoint dibatasi oleh middleware `esp.auth`
- Data input divalidasi untuk mencegah input invalid
- `TelegramService` memeriksa konfigurasi token/chat_id sebelum kirim

#### 3.3.4 Skalabilitas dan Maintanabilitas
- Arsitektur Laravel memisahkan controller, model, service, dan views
- Geofence disimpan di DB dan diproses secara dinamis untuk setiap update GPS

#### 3.3.5 Portabilitas
- Aplikasi dapat dijalankan di lingkungan yang mendukung PHP 8.2 dan Laravel 11
- Bergantung pada driver cache Laravel yang tersedia

### 3.4 Kebutuhan Data

#### 3.4.1 Entitas Data Utama
- `Geofence`:
  - `id`, `name`, `latitude`, `longitude`, `radius`, `status`, `description`, timestamps
- `GpsLog`:
  - `id`, `session_id`, `track`, `started_at`, `ended_at`, timestamps
- `RelayLog`:
  - `id`, `state`, `triggered_by`, timestamps

#### 3.4.2 Format Data
- `track` disimpan sebagai JSON array objek titik GPS
- `relay` response berupa boolean
- Notifikasi Telegram dikirim dalam format HTML plain text

### 3.5 Kebutuhan Lingkungan Operasi
- Web server PHP 8.2
- Database MySQL/MariaDB
- Cache provider Laravel (file, Redis, atau array selama runtime)
- Koneksi internet untuk Telegram API
- Browser modern dengan dukungan JavaScript

### 3.6 Kriteria Penerimaan
- Data GPS diterima dan disimpan sementara ketika valid
- State relay berubah saat pengguna menekan tombol on/off
- Geofence dapat dibuat, diperbarui, dibaca, dan dihapus dari dashboard
- Telegram menerima notifikasi saat event geofence ter-trigger
- Riwayat sesi GPS muncul di halaman history
- Mode offline ESP32 terdeteksi bila tidak ada update selama >15 detik

## 4. Use Case Utama

### 4.1 Use Case: Monitor GPS dan Mesin
- Aktor: Pengguna Web
- Deskripsi: Melihat status GPS, konektivitas ESP32, dan status relay di dashboard

### 4.2 Use Case: Kontrol Relay Mesin
- Aktor: Pengguna Web
- Deskripsi: Menyalakan atau mematikan relay pada perangkat melalui tombol web

### 4.3 Use Case: Kelola Geofence
- Aktor: Pengguna Web
- Deskripsi: Membuat, melihat, mengubah, dan menghapus zona geofence

### 4.4 Use Case: Deteksi Geofence dan Notifikasi
- Aktor: Sistem ESP32 & Telegram
- Deskripsi: Sistem memonitor pergerakan GPS dan mengirim alert Telegram saat kendaraan masuk/keluar zona geofence

### 4.5 Use Case: Simpan Sesi GPS
- Aktor: Sistem
- Deskripsi: Menyimpan sesi track GPS ke database saat perangkat offline

## 5. Lampiran

### 5.1 Variabel Konfigurasi Eksternal
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `APP_URL`, `DB_*`, `CACHE_DRIVER`, `SESSION_DRIVER`

### 5.2 File Konfigurasi Penting
- `routes/api.php`
- `routes/web.php`
- `app/Http/Controllers/Api/EspController.php`
- `app/Http/Controllers/Api/GeofenceController.php`
- `app/Http/Controllers/Api/GeofenceAlertController.php`
- `app/Services/TelegramService.php`
- `database/migrations/*`

### 5.3 Catatan Implementasi
- Reset state geofence dijalankan saat halaman dashboard di-load/refresh
- `RelayLog` menyimpan satu catatan terakhir untuk command `GET /api/device/command`
- `GpsLog` menyimpan track penuh session untuk analisis riwayat
