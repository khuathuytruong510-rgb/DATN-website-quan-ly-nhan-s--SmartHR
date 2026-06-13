<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'employee_id',
        'title',
        'salary',
        'start_date',
        'end_date',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
