<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    use HasFactory;

    public const CHANGE_PAYMENT = 'Thanh toán lương';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'code',
        'period',
        'effective_date',
        'change_type',
        'old_salary',
        'new_salary',
        'position',
        'department_id',
        'allowances',
        'rewards',
        'deductions',
        'tax',
        'insurance',
        'notes',
        'document_number',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'allowances' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Lưu / cập nhật lịch sử lương từ phiếu đã thanh toán (idempotent theo payroll_id).
     */
    public static function recordFromPaidPayroll(Payroll $payroll, ?User $actor = null): self
    {
        $payroll->loadMissing(['employee', 'salaryPayment']);

        $employee = $payroll->employee;
        $payment = $payroll->salaryPayment;
        $period = sprintf('%02d/%d', (int) $payroll->month, (int) $payroll->year);
        $methodLabel = match ($payroll->payment_method) {
            'cash' => 'Tiền mặt',
            'bank_transfer', 'qr' => 'Chuyển khoản',
            default => $payroll->payment_method ?: '—',
        };

        $notes = trim(implode("\n", array_filter([
            "Thanh toán lương kỳ {$period}.",
            "Phương thức: {$methodLabel}.",
            $payment?->transaction_code ? 'Mã GD: '.$payment->transaction_code : null,
            $payment?->notes,
        ])));

        return static::updateOrCreate(
            ['payroll_id' => $payroll->id],
            [
                'employee_id' => $payroll->employee_id,
                'code' => 'SH-'.$payroll->year.sprintf('%02d', $payroll->month).'-'.$payroll->id,
                'period' => $period,
                'effective_date' => optional($payroll->paid_at)?->toDateString() ?? now()->toDateString(),
                'change_type' => self::CHANGE_PAYMENT,
                'old_salary' => (float) ($payroll->base_salary ?? 0),
                'new_salary' => (float) ($payroll->total_salary ?? 0),
                'position' => $employee?->position,
                'department_id' => $employee?->department_id,
                'allowances' => [
                    'other' => (float) ($payroll->allowance ?? 0),
                    'overtime' => (float) ($payroll->overtime_salary ?? 0),
                ],
                'rewards' => (float) ($payroll->bonus ?? 0),
                'deductions' => (float) ($payroll->deduction ?? 0),
                'tax' => (float) ($payroll->tax ?? 0),
                'insurance' => (float) ($payroll->insurance ?? 0),
                'notes' => $notes,
                'document_number' => $payment?->transaction_code
                    ?? $payment?->code
                    ?? ('PAY-'.$payroll->id),
                'status' => self::STATUS_APPLIED,
                'updated_by' => $actor?->id ?? $payroll->paid_by,
            ]
        );
    }
}
