<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [

    'employee_id',

    'date',

    'check_in',

    'check_out',

    'work_hours',

    'late_minutes',

    'early_leave_minutes',

    'overtime_hours',

    'status',

    'notes',

];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}