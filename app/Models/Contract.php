<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sign_date' => 'date',
        'employee_signed_at' => 'datetime',
        'director_signed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
