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
        'base_salary',
        'allowance',
        'probation_salary',
        'start_date',
        'end_date',
        'status',
        'company_representative',
        'signer',
        'notes',
        'pdf_file',
        'scan_file',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
