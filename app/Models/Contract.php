<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_WAITING_EMPLOYEE_SIGNATURE = 'waiting_employee_signature';
    public const STATUS_WAITING_DIRECTOR_SIGNATURE = 'waiting_director_signature';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'employee_id',
        'title',
        'salary',
        'contract_code',
        'contract_type',
        'sign_date',
        'base_salary',
        'allowance',
        'bonus',
        'payment_method',
        'status',
        'contract_status',
        'terms',
        'additional_terms',
        'contract_content',
        'signer_id',
        'notes',
        'document_path',
        'document_name',
        'file_path',
        'start_date',
        'end_date',
        'created_by',
        'parent_contract_id',
        'employee_signed_at',
        'director_signed_at',
        'signed_employee_at',
        'signed_director_at',
        'contract_template_id',
        'workplace',
        'working_schedule',
        'benefits',
        'allowed_unpaid_leave_days_per_month',
        'allowed_makeup_attendance_per_month',
        'allowed_maternity_leave_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sign_date' => 'date',
        'employee_signed_at' => 'datetime',
        'director_signed_at' => 'datetime',
        'signed_employee_at' => 'datetime',
        'signed_director_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function parentContract()
    {
        return $this->belongsTo(self::class, 'parent_contract_id');
    }

    public function renewals()
    {
        return $this->hasMany(self::class, 'parent_contract_id');
    }

    public function logs()
    {
        return $this->hasMany(ContractLog::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->createdByUser();
    }
}
