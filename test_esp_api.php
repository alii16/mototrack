<?php

/**
 * Script untuk test komunikasi ESP32 -> Laravel
 * Jalankan: php test_esp_api.php
 */

require 'vendor/autoload.php';

use Illuminate\Support\Facades\Cache;
use App\Models\GpsLog;
use App\Models\RelayLog;

// Bootstrap Laravel
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test ESP32 API Communication ===\n\n";

// 1. Test Cache
echo "1. Testing Cache...\n";
Cache::put('test_key', 'test_value', now()->addMinutes(5));
$val = Cache::get('test_key');
echo "   Cache: " . ($val === 'test_value' ? "✓ OK" : "✗ FAILED") . "\n";

// 2. Test Relay Logs
echo "\n2. Testing RelayLog Model...\n";
try {
    $relay = RelayLog::latest()->first();
    echo "   Latest RelayLog: " . ($relay ? "✓ Found (state={$relay->state})" : "✗ No records") . "\n";
    
    // Create test relay
    $new = RelayLog::create(['state' => 'on', 'triggered_by' => 'test']);
    echo "   Create test record: ✓ OK (id={$new->id})\n";
    
    // Clean up
    $new->delete();
    echo "   Delete test record: ✓ OK\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

// 3. Test GPS Logs  
echo "\n3. Testing GpsLog Model...\n";
try {
    $gps = GpsLog::latest()->first();
    echo "   Latest GpsLog: " . ($gps ? "✓ Found" : "✗ No records") . "\n";
    
    // Create test GPS
    $new = GpsLog::create([
        'session_id'  => 'test-' . uniqid(),
        'track'       => [['lat' => -3.6527, 'lng' => 128.1947, 'sat' => 12]],
        'started_at'  => now(),
        'ended_at'    => now(),
    ]);
    echo "   Create test record: ✓ OK (id={$new->id})\n";
    
    // Clean up
    $new->delete();
    echo "   Delete test record: ✓ OK\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

// 4. Test ESP API key
echo "\n4. Testing ESP32 API Key...\n";
$apiKey = env('ESP32_API_KEY');
echo "   API Key from .env: " . ($apiKey ? "✓ {$apiKey}" : "✗ NOT FOUND") . "\n";

echo "\n=== Test Complete ===\n";
