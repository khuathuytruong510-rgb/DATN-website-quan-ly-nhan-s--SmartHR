<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    use HasFactory;

    public const SOURCE_REQUESTED = 'requested';
    public const SOURCE_ASSIGNED = 'assigned';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'source',
        'assigned_by',
        'date',
        'start_time',
        'end_time',
        'requested_start',
        'requested_end',
        'approved_start',
        'approved_end',
        'actual_start',
        'actual_end',
        'actual_minutes',
        'attendance_id',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'actual_minutes' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isAssigned(): bool
    {
        return $this->source === self::SOURCE_ASSIGNED;
    }

    public function isOpenForWork(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_IN_PROGRESS], true);
    }

    public function payrollHours(): float
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return 0.0;
        }

        return round(((int) $this->actual_minutes) / 60, 2);
    }

    public function requestedStartTime(): ?string
    {
        return $this->requested_start ?: $this->start_time;
    }

    public function requestedEndTime(): ?string
    {
        return $this->requested_end ?: $this->end_time;
    }

    public function approvedStartTime(): ?string
    {
        return $this->approved_start ?: null;
    }

    public function approvedEndTime(): ?string
    {
        return $this->approved_end ?: null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt / được chỉ định',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_IN_PROGRESS => 'Đang tăng ca',
            self::STATUS_COMPLETED => 'Đã tính giờ thực tế — chờ xác nhận',
            self::STATUS_VERIFIED => 'Đã xác nhận — đưa vào lương',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => $this->status ?? '—',
        };
    }

    public function sourceLabel(): string
    {
        return $this->isAssigned() ? 'HR chỉ định' : 'Nhân viên đăng ký';
    }

    public function clock(?string $time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return '—';
        }

        return substr($time, 0, 5);
    }

    public function requestedWindowLabel(): string
    {
        return $this->clock($this->requestedStartTime()).' – '.$this->clock($this->requestedEndTime());
    }

    public function approvedWindowLabel(): string
    {
        if (! $this->approvedStartTime() && ! $this->approvedEndTime()) {
            return '—';
        }

        return $this->clock($this->approvedStartTime()).' – '.$this->clock($this->approvedEndTime());
    }

    public function actualWindowLabel(): string
    {
        if ($this->actual_minutes === null && ! $this->actual_start && ! $this->actual_end) {
            return '—';
        }

        $span = $this->clock($this->actual_start).' – '.$this->clock($this->actual_end);
        if ($this->actual_minutes !== null) {
            $hours = intdiv((int) $this->actual_minutes, 60);
            $mins = ((int) $this->actual_minutes) % 60;
            $span .= sprintf(' (%d giờ %02d phút)', $hours, $mins);
        }

        return $span;
    }
}
