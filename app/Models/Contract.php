<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_WAITING_EMPLOYEE_SIGNATURE = 'waiting_employee_signature';
    public const STATUS_WAITING_DIRECTOR_SIGNATURE = 'waiting_director_signature';
    public const STATUS_PENDING_SIGNATURE = 'pending_signature';
    public const STATUS_DIRECTOR_SIGNED = 'director_signed';
    public const STATUS_EMPLOYEE_SIGNED = 'employee_signed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TERMINATED = 'terminated';

    public const ALERT_OK = 'ok';
    public const ALERT_EXPIRING = 'expiring';
    public const ALERT_URGENT = 'urgent';
    public const ALERT_EXPIRED = 'expired';
    public const ALERT_OVERDUE = 'overdue';

    protected $fillable = [
        'employee_id',
        'title',
        'salary',
        'contract_code',
        'contract_type',
        'sign_date',
        'base_salary',
        'allowance',
        'bonus',
        'payment_method',
        'status',
        'contract_status',
        'terms',
        'additional_terms',
        'contract_content',
        'signer_id',
        'notes',
        'document_path',
        'document_name',
        'file_path',
        'start_date',
        'end_date',
        'created_by',
        'parent_contract_id',
        'employee_signed_at',
        'director_signed_at',
        'signed_employee_at',
        'signed_director_at',
        'content_locked_at',
        'canonical_document_path',
        'document_hash',
        'contract_template_id',
        'workplace',
        'working_schedule',
        'benefits',
        'allowed_unpaid_leave_days_per_month',
        'allowed_makeup_attendance_per_month',
        'allowed_maternity_leave_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sign_date' => 'date',
        'employee_signed_at' => 'datetime',
        'director_signed_at' => 'datetime',
        'signed_employee_at' => 'datetime',
        'signed_director_at' => 'datetime',
        'content_locked_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function parentContract()
    {
        return $this->belongsTo(self::class, 'parent_contract_id');
    }

    public function renewals()
    {
        return $this->hasMany(self::class, 'parent_contract_id');
    }

    public function logs()
    {
        return $this->hasMany(ContractLog::class);
    }

    public function signatures()
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function directorSignature()
    {
        return $this->hasOne(ContractSignature::class)->ofMany(
            ['id' => 'max'],
            function ($query) {
                $query->where('signer_role', ContractSignature::ROLE_DIRECTOR)
                    ->where('status', ContractSignature::STATUS_SIGNED);
            }
        );
    }

    public function employeeSignature()
    {
        return $this->hasOne(ContractSignature::class)->ofMany(
            ['id' => 'max'],
            function ($query) {
                $query->where('signer_role', ContractSignature::ROLE_EMPLOYEE)
                    ->where('status', ContractSignature::STATUS_SIGNED);
            }
        );
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->createdByUser();
    }

    public function expiryAlerts(): HasMany
    {
        return $this->hasMany(ContractExpiryAlert::class);
    }

    public function expiryActions(): HasMany
    {
        return $this->hasMany(ContractExpiryAction::class);
    }

    public function latestExpiryAction(): HasOne
    {
        return $this->hasOne(ContractExpiryAction::class)->latestOfMany();
    }

    public function isFullySigned(): bool
    {
        return $this->employee_signed_at !== null && $this->director_signed_at !== null;
    }

    public function isContentLocked(): bool
    {
        if ($this->content_locked_at || $this->director_signed_at || $this->isFullySigned()) {
            return true;
        }

        return in_array($this->status, [
            self::STATUS_PENDING_SIGNATURE,
            self::STATUS_DIRECTOR_SIGNED,
            self::STATUS_EMPLOYEE_SIGNED,
            self::STATUS_SIGNED,
            self::STATUS_ACTIVE,
            self::STATUS_EXPIRED,
            self::STATUS_TERMINATED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function isAwaitingHrSend(): bool
    {
        return ! $this->director_signed_at
            && ! $this->content_locked_at
            && in_array($this->status, [
                self::STATUS_DRAFT,
                self::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                'waiting_employee',
            ], true);
    }

    public function isPendingDirectorEsign(): bool
    {
        return ! $this->director_signed_at
            && in_array($this->status, [
                self::STATUS_PENDING_SIGNATURE,
                self::STATUS_WAITING_DIRECTOR_SIGNATURE,
                'waiting_director',
            ], true);
    }

    public function isPendingEmployeeEsign(): bool
    {
        return $this->director_signed_at
            && ! $this->employee_signed_at
            && in_array($this->status, [
                self::STATUS_DIRECTOR_SIGNED,
                self::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                'waiting_employee',
            ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp — HR đang soạn',
            self::STATUS_PENDING_SIGNATURE, self::STATUS_WAITING_DIRECTOR_SIGNATURE, 'waiting_director' => 'Chờ Giám đốc ký',
            self::STATUS_DIRECTOR_SIGNED => 'Giám đốc đã ký — chờ nhân viên',
            self::STATUS_WAITING_EMPLOYEE_SIGNATURE, 'waiting_employee' => 'Chờ nhân viên ký',
            self::STATUS_EMPLOYEE_SIGNED => 'Nhân viên đã ký — đủ hai bên',
            self::STATUS_SIGNED => 'Đã ký — chờ hiệu lực',
            self::STATUS_ACTIVE => 'Có hiệu lực',
            self::STATUS_EXPIRED => 'Hết hạn',
            self::STATUS_REJECTED => 'Giám đốc từ chối',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_TERMINATED => 'Đã chấm dứt',
            default => $this->status ?? '—',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_TERMINATED,
            self::STATUS_REJECTED,
        ], true);
    }

    /**
     * Số ngày còn lại đến end_date. Dương = chưa hết hạn, 0 = hết hạn hôm nay, âm = đã quá hạn.
     * Hợp đồng không xác định thời hạn (không có end_date) trả về null.
     */
    public function daysUntilExpiry(?CarbonInterface $today = null): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        $today = Carbon::parse($today ?? now())->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        return (int) round($today->diffInDays($end, false));
    }

    /**
     * Mức cảnh báo tính từ end_date — không lưu vào database.
     */
    public function alertLevel(?CarbonInterface $today = null): string
    {
        if ($this->isClosed() || ! $this->isFullySigned()) {
            return self::ALERT_OK;
        }

        $days = $this->daysUntilExpiry($today);
        if ($days === null) {
            return self::ALERT_OK;
        }

        $notice = (int) config('contracts.notice_days', 30);
        $urgent = (int) config('contracts.urgent_days', 7);

        if ($days < 0) {
            return self::ALERT_OVERDUE;
        }
        if ($days === 0) {
            return self::ALERT_EXPIRED;
        }
        if ($days <= $urgent) {
            return self::ALERT_URGENT;
        }
        if ($days <= $notice) {
            return self::ALERT_EXPIRING;
        }

        return self::ALERT_OK;
    }

    public function alertLabel(?CarbonInterface $today = null): string
    {
        $days = $this->daysUntilExpiry($today);

        return match ($this->alertLevel($today)) {
            self::ALERT_EXPIRING => 'Sắp hết hạn'.($days !== null ? ' (còn '.$days.' ngày)' : ''),
            self::ALERT_URGENT => 'Sắp hết hạn khẩn cấp'.($days !== null ? ' (còn '.$days.' ngày)' : ''),
            self::ALERT_EXPIRED => 'Đã hết hạn',
            self::ALERT_OVERDUE => 'Đã quá hạn'.($days !== null ? ' '.abs($days).' ngày' : ''),
            default => 'Đang hiệu lực',
        };
    }

    public function hasSuccessorContract(): bool
    {
        if ($this->relationLoaded('renewals')) {
            return $this->renewals->contains(fn (self $row) => ! $row->isClosed());
        }

        return $this->renewals()
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_TERMINATED, self::STATUS_REJECTED])
            ->exists();
    }

    public function needsExpiryHandling(?CarbonInterface $today = null): bool
    {
        if ($this->isClosed() || ! $this->isFullySigned() || $this->daysUntilExpiry($today) === null) {
            return false;
        }

        if ($this->hasSuccessorContract()) {
            return false;
        }

        return $this->alertLevel($today) !== self::ALERT_OK;
    }
}
