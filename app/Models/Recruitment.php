<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recruitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'title',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'status',
        'posted_at',
        'closed_at',
    ];

    protected $casts = [
        'salary_min' => 'integer',
        'salary_max' => 'integer',
        'posted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
