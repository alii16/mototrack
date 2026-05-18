<?php

use App\Http\Controllers\Api\EspController;
use App\Http\Controllers\Api\GeofenceController;
use App\Http\Controllers\Api\GeofenceAlertController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint untuk ESP32 — pakai API key, tidak butuh login
|--------------------------------------------------------------------------
*/
Route::middleware('esp.auth')->prefix('device')->group(function () {
    Route::post('/gps',    [EspController::class, 'receiveGps']);
    Route::get('/command', [EspController::class, 'getCommand']);
});

/*
|--------------------------------------------------------------------------
| Endpoint untuk Browser — butuh login (session auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:web')->group(function () {
    Route::get('/status',          [EspController::class, 'status']);
    Route::post('/relay/on',       [EspController::class, 'relayOn']);
    Route::post('/relay/off',      [EspController::class, 'relayOff']);
    Route::get('/history',         [EspController::class, 'history']);
    Route::delete('/history/{id}', [EspController::class, 'deleteHistory']);
    Route::post('/session/save',   [EspController::class, 'saveSession']);
    Route::post('/session/flush', [EspController::class, 'flushSession']);

    // Reset state geofence → dipanggil saat browser load/refresh dashboard
    // agar notifikasi "masuk" bisa terkirim ulang meski ESP32 sudah di dalam area
    Route::post('/geofence/reset-state', [EspController::class, 'resetGeofenceState']);

    // Geofence CRUD
    Route::get('/geofences',         [GeofenceController::class, 'index']);
    Route::post('/geofences',        [GeofenceController::class, 'store']);
    Route::get('/geofences/{id}',    [GeofenceController::class, 'show']);
    Route::put('/geofences/{id}',    [GeofenceController::class, 'update']);
    Route::delete('/geofences/{id}', [GeofenceController::class, 'destroy']);

    // Geofence alert -> Telegram (tidak simpan ke DB)
    Route::post('/geofence/alert', [GeofenceAlertController::class, 'send']);
});

Route::get('/debug-auth', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'user'       => auth('web')->user()?->email,
        'guard'      => auth()->getDefaultDriver(),
    ]);
});

// Debug ping (hapus setelah testing)
Route::get('/ping', fn() => response()->json(['status' => 'ok', 'time' => now()]));