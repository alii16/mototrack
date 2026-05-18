<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EspAuth
{
    /**
     * Validasi setiap request dari ESP32 menggunakan API key
     * yang dikirim lewat header X-ESP-Key.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-ESP-Key');

        if (!$key || $key !== env('ESP32_API_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}