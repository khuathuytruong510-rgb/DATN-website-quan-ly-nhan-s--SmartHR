<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Benefit;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use App\Models\Position;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'position_id',
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

    public function positionDetail(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Generate employee code based on department and sequence number
     * Format: DEPT-001 (e.g., HR-001, IT-002, etc.)
     */
    public static function generateEmployeeCode(Department $department): string
    {
        $deptCode = strtoupper(substr($department->name, 0, 3));
        $count = Employee::where('department_id', $department->id)->count();
        $nextSequence = $count + 1;
        
        return $deptCode . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique employee code with conflict resolution
     */
    public static function generateUniqueEmployeeCode(Department $department): string
    {
        $code = self::generateEmployeeCode($department);
        $counter = 1;
        
        while (Employee::where('employee_code', $code)->exists()) {
            $deptCode = strtoupper(substr($department->name, 0, 3));
            $code = $deptCode . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }
        
        return $code;
    }
}
