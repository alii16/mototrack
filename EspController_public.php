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
use Illuminate\Support\Facades\Http;

class EspController extends Controller
{
    const OFFLINE_TIMEOUT = 90;

    // =========================================================
    //  KONFIGURASI EMQX — sesuaikan dengan deployment kamu
    // =========================================================
    //
    //  Cara dapat nilai-nilai ini:
    //  1. Buka halaman deployment EMQX Cloud kamu
    //  2. Klik menu "REST API" atau "Overview" → cari API key
    //  3. Klik "API Keys" → "Add API Key" → catat Key + Secret
    //  4. Base URL ada di halaman Overview:
    //     Format: https://{host}:{api_port}/api/v5
    //     Untuk Serverless biasanya port API-nya 8443 atau 443
    //     Contoh: https://xxxx.ala.us-east-1.emqx.cloud:8443/api/v5
    //
    //  Letakkan di .env agar tidak hardcode:
    //
    //  EMQX_API_URL=https://xxxx.ala.us-east-1.emqx.cloud:8443/api/v5
    //  EMQX_API_KEY=your_api_key_here
    //  EMQX_API_SECRET=your_api_secret_here
    //  EMQX_TOPIC_RELAY_CMD=mototrack/naurah/relay/command
    //  EMQX_TOPIC_RELAY_STATE=mototrack/naurah/relay/state
    // =========================================================

    // ─────────────────────────────────────────────────────
    // ENDPOINT UNTUK ESP32 (dilindungi middleware EspAuth)
    // ─────────────────────────────────────────────────────

