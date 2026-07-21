<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'payroll_id', 'code', 'month', 'year', 'total', 'deductions', 'net',
        'payment_method', 'bank', 'account_holder', 'account_number', 'transaction_code',
        'cash_payer', 'notes', 'status', 'paid_by', 'paid_at',
        'batch_id', 'reconciliation_status', 'reconciliation_notes',
        'reconciled_at', 'reconciled_by', 'qr_code', 'qr_reference',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SalaryPaymentBatch::class, 'batch_id');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SalaryPaymentLog::class, 'salary_payment_id');
    }
}
