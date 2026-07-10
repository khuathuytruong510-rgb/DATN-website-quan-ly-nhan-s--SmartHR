<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'status',
        'message',
        'sent_at',
    ];
}