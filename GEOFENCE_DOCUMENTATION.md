# Geofence Manager - Dokumentasi Fitur

## ✅ Yang Sudah Diimplementasikan

### 1. Database & Model
- ✅ Migration: `create_geofences_table` dengan fields:
  - `id` (Primary Key)
  - `name` (Nama geofence)
  - `latitude` (Desimal, presisi 8)
  - `longitude` (Desimal, presisi 8)
  - `radius` (Float, dalam meter)
  - `status` (Enum: active/inactive)
  - `description` (Optional)
  - `timestamps` (created_at, updated_at)

- ✅ Model: `App\Models\Geofence` dengan fillable fields

### 2. API Endpoints (Protected dengan Auth)
```
POST   /api/geofences          - Tambah geofence baru
GET    /api/geofences          - Ambil semua geofence
GET    /api/geofences/{id}     - Ambil detail geofence
PUT    /api/geofences/{id}     - Update geofence
DELETE /api/geofences/{id}     - Hapus geofence
```

### 3. Halaman Geofence (`/geofence`)
Fitur yang tersedia:

#### **Layout 2 Kolom:**
- **Kolom Kiri (Sidebar):**
  - Daftar geofence dengan info: nama, koordinat, radius, status
  - Tombol "Tambah" untuk membuat geofence baru
  - Tombol "Edit" dan "Hapus" untuk setiap geofence
  - Status badge: Aktif (hijau) / Nonaktif (merah)
  - Scrollable jika geofence banyak

- **Kolom Kanan (Map):**
  - Peta interaktif menggunakan Leaflet
  - Tampil circle (lingkaran) geofence yang dipilih
  - Marker di pusat geofence
  - Info bar di bawah menampilkan: nama, pusat (lat/lng), radius
  - Auto zoom ke geofence yang dipilih

#### **Modal Tambah/Edit:**
Fitur input dengan 2 tab untuk pemilihan lokasi:

**Tab 1: Manual**
- Input Latitude (manual)
- Input Longitude (manual)
- Input Radius (meter)
- Input Nama
- Select Status (Aktif/Nonaktif)
- Textarea Deskripsi (opsional)

**Tab 2: Pilih di Peta**
- Peta interaktif di dalam modal
- Klik titik di peta → auto-fill Latitude & Longitude
- Tombol "Gunakan Koordinat Ini" untuk memindahkan ke manual tab
- Ukuran peta responsive terhadap modal

#### **Fitur Interaksi:**
- ✅ Klik geofence di sidebar → tampil di peta dengan circle + marker
- ✅ Tombol "Tambah" → buka modal untuk geofence baru
- ✅ Tombol "Edit" → pre-fill form dengan data existing
- ✅ Tombol "Hapus" → konfirmasi, lalu hapus
- ✅ Klik di peta (tab map) → auto-fill koordinat
- ✅ Switch tab → smooth transition
- ✅ Form validation → harus isi required fields

## 🚀 Cara Menggunakan

### Step 1: Akses Halaman Geofence
```
URL: http://localhost:8000/geofence
(Sudah login sebagai user terverifikasi)
```

### Step 2: Tambah Geofence Baru
1. Klik tombol **"+ Tambah"** di sidebar kiri
2. Pilih metode input lokasi:
   - **Manual**: Ketik latitude/longitude langsung
   - **Peta**: Klik di peta untuk select lokasi
3. Isi nama, radius, status, deskripsi (opsional)
4. Klik **"Simpan"**

### Step 3: View & Manage
- Klik geofence di sidebar → lihat di peta
- Klik **Edit** → ubah data
- Klik **Hapus** → hapus geofence
- Peta auto-zoom ke geofence yang dipilih

## 📱 API Usage Examples

### Create Geofence
```bash
curl -X POST http://localhost:8000/api/geofences \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: {token}" \
  -H "Cookie: {session_cookie}" \
  -d '{
    "name": "Kantor",
    "latitude": -6.200000,
    "longitude": 106.800000,
    "radius": 500,
    "status": "active",
    "description": "Area kantor pusat"
  }'
```

### Get All Geofences
```bash
curl http://localhost:8000/api/geofences \
  -H "Cookie: {session_cookie}"
```

### Update Geofence
```bash
curl -X PUT http://localhost:8000/api/geofences/1 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: {token}" \
  -H "Cookie: {session_cookie}" \
  -d '{
    "status": "inactive"
  }'
```

### Delete Geofence
```bash
curl -X DELETE http://localhost:8000/api/geofences/1 \
  -H "X-CSRF-TOKEN: {token}" \
  -H "Cookie: {session_cookie}"
```

## 🔗 Integrasi Telegram Bot (Next Step)

Untuk integrasi notifikasi ke Telegram, akan memerlukan:

1. **Geofence Alert Model** (untuk track alert status)
2. **Background Job/Queue** (untuk check geofence di background)
3. **Telegram Bot Handler** (untuk send notifikasi)

Contoh flow:
```
GPS dari ESP32
    ↓ POST /api/device/gps
Laravel Cache (esp_latest)
    ↓ Background job (every 5 detik)
Check distance ke semua geofence active
    ↓ Jika di dalam geofence yang berbeda
Create GeofenceAlert + Send Telegram
    ↓ Dashboard display notifikasi real-time
```

## 🎯 Testing Checklist

- [ ] Login ke dashboard
- [ ] Navigasi ke halaman Geofence
- [ ] Tambah geofence baru (manual input)
- [ ] Tambah geofence dengan peta picker
- [ ] Klik geofence → tampil di peta
- [ ] Edit geofence → update data
- [ ] Hapus geofence → confirm dialog
- [ ] Check API responses di DevTools
- [ ] Responsive design (test di berbagai ukuran)

## 📝 Database Schema

```sql
CREATE TABLE geofences (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  radius FLOAT NOT NULL,
  status ENUM('active', 'inactive') DEFAULT 'active',
  description TEXT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

**Status**: Ready for Testing ✓
**Created**: April 28, 2026
**Version**: 1.0
