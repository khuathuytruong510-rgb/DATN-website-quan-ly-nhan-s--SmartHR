<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Benefit;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'position',
        'department_id',
        'status',
        'avatar',
        'employee_code',
        'gender',
        'dob',
        'cccd',
        'phone',
        'address',
        'start_date',
        'education',
        'experience',
        'leave_balance',
    ];

    protected $casts = [
        'dob' => 'date',
        'start_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function faceProfile(): HasOne
    {
        return $this->hasOne(FaceProfile::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EmployeeEvaluation::class);
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(Benefit::class);
    }

    public function employeeBenefits(): HasMany
    {
        return $this->hasMany(EmployeeBenefit::class);
    }
}
