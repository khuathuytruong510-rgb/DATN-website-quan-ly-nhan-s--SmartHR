<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Benefit;
use App\Models\Contract;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use App\Models\Position;
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
        'terminated_at',
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
        'bank_name',
        'account_number',
        'account_holder',
        'qr_image',
        'address_detail',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_PENDING_TERMINATION = 'pending_termination';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_INACTIVE = 'inactive';

    protected $casts = [
        'dob' => 'date',
        'start_date' => 'date',
        'terminated_at' => 'date',
    ];

    public static function workingStatuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_ON_LEAVE, self::STATUS_PENDING_TERMINATION];
    }

    public static function terminatedStatuses(): array
    {
        return [self::STATUS_TERMINATED, self::STATUS_INACTIVE];
    }

    public function isTerminated(): bool
    {
        return in_array($this->status, self::terminatedStatuses(), true);
    }

    public function isPendingTermination(): bool
    {
        return $this->status === self::STATUS_PENDING_TERMINATION;
    }

    public function isAwaitingContract(): bool
    {
        return $this->status === self::STATUS_PENDING
            || ($this->status === self::STATUS_ACTIVE && ! $this->hasEffectiveContract());
    }

    public function hasEffectiveContract(): bool
    {
        if ($this->relationLoaded('contracts')) {
            return $this->contracts->contains(fn (Contract $c) => $c->status === Contract::STATUS_ACTIVE);
        }

        return $this->contracts()->where('status', Contract::STATUS_ACTIVE)->exists();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ hợp đồng',
            self::STATUS_ACTIVE => $this->hasEffectiveContract() ? 'Còn làm việc' : 'Chờ hợp đồng',
            self::STATUS_ON_LEAVE => 'Tạm nghỉ',
            self::STATUS_PENDING_TERMINATION => 'Chờ nghỉ việc',
            self::STATUS_TERMINATED, self::STATUS_INACTIVE => 'Đã nghỉ việc',
            default => $this->status ?: '—',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Hồ sơ module Nhân viên: loại Giám đốc (vai trò/chức vụ), vẫn gồm Trợ lý & Thư ký Ban Giám đốc.
     */
    public function scopeWithoutBoardAndDirector($query)
    {
        $directorEmails = User::query()
            ->where('is_director', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();

        return $query
            ->where(function ($q) {
                $q->whereNull('position')
                    ->orWhereRaw('LOWER(TRIM(position)) <> ?', ['giám đốc']);
            })
            ->whereDoesntHave('positionDetail', function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['giám đốc']);
            })
            ->whereDoesntHave('user', fn ($q) => $q->where('is_director', true))
            ->when($directorEmails !== [], function ($q) use ($directorEmails) {
                $q->where(function ($inner) use ($directorEmails) {
                    $inner->whereNull('email')
                        ->orWhereRaw('LOWER(email) not in ('.implode(',', array_fill(0, count($directorEmails), '?')).')', $directorEmails);
                });
            });
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

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract(): ?Contract
    {
        return $this->contracts()
            ->where('status', Contract::STATUS_ACTIVE)
            ->latest('start_date')
            ->first();
    }

    public function isFemale(): bool
    {
        $gender = mb_strtolower(trim((string) $this->gender));

        return in_array($gender, ['female', 'nu', 'nữ'], true);
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

    public function positionHistories(): HasMany
    {
        return $this->hasMany(EmployeePositionHistory::class);
    }

    public function directorTenures(): HasMany
    {
        return $this->positionHistories()->where('is_director_role', true);
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class, 'employee_id');
    }

    /**
     * Alias tương thích Payment Center (main) với cột account_* của workflow lương.
     */
    public function getBankAccountNumberAttribute($value = null): ?string
    {
        if (filled($value)) {
            return $value;
        }

        return $this->attributes['account_number'] ?? null;
    }

    public function getBankAccountHolderAttribute($value = null): ?string
    {
        if (filled($value)) {
            return $value;
        }

        return $this->attributes['account_holder'] ?? null;
    }

    public function setBankAccountNumberAttribute($value): void
    {
        $this->attributes['account_number'] = $value;
    }

    public function setBankAccountHolderAttribute($value): void
    {
        $this->attributes['account_holder'] = $value;
    }

    /**
     * Generate employee code based on department and sequence number
     * Format: DEPT-001 (e.g., HR-001, IT-002, etc.)
     */
    public static function generateEmployeeCode(Department $department): string
    {
        $deptCode = self::departmentCodePrefix($department);
        $count = Employee::where('department_id', $department->id)->count();
        $nextSequence = $count + 1;

        return $deptCode.'-'.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique employee code with conflict resolution
     */
    public static function generateUniqueEmployeeCode(Department $department): string
    {
        $code = self::generateEmployeeCode($department);
        $counter = 1;
        $deptCode = self::departmentCodePrefix($department);

        while (Employee::where('employee_code', $code)->exists()) {
            $counter++;
            $code = $deptCode.'-'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    protected static function departmentCodePrefix(Department $department): string
    {
        $raw = trim((string) ($department->code ?: ''));
        if ($raw === '') {
            $raw = (string) mb_substr((string) $department->name, 0, 3, 'UTF-8');
        }

        $ascii = preg_replace('/[^A-Za-z0-9]/', '', $raw) ?: 'NV';

        return strtoupper(substr($ascii, 0, 6));
    }
}
