<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'status',
        'description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'float',
        'status' => 'string',
    ];
}