    /**
     * ESP32 -> Laravel: terima satu titik GPS.
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

        $sessionId = Cache::get('esp_session_id');
        if (!$sessionId) {
            $sessionId = uniqid('esp_', true);
            Cache::put('esp_session_id', $sessionId, now()->addHours(24));
            Cache::put('esp_session_started', now()->toDateTimeString(), now()->addHours(24));
        }

        Cache::put('esp_online', true, now()->addSeconds(self::OFFLINE_TIMEOUT));

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

        Cache::put('esp_latest', [
            'gps_valid'  => $data['gps_valid'],
            'lat'        => $data['lat'],
            'lng'        => $data['lng'],
            'satellites' => $data['satellites'],
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(24));

        if ($data['gps_valid']) {
            $this->checkGeofenceAlerts($data['lat'], $data['lng']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * ESP32 -> Laravel: ambil state relay terbaru (FALLBACK saat ESP32 baru nyala).
     * Dipakai sebagai cadangan jika MQTT retained message belum diterima ESP32.
     * GET /api/device/command
     */
    public function getCommand()
    {
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
     *
     * Alur:
     * 1. Update state di DB
     * 2. Publish perintah "1" ke MQTT topic relay/command  ← real-time ke ESP32
     * 3. Publish state "1"  ke MQTT topic relay/state (retained) ← sync saat reconnect
     */
    public function relayOn()
    {
        RelayLog::updateOrCreate(
            ['id' => 1],
            ['state' => 'on', 'triggered_by' => 'web']
        );

        // Publish ke MQTT
        $this->mqttPublish(env('EMQX_TOPIC_RELAY_CMD',   'mototrack/naurah/relay/command'), '1', false);
        $this->mqttPublish(env('EMQX_TOPIC_RELAY_STATE', 'mototrack/naurah/relay/state'),   '1', true);

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

        // Publish ke MQTT
        $this->mqttPublish(env('EMQX_TOPIC_RELAY_CMD',   'mototrack/naurah/relay/command'), '0', false);
        $this->mqttPublish(env('EMQX_TOPIC_RELAY_STATE', 'mototrack/naurah/relay/state'),   '0', true);

        return response()->json(['relay' => false]);
    }

    // ─────────────────────────────────────────────────────
    // MQTT PUBLISH via EMQX HTTP API
    // ─────────────────────────────────────────────────────

    /**
     * Publish pesan MQTT via EMQX REST API.
     *
     * Kenapa pakai HTTP API bukan library PHP MQTT?
     * → Shared hosting biasanya blokir outbound TCP ke port 8883.
     *   Tapi port 443/HTTPS selalu terbuka. EMQX Cloud menyediakan
     *   REST API di port 8443 atau 443 untuk keperluan ini.
     *
     * @param string $topic   Topic MQTT tujuan
     * @param string $payload Isi pesan (string)
     * @param bool   $retain  true = broker simpan sebagai retained message
     */
    private function mqttPublish(string $topic, string $payload, bool $retain = false): void
    {
        $apiUrl    = rtrim(env('EMQX_API_URL', ''), '/');
        $apiKey    = env('EMQX_API_KEY', '');
        $apiSecret = env('EMQX_API_SECRET', '');

        if (!$apiUrl || !$apiKey || !$apiSecret) {
            Log::warning('[MQTT] EMQX API credentials belum dikonfigurasi di .env');
            return;
        }

        try {
            $response = Http::withBasicAuth($apiKey, $apiSecret)
                ->timeout(5)
                ->post("{$apiUrl}/publish", [
                    'topic'   => $topic,
                    'payload' => $payload,
                    'qos'     => 1,
                    'retain'  => $retain,
                ]);

            if ($response->successful()) {
                Log::info("[MQTT] Publish OK → topic={$topic} payload={$payload} retain=" . ($retain ? 'true' : 'false'));
            } else {
                Log::error("[MQTT] Publish gagal → HTTP {$response->status()}: {$response->body()}");
            }

        } catch (\Exception $e) {
            Log::error('[MQTT] Publish exception: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────
    // HISTORY, SESSION, GEOFENCE — tidak ada perubahan
    // ─────────────────────────────────────────────────────

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

    public function resetGeofenceState()
    {
        try {
            $geofences = Geofence::where('status', 'active')->pluck('id');

            foreach ($geofences as $id) {
                Cache::forget("geofence_state_{$id}");
            }

            Log::info('[Geofence] State di-reset oleh browser. Total: ' . $geofences->count());

            return response()->json([
                'ok'    => true,
                'reset' => $geofences->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('[Geofence] resetGeofenceState error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

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
        if ($online) return;

        $sessionId = Cache::get('esp_session_id');
        $track     = Cache::get('esp_track', []);
        $startedAt = Cache::get('esp_session_started');

        if ($sessionId && count($track) > 0) {
            $saved = Cache::get('esp_session_saved_' . $sessionId, false);
            if (!$saved) {
                GpsLog::create([
                    'session_id' => $sessionId,
                    'track'      => $track,
                    'started_at' => $startedAt ?? now(),
                    'ended_at'   => now(),
                ]);

                Cache::put('esp_session_saved_' . $sessionId, true, now()->addHours(1));
                Cache::forget('esp_session_id');
                Cache::forget('esp_track');
                Cache::forget('esp_session_started');
                Cache::forget('esp_latest');
            }
        }
    }

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

    private function checkGeofenceAlerts(float $lat, float $lng): void
    {
        try {
            $geofences = Geofence::where('status', 'active')->get();
            if ($geofences->isEmpty()) return;

            foreach ($geofences as $geo) {
                $distance  = $this->calculateDistance($lat, $lng, $geo->latitude, $geo->longitude);
                $isInside  = $distance <= $geo->radius;
                $cacheKey  = "geofence_state_{$geo->id}";
                $wasInside = Cache::get($cacheKey);

                if ($isInside && $wasInside !== true) {
                    Log::info("[Geofence] EVENT MASUK '{$geo->name}' dist={$distance}m");

                    $sent = (new TelegramService())->sendGeofenceAlert(
                        geoName:  $geo->name,
                        lat:      $lat,
                        lng:      $lng,
                        distance: (int) $distance,
                        event:    'enter'
                    );

                    if ($sent) Cache::put($cacheKey, true, now()->addHours(24));
                }
                elseif (!$isInside && $wasInside === true) {
                    Log::info("[Geofence] EVENT KELUAR '{$geo->name}' dist={$distance}m");

                    $sent = (new TelegramService())->sendGeofenceAlert(
                        geoName:  $geo->name,
                        lat:      $lat,
                        lng:      $lng,
                        distance: (int) $distance,
                        event:    'exit'
                    );

                    if ($sent) Cache::put($cacheKey, false, now()->addHours(24));
                }
            }
        } catch (\Exception $e) {
            Log::error('[Geofence] Exception: ' . $e->getMessage());
        }
    }

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