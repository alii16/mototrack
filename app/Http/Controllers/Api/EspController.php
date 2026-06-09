<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsLog;
use App\Models\RelayLog;
use App\Models\Geofence;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EspController extends Controller
{
    // Berapa detik tanpa kabar dari ESP32 dianggap "mati"
    const OFFLINE_TIMEOUT = 90;

    // ─────────────────────────────────────────────────────
    // ENDPOINT UNTUK ESP32 (dilindungi middleware EspAuth)
    // ─────────────────────────────────────────────────────

    /**
     * ESP32 -> Laravel: terima satu titik GPS.
     * Titik disimpan di Cache (RAM).
     * Baru disimpan ke DB saat ESP32 terdeteksi offline.
     *
     * POST /api/device/gps
     */
    public function receiveGps(Request $request)
    {
        $data = $request->validate([
            'lat'        => 'required|numeric',
            'lng'        => 'required|numeric',
            'satellites' => 'required|integer|min:0',
            'gps_valid'  => 'required|boolean',
        ]);

        // Ambil atau buat session_id untuk sesi ini
        $sessionId = Cache::get('esp_session_id');
        if (!$sessionId) {
            $sessionId = uniqid('esp_', true);
            Cache::put('esp_session_id', $sessionId, now()->addHours(24));
            Cache::put('esp_session_started', now()->toDateTimeString(), now()->addHours(24));
        }

        // Tandai ESP32 masih hidup (perpanjang 15 detik dari sekarang)
        Cache::put('esp_online', true, now()->addSeconds(self::OFFLINE_TIMEOUT));

        // Tambahkan titik ke track buffer (hanya jika GPS valid)
        if ($data['gps_valid']) {
            $track   = Cache::get('esp_track', []);
            $track[] = [
                'lat'  => $data['lat'],
                'lng'  => $data['lng'],
                'sat'  => $data['satellites'],
                'ts'   => now()->toDateTimeString(),
            ];
            Cache::put('esp_track', $track, now()->addHours(24));
        }

        // Simpan status terakhir untuk dashboard
        Cache::put('esp_latest', [
            'gps_valid'  => $data['gps_valid'],
            'lat'        => $data['lat'],
            'lng'        => $data['lng'],
            'satellites' => $data['satellites'],
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(24));

        // ── CHECK GEOFENCE & KIRIM NOTIFIKASI ──
        if ($data['gps_valid']) {
            $this->checkGeofenceAlerts($data['lat'], $data['lng']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * ESP32 -> Laravel: ambil perintah relay terbaru.
     * GET /api/device/command
     */
    public function getCommand()
    {
        // Tandai ESP32 masih hidup
        Cache::put('esp_online', true, now()->addSeconds(self::OFFLINE_TIMEOUT));

        $latest = RelayLog::find(1);

        return response()->json([
            'relay' => $latest ? ($latest->state === 'on') : false,
        ]);
    }

    // ─────────────────────────────────────────────────────
    // ENDPOINT UNTUK BROWSER (butuh login)
    // ─────────────────────────────────────────────────────

    /**
     * Browser: status terbaru ESP32, relay, dan GPS.
     * GET /api/status
     */
    public function status()
    {
        $online  = Cache::get('esp_online', false);
        $latest  = Cache::get('esp_latest', null);
        $relay   = RelayLog::find(1);

        // Jika ESP32 baru saja offline — simpan sesi ke DB
        // $this->flushSessionIfOffline($online);

        return response()->json([
            'esp_online' => $online,
            'relay'      => $relay ? ($relay->state === 'on') : false,
            'gps_valid'  => $latest['gps_valid']  ?? false,
            'lat'        => $latest['lat']         ?? 0.0,
            'lng'        => $latest['lng']         ?? 0.0,
            'satellites' => $latest['satellites']  ?? 0,
            'updated_at' => $latest['updated_at']  ?? null,
        ]);
    }

    /**
     * Browser: nyalakan relay.
     * POST /api/relay/on
     */
    public function relayOn()
    {
        RelayLog::updateOrCreate(
            ['id' => 1],
            ['state' => 'on', 'triggered_by' => 'web']
        );
        return response()->json(['relay' => true]);
    }

    /**
     * Browser: matikan relay.
     * POST /api/relay/off
     */
    public function relayOff()
    {
        RelayLog::updateOrCreate(
            ['id' => 1],
            ['state' => 'off', 'triggered_by' => 'web']
        );
        return response()->json(['relay' => false]);
    }

    /**
     * Browser: daftar sesi perjalanan yang sudah tersimpan.
     * GET /api/history
     */
    public function history()
    {
        $logs = GpsLog::latest()
            ->get(['id', 'session_id', 'started_at', 'ended_at', 'track']);

        return response()->json($logs->map(fn($l) => [
            'id'         => $l->id,
            'started_at' => $l->started_at,
            'ended_at'   => $l->ended_at,
            'points'     => count($l->track ?? []),
            'track'      => $l->track,
        ]));
    }

    /**
     * Browser: reset state geofence di cache server.
     *
     * Dipanggil sekali saat dashboard di-load/refresh oleh browser.
     * Dengan menghapus semua key `geofence_state_{id}`, maka
     * checkGeofenceAlerts() akan memperlakukan SEMUA geofence seolah
     * belum pernah dicek (state = null), sehingga notifikasi "masuk"
     * akan terkirim kembali pada titik GPS berikutnya jika ESP32
     * memang sedang berada di dalam area.
     *
     * POST /api/geofence/reset-state
     */
    public function resetGeofenceState()
    {
        try {
            $geofences = Geofence::where('status', 'active')->pluck('id');

            foreach ($geofences as $id) {
                Cache::forget("geofence_state_{$id}");
            }

            Log::info('[Geofence] State di-reset oleh browser (page load/refresh). Total: ' . $geofences->count());

            return response()->json([
                'ok'    => true,
                'reset' => $geofences->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('[Geofence] resetGeofenceState error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────
    // INTERNAL: flush sesi ke DB saat ESP32 offline
    // ─────────────────────────────────────────────────────

    /**
     * Dipanggil browser saat deteksi ESP offline (bukan tiap poll).
     * POST /api/session/flush
     */
    public function flushSession()
    {
        $online = Cache::get('esp_online', false);
        if ($online) {
            return response()->json(['ok' => false, 'message' => 'ESP masih online, tidak perlu flush.']);
        }
        $this->flushSessionIfOffline(false);
        return response()->json(['ok' => true]);
    }

    private function flushSessionIfOffline(bool $online): void
    {
        // Kalau masih online atau tidak ada sesi aktif, skip
        if ($online) return;

        $sessionId = Cache::get('esp_session_id');
        $track     = Cache::get('esp_track', []);
        $startedAt = Cache::get('esp_session_started');

        // Hanya simpan jika ada session aktif dan ada titik GPS terkumpul
        if ($sessionId && count($track) > 0) {
            // Cegah double-save dengan flag
            $saved = Cache::get('esp_session_saved_' . $sessionId, false);
            if (!$saved) {
                GpsLog::create([
                    'session_id' => $sessionId,
                    'track'      => $track,
                    'started_at' => $startedAt ?? now(),
                    'ended_at'   => now(),
                ]);

                // Tandai sudah disimpan, bersihkan buffer
                Cache::put('esp_session_saved_' . $sessionId, true, now()->addHours(1));
                Cache::forget('esp_session_id');
                Cache::forget('esp_track');
                Cache::forget('esp_session_started');
                Cache::forget('esp_latest');
            }
        }
    }

    /**
    * Browser: paksa simpan sesi aktif ke DB sekarang.
    * POST /api/session/save
    */
    public function saveSession()
    {
        $sessionId = Cache::get('esp_session_id');
        $track     = Cache::get('esp_track', []);
        $startedAt = Cache::get('esp_session_started');

        if (!$sessionId || count($track) === 0) {
            return response()->json(['ok' => false, 'message' => 'Tidak ada sesi aktif atau track kosong.']);
        }

        $saved = Cache::get('esp_session_saved_' . $sessionId, false);
        if ($saved) {
            return response()->json(['ok' => false, 'message' => 'Sesi ini sudah pernah disimpan.']);
        }

        GpsLog::create([
            'session_id' => $sessionId,
            'track'      => $track,
            'started_at' => $startedAt ?? now(),
            'ended_at'   => now(),
        ]);

        Cache::put('esp_session_saved_' . $sessionId, true, now()->addHours(1));

        return response()->json(['ok' => true, 'message' => 'Sesi berhasil disimpan.', 'points' => count($track)]);
    }

    // ─────────────────────────────────────────────────────
    // GEOFENCE ALERT LOGIC
    // ─────────────────────────────────────────────────────

    /**
     * Check apakah titik GPS masuk/keluar area geofence — event-based.
     *
     * State per geofence disimpan di Cache dengan key: geofence_state_{id}
     *   null  = belum pernah dicek (server baru start / cache expired / baru di-reset)
     *   true  = GPS sedang DI DALAM area
     *   false = GPS sedang DI LUAR area
     *
     * Notifikasi HANYA dikirim saat terjadi PERUBAHAN STATE:
     *   null/false → true  : event MASUK  → kirim notif "masuk"
     *   true       → false : event KELUAR → kirim notif "keluar"
     *
     * State di-reset ke null setiap kali dashboard di-load/refresh
     * via POST /api/geofence/reset-state, sehingga notif masuk
     * akan terkirim ulang pada GPS poll berikutnya.
     */
    private function checkGeofenceAlerts(float $lat, float $lng): void
    {
        try {
            $geofences = Geofence::where('status', 'active')->get();
            if ($geofences->isEmpty()) return;

            foreach ($geofences as $geo) {
                $distance  = $this->calculateDistance($lat, $lng, $geo->latitude, $geo->longitude);
                $isInside  = $distance <= $geo->radius;
                $cacheKey  = "geofence_state_{$geo->id}";
                $wasInside = Cache::get($cacheKey); // null | true | false

                // ── EVENT MASUK ──────────────────────────────────────────────
                // Trigger jika: state null (pertama kali / baru di-reset) atau false,
                // DAN posisi sekarang di dalam area.
                if ($isInside && $wasInside !== true) {
                    Log::info("[Geofence] EVENT MASUK '{$geo->name}' dist={$distance}m radius={$geo->radius}m wasInside=" . json_encode($wasInside));

                    $sent = (new TelegramService())->sendGeofenceAlert(
                        geoName:  $geo->name,
                        lat:      $lat,
                        lng:      $lng,
                        distance: (int) $distance,
                        event:    'enter'
                    );

                    if ($sent) {
                        Cache::put($cacheKey, true, now()->addHours(24));
                        Log::info("[Geofence] Notif MASUK terkirim: '{$geo->name}'");
                    } else {
                        Log::error("[Geofence] Gagal kirim notif MASUK: '{$geo->name}'");
                    }
                }

                // ── EVENT KELUAR ─────────────────────────────────────────────
                // Trigger HANYA jika state sebelumnya benar-benar true (pernah masuk),
                // dan sekarang posisi sudah di luar. State null tidak trigger keluar.
                elseif (!$isInside && $wasInside === true) {
                    Log::info("[Geofence] EVENT KELUAR '{$geo->name}' dist={$distance}m radius={$geo->radius}m");

                    $sent = (new TelegramService())->sendGeofenceAlert(
                        geoName:  $geo->name,
                        lat:      $lat,
                        lng:      $lng,
                        distance: (int) $distance,
                        event:    'exit'
                    );

                    if ($sent) {
                        Cache::put($cacheKey, false, now()->addHours(24));
                        Log::info("[Geofence] Notif KELUAR terkirim: '{$geo->name}'");
                    } else {
                        Log::error("[Geofence] Gagal kirim notif KELUAR: '{$geo->name}'");
                    }
                }

                // ── TIDAK ADA PERUBAHAN ──────────────────────────────────────
                // wasInside === true  dan isInside === true  → masih di dalam, diam
                // wasInside === false dan isInside === false → masih di luar, diam
            }
        } catch (\Exception $e) {
            Log::error('[Geofence] Exception: ' . $e->getMessage());
        }
    }

    /**
     * Hitung jarak antara dua titik koordinat dalam meter (Haversine formula).
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Browser: hapus sesi perjalanan.
     * DELETE /api/history/{id}
     */
    public function deleteHistory($id)
    {
        $log = GpsLog::find($id);
        if (!$log) {
            return response()->json(['ok' => false, 'message' => 'Sesi tidak ditemukan.'], 404);
        }

        $log->delete();

        return response()->json(['ok' => true, 'message' => 'Sesi berhasil dihapus.']);
    }
}