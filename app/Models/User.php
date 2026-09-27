<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'api_token', 'avatar', 'is_admin', 'is_super_admin', 'is_hr', 'is_accountant', 'is_director', 'is_locked'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_super_admin' => 'boolean',
        'is_hr' => 'boolean',
        'is_accountant' => 'boolean',
        'is_director' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function isDirector(): bool
    {
        return (bool) $this->is_director;
    }

    /** Quản trị hệ thống CNTT: tài khoản, phân quyền, cấu hình. */
    public function canAdministerSystem(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /** HR thao tác dữ liệu nhân sự. */
    public function canManageHr(): bool
    {
        return (bool) ($this->is_super_admin || $this->is_hr);
    }

    /** Phê duyệt cuối bảng lương / ký HĐ phía công ty. */
    public function canFinalApprovePayroll(): bool
    {
        return $this->isSuperAdmin() || $this->isDirector();
    }

    /** Tính lương và thanh toán. */
    public function canPayPayroll(): bool
    {
        return (bool) ($this->is_super_admin || $this->is_accountant);
    }

    public function canActAsDirector(): bool
    {
        return (bool) ($this->is_super_admin || $this->is_director);
    }

    public function hasRole(string $role): bool
    {
        if ($this->is_super_admin) {
            return in_array($role, ['admin', 'super_admin', 'hr', 'accountant', 'director'], true);
        }

        return match ($role) {
            'admin' => (bool) $this->is_admin,
            'super_admin' => (bool) $this->is_super_admin,
            'hr' => (bool) $this->is_hr,
            'accountant' => (bool) $this->is_accountant,
            'director' => (bool) $this->is_director,
            default => false,
        };
    }

    public function isStaffUser(): bool
    {
        return $this->is_admin || $this->is_super_admin || $this->is_director || $this->is_hr || $this->is_accountant;
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /** Hồ sơ nhân viên gắn user_id, hoặc khớp email nếu chưa liên kết user. */
    public function linkedEmployee(): ?Employee
    {
        return $this->employee ?: Employee::where('email', $this->email)->first();
    }

    public function positionHistories(): HasMany
    {
        return $this->hasMany(EmployeePositionHistory::class);
    }

    public function leaveRequestsApproved(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    public function attendancesApproved(): HasMany
    {
        return $this->hasMany(Attendance::class, 'approved_by');
    }
}
