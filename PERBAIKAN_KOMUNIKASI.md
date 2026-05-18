# Perbaikan Komunikasi ESP32 ↔ Laravel & Dashboard

## 🔧 Masalah yang Ditemukan & Diperbaiki

### 1. **Error JSON di Dashboard (Sidebar)**
**Masalah:** `Error: Unexpected token '<', "<!DOCTYPE "... is not valid JSON`

**Penyebab:** 
- Endpoint `/api/status` dan `/api/history` memerlukan login (middleware `auth`)
- Jika user tidak terautentikasi, Laravel redirect ke login page (return HTML, bukan JSON)
- Dashboard mencoba parse HTML sebagai JSON → Error

**Solusi:**
- ✅ Tambahkan proper error handling di `fetchHistory()` function
- ✅ Check HTTP status dan handle redirect (401)
- ✅ Display error message di dashboard dengan warna warning

### 2. **Tombol & Sidebar Tidak Bisa Diklik**
**Masalah:** Layar tidak responsif terhadap klik pada sidebar

**Penyebab:** 
- CSS issue: `pointer-events` tidak eksplisit di-set
- Map Leaflet mungkin overlay sidebar

**Solusi:**
- ✅ Tambahkan `pointer-events: auto` ke `aside`, `.card`, dan `#relay-btn`
- ✅ Set `z-index: 10` untuk sidebar, `z-index: 0` untuk map
- ✅ Button sekarang fully clickable

### 3. **User Tidak Bisa Login**
**Masalah:** Dashboard meminta login tapi user tidak ada atau email tidak verified

**Penyebab:**
- User ada (admin@gmail.com, adm001@gmail.com) tapi `email_verified_at` = NULL
- Laravel memerlukan email verified untuk dashboard access

**Solusi:**
- ✅ Verify email user: `admin@gmail.com` 
- ✅ Reset password: `password123` (untuk testing)

## 📝 Konfigurasi yang Verified

### Database (✓ OK)
- ✅ MySQL tersambung
- ✅ Semua migrations sudah berjalan:
  - `users_table` 
  - `relay_logs_table` 
  - `gps_logs_table`
  - `cache_table`

### API Keys (✓ OK)
- ✅ ESP32_API_KEY = `esp32-naurah-k7x9mQ2p`
- ✅ Middleware `esp.auth` registered di `bootstrap/app.php`
- ✅ Routes protected dengan middleware

### Console Logging (✓ OK)
- ✅ Ditambahkan debug logging di `fetchStatus()` dan `toggleRelay()`
- ✅ Check browser DevTools → Console untuk melihat:
  - `[STATUS]` logs
  - `[RELAY]` logs
  - `[HISTORY]` logs

## 🚀 Cara Test

### Step 1: Login ke Dashboard
```
URL: http://localhost:8000/dashboard
Email: admin@gmail.com
Password: password123
```

### Step 2: Cek Console Browser (F12)
Lihat logs untuk verify API calls working:
```
[STATUS] Response: 200
[STATUS] Data: {esp_online: false, relay: false, ...}
[RELAY] Sending to: /api/relay/on
```

### Step 3: Kirim Data GPS dari ESP32
Serial Monitor di Arduino akan show: `[GPS] OK` jika berhasil

### Step 4: Verifikasi di Dashboard
- GPS Data akan muncul di sidebar (Lat, Lng, Satelit, Update)
- Relay status akan update ketika button diklik
- Map akan show position ESP32

## 🔌 Komunikasi Flow

```
ESP32 (192.168.1.15:5000)
    ↓ POST /api/device/gps (dengan header X-ESP-Key)
Laravel API
    ↓ Middleware EspAuth validate key
    ↓ Store ke Cache (real-time)
    ↓ Store ke Database GpsLog (saat offline)
    
Browser/Dashboard (authenticated user)
    ↓ GET /api/status
    ↓ Middleware auth check session
    ↓ Return: esp_online, relay, gps_valid, lat, lng, satellites
    ↓ Display di sidebar + map
```

## 📱 Testing Endpoints

### Test GPS Endpoint (dari terminal)
```powershell
curl -X POST http://127.0.0.1:8000/api/device/gps `
  -H "Content-Type: application/json" `
  -H "X-ESP-Key: esp32-naurah-k7x9mQ2p" `
  -d '{"lat": -3.6527, "lng": 128.1947, "satellites": 12, "gps_valid": true}'
```

### Test Relay Control (dari terminal, perlu login session)
```powershell
# Tidak bisa via curl tanpa session cookie, perlu browser
# Atau dari dashboard dengan authenticated session
```

## 🎯 Next: ESP32 Connection

Pastikan ESP32 sketch sudah updated:
- ✅ IP Server: `http://192.168.1.15:8000`
- ✅ API Key: `esp32-naurah-k7x9mQ2p` (sama dengan .env)
- ✅ GPS Push Interval: 5000ms (5 detik)
- ✅ Relay Poll Interval: 800ms (0.8 detik - responsif)

## 🧪 Debug Commands

Jika ada masalah, test via command line:

```bash
# Check API key is loaded
php artisan tinker
> env('ESP32_API_KEY')

# Check user data
php artisan tinker
> App\Models\User::where('email','admin@gmail.com')->first()

# Check cache
php artisan tinker
> Cache::get('esp_latest')

# Check recent GPS logs
php artisan tinker
> App\Models\GpsLog::latest()->first()
```

---

**Last Updated:** April 28, 2026
**Status:** Ready for Testing ✓
