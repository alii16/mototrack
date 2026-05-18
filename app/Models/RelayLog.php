<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelayLog extends Model
{
    protected $fillable = [
        'state',
        'triggered_by',
    ];
}