<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const RESOLVED = 'resolved';

    protected $fillable = [
        'employee_id', 'subject', 'message', 'type', 'status', 'attachment', 'hr_reply', 'follow_up', 'employee_feedback',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PROCESSING => 'Đã duyệt',
            self::RESOLVED => 'Đã xử lý',
            default => 'Chờ duyệt',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::PROCESSING;
    }

    public function isResolved(): bool
    {
        return $this->status === self::RESOLVED;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'attendance' => 'Lỗi chấm công',
            'payroll' => 'Lỗi bảng lương',
            'document' => 'Yêu cầu giấy tờ',
            'personnel' => 'Thông tin nhân sự',
            default => 'Khác',
        };
    }
}
