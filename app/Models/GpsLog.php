<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsLog extends Model
{
    protected $fillable = [
        'session_id',
        'track',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'track'      => 'array',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];
}