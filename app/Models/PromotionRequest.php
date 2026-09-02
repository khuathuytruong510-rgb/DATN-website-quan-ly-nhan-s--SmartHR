<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRequest extends Model
{
    public const CHANGE_PROMOTION = 'promotion';
    public const CHANGE_SALARY_RAISE = 'salary_raise';
    public const CHANGE_BOTH = 'both';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'employee_id',
        'change_type',
        'old_position_id',
        'old_position',
        'new_position_id',
        'new_position',
        'department_id',
        'old_base_salary',
        'new_base_salary',
        'old_allowance',
        'new_allowance',
        'effective_date',
        'reason',
        'document_number',
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
        'old_base_salary' => 'float',
        'new_base_salary' => 'float',
        'old_allowance' => 'float',
        'new_allowance' => 'float',
        'effective_date' => 'date',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function oldPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'old_position_id');
    }

    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    public function hasPositionChange(): bool
    {
        return in_array($this->change_type, [self::CHANGE_PROMOTION, self::CHANGE_BOTH], true)
            && filled($this->new_position);
    }

    public function hasSalaryChange(): bool
    {
        return (float) $this->new_base_salary > 0
            && in_array($this->change_type, [self::CHANGE_PROMOTION, self::CHANGE_SALARY_RAISE, self::CHANGE_BOTH], true);
    }

    public function changeTypeLabel(): string
    {
        return match ($this->change_type) {
            self::CHANGE_PROMOTION => 'Thăng chức',
            self::CHANGE_SALARY_RAISE => 'Tăng lương',
            self::CHANGE_BOTH => 'Thăng chức & tăng lương',
            default => ucfirst((string) $this->change_type),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ Giám đốc duyệt',
            self::STATUS_APPROVED => 'Đã duyệt — tự động cập nhật',
            self::STATUS_APPLIED => 'Đã áp dụng',
            self::STATUS_REJECTED => 'Bị từ chối',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => $this->status ? ucfirst($this->status) : '—',
        };
    }
}