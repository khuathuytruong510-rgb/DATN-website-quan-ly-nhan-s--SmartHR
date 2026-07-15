<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'daily_salary',
        'working_salary',
        'working_days',
        'required_working_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'overtime_days',
        'overtime_hours',
        'overtime_day_salary',
        'overtime_hour_salary',
        'overtime_salary',
        'allowance',
        'bonus',
        'deduction',
        'insurance',
        'tax',
        'total_salary',
        'status',
        'paid_at',
        'sent_at',
        'sent_by',
        'email_status',
        'confirmed_at',
        'confirmation_status',
        'confirmation_deadline',
        'issue_report',
        'issue_reported_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'confirmation_deadline' => 'datetime',
        'issue_reported_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if payroll is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    /**
     * Get the total salary with all calculations
     */
    public function getTotalWithCalculationsAttribute(): int|float
    {
        $totalIncome = ($this->working_salary ?? 0)
            + ($this->overtime_salary ?? 0)
            + ($this->allowance ?? 0)
            + ($this->bonus ?? 0);

        return round(
            $totalIncome
            - ($this->insurance ?? 0)
            - ($this->tax ?? 0)
            - ($this->deduction ?? 0),
            2
        );
    }

    /**
     * Return the year part of the `month` field (YYYY-MM format)
     */
    public function getYearAttribute($value)
    {
        if (! empty($value)) {
            return $value;
        }

        if (empty($this->month) || ! str_contains($this->month, '-')) {
            return null;
        }

        return explode('-', $this->month)[0];
    }

    public function getDisplayMonthAttribute(): string
    {
        if (empty($this->month)) {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            return Carbon::createFromFormat('Y-m', $this->month)->format('n/Y');
        }

        if (preg_match('/^\d{1,2}$/', $this->month)) {
            $year = $this->year ?? $this->created_at?->format('Y') ?? now()->year;
            return sprintf('%d/%s', (int) $this->month, $year);
        }

        return (string) $this->month;
    }
    public function getGrossSalaryAttribute(): float
    {
        return round(
            ($this->working_salary ?? 0)
            + ($this->overtime_salary ?? 0)
            + ($this->allowance ?? 0)
            + ($this->bonus ?? 0),
            2
        );
    }

    /**
     * Get net salary after insurance and tax.
     */
    public function getNetSalaryAttribute(): float
    {
        return round($this->total_salary ?? 0, 2);
    }
}
