<?php

namespace App\Models;

use App\Models\Benefit;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'benefit_id',
        'applied_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'applied_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(Benefit::class);
    }
}
