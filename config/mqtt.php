<?php

/**
 * config/mqtt.php
 *
 * Konfigurasi koneksi MQTT ke HiveMQ Cloud.
 * Nilai diambil dari .env — jangan hardcode di sini.
 */

return [

    /*
     * Host HiveMQ Cloud kamu.
     * Format: xxxxxxxx.s1.eu.hivemq.cloud
     * Ambil dari: HiveMQ Console → Clusters → Connection Settings
     */
    'host' => env('MQTT_HOST', 'f547b61204cc4dae9d1c9a7d316ba15f.s1.eu.hivemq.cloud'),

    /*
     * Port TLS (wajib untuk HiveMQ Cloud free tier)
     */
    'port' => env('MQTT_PORT', 8883),

    /*
     * Kredensial.
     * Buat di: HiveMQ Console → Access Management → Credentials
     */
    'username' => env('MQTT_USERNAME', 'esp32-naurah-k7x9mQ2p'),
    'password' => env('MQTT_PASSWORD', 'esp32-naurah-k7x9mQ2p'),

];