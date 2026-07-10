<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class EmployeeEvaluation extends Model
{
    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'month',
        'rating',
        'punctuality',
        'task_completion',
        'quality',
        'technical_skill',
        'responsibility',
        'teamwork',
        'attitude',
        'score_total',
        'classification',
        'status',
        'approved_by',
        'approved_at',
        'self_evaluation',
        'summary',
        'comments',
    ];

    protected $casts = [
        'rating' => 'integer',
        'punctuality' => 'integer',
        'task_completion' => 'integer',
        'quality' => 'integer',
        'technical_skill' => 'integer',
        'responsibility' => 'integer',
        'teamwork' => 'integer',
        'attitude' => 'integer',
        'score_total' => 'integer',
        'approved_at' => 'datetime',
        'self_evaluation' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
