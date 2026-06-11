<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/geofence', function () {
    return view('geofence');
})->middleware(['auth', 'verified'])->name('geofence');

Route::get('/history', function () {
    return view('history');
})->middleware(['auth', 'verified'])->name('history');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-mqtt', function () {
    $cmd = '"C:\\Program Files\\mosquitto\\mosquitto_pub.exe" -h 127.0.0.1 -p 1883 -t "test/topic" -m "hello" 2>&1';
    exec($cmd, $output, $code);
    return response()->json(['code' => $code, 'output' => $output]);
});

require __DIR__.'/auth.php';