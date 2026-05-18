<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relay_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('state', ['on', 'off']);
            $table->string('triggered_by')->default('web'); // 'web' atau 'device'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relay_logs');
    }
};