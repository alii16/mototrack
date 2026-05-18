<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Geofence;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    /**
     * GET /api/geofences - Ambil semua geofence
     */
    public function index()
    {
        $geofences = Geofence::all();
        return response()->json($geofences);
    }

    /**
     * POST /api/geofences - Tambah geofence baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:1',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $geofence = Geofence::create($validated);
        return response()->json(['message' => 'Geofence berhasil ditambahkan', 'data' => $geofence], 201);
    }

    /**
     * GET /api/geofences/{id} - Ambil detail geofence
     */
    public function show($id)
    {
        $geofence = Geofence::find($id);
        if (!$geofence) {
            return response()->json(['error' => 'Geofence tidak ditemukan'], 404);
        }
        return response()->json($geofence);
    }

    /**
     * PUT /api/geofences/{id} - Update geofence
     */
    public function update(Request $request, $id)
    {
        $geofence = Geofence::find($id);
        if (!$geofence) {
            return response()->json(['error' => 'Geofence tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:1',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $geofence->update($validated);
        return response()->json(['message' => 'Geofence berhasil diperbarui', 'data' => $geofence]);
    }

    /**
     * DELETE /api/geofences/{id} - Hapus geofence
     */
    public function destroy($id)
    {
        $geofence = Geofence::find($id);
        if (!$geofence) {
            return response()->json(['error' => 'Geofence tidak ditemukan'], 404);
        }

        $geofence->delete();
        return response()->json(['message' => 'Geofence berhasil dihapus']);
    }
}
