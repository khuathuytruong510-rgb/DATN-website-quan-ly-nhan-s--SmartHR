<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPeriodLock extends Model
{
    protected $fillable = [
        'month',
        'year',
        'is_locked',
        'locked_at',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
        'unlock_reason',
        'hr_verified_at',
        'hr_verified_by',
        'unlock_request_status',
        'unlock_requested_at',
        'unlock_requested_by',
        'unlock_request_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'hr_verified_at' => 'datetime',
            'unlock_requested_at' => 'datetime',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_verified_by');
    }

    public function unlockRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlock_requested_by');
    }

    public function periodLabel(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }

    public function hasPendingUnlockRequest(): bool
    {
        return $this->unlock_request_status === 'pending';
    }

    public function isHrVerified(): bool
    {
        return (bool) $this->hr_verified_at;
    }
}
