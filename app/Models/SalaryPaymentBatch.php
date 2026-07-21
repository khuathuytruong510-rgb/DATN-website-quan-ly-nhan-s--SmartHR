<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryPaymentBatch extends Model
{
    protected $fillable = [
        'code', 'name', 'month', 'year',
        'total_items', 'total_amount', 'total_paid', 'total_remaining',
        'status', 'created_by', 'approved_by',
        'approved_at', 'processed_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_remaining' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class, 'batch_id');
    }

    public static function generateCode(): string
    {
        $prefix = 'BATCH-' . now()->format('Ymd');
        $last = self::where('code', 'like', $prefix . '%')->count();
        return $prefix . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return round(($this->total_paid / $this->total_amount) * 100, 1);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'secondary',
            'processing' => 'info',
            'completed' => 'success',
            'partial' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }
}
