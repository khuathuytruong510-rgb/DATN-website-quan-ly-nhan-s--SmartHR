<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'level',
        'salary_range_min',
        'salary_range_max',
    ];

    protected $casts = [
        'salary_range_min' => 'integer',
        'salary_range_max' => 'integer',
    ];
}
