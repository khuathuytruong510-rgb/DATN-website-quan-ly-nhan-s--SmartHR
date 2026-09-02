<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeletionRequest extends Model
{
    public const KIND_EMPLOYEE = 'employee';
    public const KIND_DEPARTMENT = 'department';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'kind',
        'requestable_id',
        'requestable_type',
        'name',
        'payload',
        'reason',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'applied_by',
        'applied_at',
        'cancellation_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'requestable_id' => 'integer',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function kindLabel(): string
    {
        return $this->kind === self::KIND_EMPLOYEE ? 'Nhân viên' : 'Phòng ban';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ Giám đốc duyệt',
            self::STATUS_APPROVED => 'Đã duyệt — chờ HR thực hiện xóa',
            self::STATUS_APPLIED => 'Đã xóa',
            self::STATUS_REJECTED => 'Bị từ chối',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => $this->status ? ucfirst($this->status) : '—',
        };
    }
}