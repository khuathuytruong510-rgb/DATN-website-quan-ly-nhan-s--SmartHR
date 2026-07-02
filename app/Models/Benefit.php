<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Benefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'created_by',
        'code',
        'title',
        'description',
        'type',
        'amount',
        'unit',
        'applies_to',
        'condition',
        'effective_date',
        'expiry_date',
        'application_status',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
