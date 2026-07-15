<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    use HasFactory;

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
        'allowances', // json or nullable
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
}
