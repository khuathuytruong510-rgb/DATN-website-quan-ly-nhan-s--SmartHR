<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'working_days',
        'daily_rate',
        'base_salary',
        'allowance',
        'total_salary',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}