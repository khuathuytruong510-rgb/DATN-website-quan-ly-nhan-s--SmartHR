<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deduction extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
