<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_location',
        'check_in_ip_address',
        'check_in_distance',
        'check_in_notes',
        'check_in_location_missing',
        'check_out',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_location',
        'check_out_ip_address',
        'check_out_distance',
        'check_out_notes',
        'check_out_location_missing',
        'work_hours',
        'late_minutes',
        'late_penalty_fee',
        'early_leave_minutes',
        'overtime_hours',
        'status',
        'notes',
        'attendance_method',
        'attendance_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'check_in_latitude' => 'float',
        'check_in_longitude' => 'float',
        'check_out_latitude' => 'float',
        'check_out_longitude' => 'float',
        'check_in_distance' => 'float',
        'check_out_distance' => 'float',
        'work_hours' => 'float',
        'late_minutes' => 'integer',
        'late_penalty_fee' => 'float',
        'early_leave_minutes' => 'integer',
        'overtime_hours' => 'float',
    ];

    public function getCheckInAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        $date = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$value}");
    }

    public function getCheckOutAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        $date = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$value}");
    }

    public function setCheckInAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['check_in'] = $value->format('H:i:s');
            return;
        }

        $this->attributes['check_in'] = $value;
    }

    public function setCheckOutAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['check_out'] = $value->format('H:i:s');
            return;
        }

        $this->attributes['check_out'] = $value;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function adjustmentRequests()
    {
        return $this->hasMany(AttendanceAdjustmentRequest::class);
    }

    /**
     * Khóa bản ghi chấm công trong ngày để tránh double check-in.
     */
    public static function lockForEmployeeDate(int $employeeId, $date): self
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : (string) $date;

        $row = static::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $dateString)
            ->lockForUpdate()
            ->first();

        if ($row) {
            return $row;
        }

        try {
            $created = static::create([
                'employee_id' => $employeeId,
                'date' => $dateString,
                'status' => 'absent',
            ]);

            return static::query()->whereKey($created->id)->lockForUpdate()->firstOrFail();
        } catch (QueryException) {
            return static::query()
                ->where('employee_id', $employeeId)
                ->whereDate('date', $dateString)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the status label in Vietnamese
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->attendance_method) {
            'face' => 'Khuôn mặt',
            'gps' => 'GPS',
            default => 'Thủ công',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'present' => 'Đi làm',
            'late' => 'Đi muộn',
            'leave_early' => 'Về sớm',
            'late_and_leave_early' => 'Đi muộn & Về sớm',
            'overtime' => 'Tăng ca',
            'absent' => 'Vắng mặt',
            default => $this->status,
        };
    }

    /**
     * Get formatted work hours with decimal separator
     */
    public function getFormattedWorkHoursAttribute(): string
    {
        return number_format($this->work_hours ?? 0, 2, '.', '');
    }

    /**
     * Get formatted overtime hours with decimal separator
     */
    public function getFormattedOvertimeHoursAttribute(): string
    {
        return number_format($this->overtime_hours ?? 0, 2, '.', '');
    }

    /**
     * Get check-in time formatted
     */
    public function getFormattedCheckInAttribute(): ?string
    {
        return $this->check_in?->format('H:i:s');
    }

    /**
     * Get check-out time formatted
     */
    public function getFormattedCheckOutAttribute(): ?string
    {
        return $this->check_out?->format('H:i:s');
    }

    /**
     * Get formatted late penalty fee with Vietnamese currency
     */
    public function getFormattedLatePenaltyFeeAttribute(): string
    {
        return number_format($this->late_penalty_fee ?? 0, 0, '.', ',') . ' ₫';
    }

    /**
     * Get late penalty fee label in Vietnamese
     */
    public function getLatePenaltyLabelAttribute(): string
    {
        $fee = $this->late_penalty_fee ?? 0;

        if ($fee <= 0) {
            return 'Không phạt';
        }

        return number_format($fee, 0, '.', ',') . ' ₫';
    }
}
