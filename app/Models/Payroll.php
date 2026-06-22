<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'base_salary',
        'allowance',
        'deduction',
        'total_salary',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if payroll is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    /**
     * Get the total salary with all calculations
     */
    public function getTotalWithCalculationsAttribute(): int|float
    {
        return ($this->base_salary + $this->allowance) - $this->deduction;
    }
}
