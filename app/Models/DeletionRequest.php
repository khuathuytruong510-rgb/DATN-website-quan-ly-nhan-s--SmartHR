<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DeletionRequest extends Model
{
    public const EMPLOYEE = 'employee';
    public const DEPARTMENT = 'department';
    public const TRANSFER = 'transfer';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'subject_label',
        'snapshot',
        'reason',
        'document_path',
        'document_name',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'executed_at',
        'account_user_id',
        'account_email',
        'account_cleared_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
        'account_cleared_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isEmployee(): bool
    {
        return $this->subject_type === self::EMPLOYEE;
    }

    public function isTransfer(): bool
    {
        return $this->subject_type === self::TRANSFER;
    }

    public function documentUrl(): ?string
    {
        return $this->document_path ? Storage::url($this->document_path) : null;
    }

    public function typeLabel(): string
    {
        return match ($this->subject_type) {
            self::EMPLOYEE => 'Nhân viên',
            self::DEPARTMENT => 'Phòng ban',
            self::TRANSFER => 'Chuyển phòng ban',
            default => $this->subject_type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => 'Chờ Giám đốc duyệt',
            self::APPROVED => match (true) {
                $this->isTransfer() => 'Đã duyệt',
                $this->isEmployee() => 'Đã nghỉ việc',
                default => 'Đã xóa',
            },
            self::REJECTED => 'Từ chối',
            default => $this->status,
        };
    }

    public function approveActionLabel(): string
    {
        return match (true) {
            $this->isTransfer() => 'Duyệt chuyển',
            $this->isEmployee() => 'Duyệt nghỉ việc',
            default => 'Duyệt xóa',
        };
    }

    public function transferHistory(): ?array
    {
        $history = data_get($this->snapshot, 'history');

        return is_array($history) ? $history : null;
    }

    public function feedbackEntries(): array
    {
        $raw = data_get($this->snapshot, 'feedback', []);
        if (! is_array($raw)) {
            return [];
        }

        $entries = [];
        foreach ($raw as $entry) {
            if (is_array($entry) && isset($entry['employee_id'])) {
                $entries[(int) $entry['employee_id']] = $entry;
            }
        }

        return $entries;
    }

    public function feedbackFor(int $employeeId): ?array
    {
        return $this->feedbackEntries()[$employeeId] ?? null;
    }

    public function pendingFeedbackCount(): int
    {
        return collect($this->feedbackEntries())
            ->filter(fn (array $row) => ($row['status'] ?? 'pending') === 'pending')
            ->count();
    }
}
