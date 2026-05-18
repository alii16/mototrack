<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeofenceAlertController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'geo_name' => 'required|string',
            'geo_id'   => 'required|integer',
            'lat'      => 'required|numeric',
            'lng'      => 'required|numeric',
            'distance' => 'required|integer',
        ]);

        try {
            $telegram = new TelegramService();

            $sent = $telegram->sendGeofenceAlert(
                geoName:  $data['geo_name'],
                lat:      (float) $data['lat'],
                lng:      (float) $data['lng'],
                distance: (int)   $data['distance'],
            );

            return response()->json([
                'ok'      => $sent,
                'message' => $sent
                    ? 'Notifikasi Telegram terkirim'
                    : 'Gagal kirim Telegram (cek laravel.log)',
            ]);

        } catch (\Exception $e) {
            Log::error('[GeofenceAlert] Exception: ' . $e->getMessage());
            return response()->json([
                'ok'      => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
}