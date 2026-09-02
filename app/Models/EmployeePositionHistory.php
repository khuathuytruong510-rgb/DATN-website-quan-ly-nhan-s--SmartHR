<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePositionHistory extends Model
{
    public const STATUS_HOLDING = 'holding';
    public const STATUS_ENDED = 'ended';

    public const REASON_SUCCESSION = 'director_succession';
    public const REASON_APPOINTMENT = 'director_appointment';
    public const REASON_TRANSFER = 'department_transfer';

    protected $fillable = [
        'employee_id',
        'user_id',
        'holder_name',
        'holder_email',
        'position_id',
        'position_name',
        'department_id',
        'department_name',
        'started_at',
        'ended_at',
        'end_reason',
        'is_director_role',
        'status',
        'decision_ref',
        'note',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'is_director_role' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isHolding(): bool
    {
        return $this->status === self::STATUS_HOLDING && $this->ended_at === null;
    }

    public function tenureLabel(): string
    {
        $start = optional($this->started_at)->format('d/m/Y') ?: '—';
        if ($this->ended_at) {
            return $start.' → '.$this->ended_at->format('d/m/Y');
        }

        return $start.' → đang giữ chức';
    }

    public function statusLabel(): string
    {
        return $this->isHolding()
            ? 'Đang giữ chức vụ'
            : 'Đã thôi chức / Không còn giữ chức vụ';
    }
}
