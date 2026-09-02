<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'is_paid',
        'work_rate',
        'source',
        'is_substitute',
    ];

    protected $casts = [
        'date' => 'date',
        'is_paid' => 'boolean',
        'work_rate' => 'float',
        'is_substitute' => 'boolean',
    ];
}
