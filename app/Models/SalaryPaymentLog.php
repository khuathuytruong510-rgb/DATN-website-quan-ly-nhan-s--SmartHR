<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_payment_id','user_id','action','ip','device','notes'
    ];

    public function payment()
    {
        return $this->belongsTo(SalaryPayment::class, 'salary_payment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
