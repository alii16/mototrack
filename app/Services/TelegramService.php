<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;
    private string $chatId;

    public function __construct()
    {
        // config() lebih reliable daripada env() di dalam class
        $this->botToken = config('services.telegram.token', '');
        $this->chatId   = config('services.telegram.chat_id', '');
    }

    /**
     * Kirim pesan plain text ke Telegram (tanpa parse_mode agar aman).
     */
    public function send(string $message): bool
    {
        if (!$this->botToken || !$this->chatId) {
            Log::warning('[Telegram] TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID kosong di .env');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

            $response = Http::timeout(8)
                ->withoutVerifying()   // hindari SSL error di hosting shared
                ->post($url, [
                    'chat_id'              => $this->chatId,
                    'text'                 => $message,
                    'parse_mode'           => 'HTML',   // HTML lebih toleran dari Markdown
                    'disable_notification' => false,
                ]);

            $body = $response->json();

            // Telegram mengembalikan {"ok": true} jika berhasil
            if ($body['ok'] ?? false) {
                Log::info('[Telegram] Notifikasi terkirim ke chat_id: ' . $this->chatId);
                return true;
            }

            Log::error('[Telegram] Gagal kirim', [
                'status'      => $response->status(),
                'description' => $body['description'] ?? 'unknown',
                'chat_id'     => $this->chatId,
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('[Telegram] Exception: ' . $e->getMessage());
            return false;
        }
    }

        /**
     * Kirim notifikasi geofence — mendukung event masuk (enter) dan keluar (exit).
     *
     * @param string $event  'enter' | 'exit'
     */
    public function sendGeofenceAlert(
        string $geoName,
        float  $lat,
        float  $lng,
        int    $distance,
        string $event = 'enter'
    ): bool {
        $mapsUrl = "https://maps.google.com/?q={$lat},{$lng}";
        $time    = now()->timezone('Asia/Jayapura')->format('d M Y, H:i:s') . ' WIT';
        $name    = htmlspecialchars($geoName);

        if ($event === 'exit') {
            $message =
                "🔔 <b>Keluar dari area geofence</b>

" .
                "📍 <b>Area:</b> {$name}
" .
                "📌 <b>Koordinat:</b> {$lat}, {$lng}
" .
                "🕐 <b>Waktu:</b> {$time}

" .
                "🗺 <a href=\"{$mapsUrl}\">Lihat di Google Maps</a>";
        } else {
            $message =
                "🚨 <b>Masuk area geofence</b>

" .
                "📍 <b>Area:</b> {$name}
" .
                "📌 <b>Koordinat:</b> {$lat}, {$lng}
" .
                "📏 <b>Jarak ke pusat:</b> {$distance} m
" .
                "🕐 <b>Waktu:</b> {$time}

" .
                "🗺 <a href=\"{$mapsUrl}\">Lihat di Google Maps</a>";
        }

        return $this->send($message);
    }
}