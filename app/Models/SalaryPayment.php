<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id','payroll_id','code','month','year','total','deductions','net',
        'payment_method','bank','account_holder','account_number','transaction_code',
        'cash_payer','notes','status','paid_by','paid_at'
    ];

    protected $dates = ['paid_at'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function logs()
    {
        return $this->hasMany(SalaryPaymentLog::class, 'salary_payment_id');
    }
}
