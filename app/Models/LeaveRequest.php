<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'days',
        'half_day',
        'type',
        'reason',
        'is_urgent',
        'urgent_reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'days' => 'float',
            'half_day' => 'boolean',
            'is_urgent' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return \App\Support\LeaveTypes::label($this->type);
    }

    public function isPaidLeave(): bool
    {
        return \App\Support\LeaveTypes::isPaid($this->type);
    }
}
