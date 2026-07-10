<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;


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

    'confirm_token',
    'approved_at',

    'paid_at',
];
    protected $casts = [
    'approved_at' => 'datetime',
    'paid_at' => 'datetime',
];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
 * Tạo token xác nhận bảng lương
 */
public function generateConfirmToken(): void
{
    $this->confirm_token = Str::uuid()->toString();
    $this->save();
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

    /**
 * Nhân viên xác nhận bảng lương
 */
public function markAsApproved(): void
{
    $this->status = 'approved';
    $this->approved_at = now();

    $this->save();
}

/**
 * Đánh dấu đã thanh toán
 */
public function markAsPaid(): void
{
    $this->status = 'paid';
    $this->paid_at = now();

    $this->save();
}

public function isPending(): bool
{
    return $this->status === 'pending';
}

public function isApproved(): bool
{
    return $this->status === 'approved';
}

public function isPaid(): bool
{
    return $this->status === 'paid';
}
}
