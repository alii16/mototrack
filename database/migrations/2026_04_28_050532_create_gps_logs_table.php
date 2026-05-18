<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();         // satu sesi = satu nyala ESP32
            $table->json('track');                         // array titik [{lat,lng,sat,ts}]
            $table->timestamp('started_at')->useCurrent(); // ESP32 pertama kali kirim
            $table->timestamp('ended_at')->nullable();     // ESP32 terdeteksi mati
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_logs');
    }
};