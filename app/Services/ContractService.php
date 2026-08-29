<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Support\ContractFixedTerms;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ContractService
{
    public function createContract(User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $data): Contract {
            $employee = Employee::findOrFail($data['employee_id']);
            $contract = Contract::create($this->buildPayload($actor, $employee, $data, null));
            $this->syncStatus($contract);
            $this->log($contract, $actor, 'created', 'Tạo hợp đồng', ['contract_code' => $contract->contract_code]);

            return $contract->fresh();
        });
    }

    public function updateContract(User $actor, Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $contract, $data): Contract {
            $employee = $contract->employee ?? Employee::find($contract->employee_id);
            $payload = $this->buildPayload($actor, $employee, $data, $contract);

            $contract->fill($payload);
            $contract->save();
            $this->syncStatus($contract);
            $this->log($contract, $actor, 'updated', 'Cập nhật hợp đồng', ['contract_code' => $contract->contract_code]);

            return $contract->fresh();
        });
    }

    public function renewContract(User $actor, Contract $parentContract, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $parentContract, $data): Contract {
            $employee = $parentContract->employee ?? Employee::find($parentContract->employee_id);

            // Ưu tiên lương từ bảng lương mới nhất nếu không có trong $data
            if (empty($data['base_salary']) || (float) $data['base_salary'] === 0.0) {
                $latestPayroll = $employee
                    ? $employee->payrolls()->orderByDesc('year')->orderByDesc('month')->first()
                    : null;
                if ($latestPayroll && $latestPayroll->base_salary > 0) {
                    $data['base_salary'] = $latestPayroll->base_salary;
                    if (empty($data['allowance'])) {
                        $data['allowance'] = $latestPayroll->allowance ?? $parentContract->allowance ?? 0;
                    }
                } else {
                    // Kế thừa lương hợp đồng cũ
                    $data['base_salary'] = $parentContract->base_salary ?? $parentContract->salary ?? 0;
                    if (empty($data['allowance'])) {
                        $data['allowance'] = $parentContract->allowance ?? 0;
                    }
                }
            }

            // Luôn gán employee_id và parent_contract_id từ hợp đồng gốc
            $data['employee_id']        = $parentContract->employee_id;
            $data['parent_contract_id'] = $parentContract->id;

            $contract = Contract::create($this->buildPayload($actor, $employee, $data, null));

            // Đảm bảo parent_contract_id được lưu (buildPayload đã nhận, nhưng ghi lại cho chắc)
            if ($contract->parent_contract_id !== $parentContract->id) {
                $contract->parent_contract_id = $parentContract->id;
                $contract->save();
            }

            $this->syncStatus($contract);

            $this->log($contract, $actor, 'renewed', 'Gia hạn hợp đồng', [
                'parent_contract_id' => $parentContract->id,
                'parent_code'        => $parentContract->contract_code,
                'new_base_salary'    => (float) $contract->base_salary,
                'new_allowance'      => (float) $contract->allowance,
                'new_start_date'     => $contract->start_date?->toDateString(),
                'new_end_date'       => $contract->end_date?->toDateString(),
            ]);

            return $contract->fresh();
        });
    }

    public function signContract(User $actor, Contract $contract, string $party): Contract
    {
        return DB::transaction(function () use ($actor, $contract, $party): Contract {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            $contract->loadMissing('employee');

            $blocked = [
                Contract::STATUS_CANCELLED,
                Contract::STATUS_TERMINATED,
                Contract::STATUS_EXPIRED,
                Contract::STATUS_REJECTED,
            ];
            if (in_array($contract->status, $blocked, true)) {
                throw new \RuntimeException('Không thể ký hợp đồng đã hết hạn, bị hủy hoặc đã chấm dứt. Hãy tạo hợp đồng/gia hạn mới.');
            }

            if ($party === 'employee') {
                $allowed = [Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE, 'waiting_employee', Contract::STATUS_DRAFT];
                if (! in_array($contract->status, $allowed, true) && $contract->employee_signed_at) {
                    throw new \RuntimeException('Nhân viên đã ký hợp đồng này.');
                }
                if (! in_array($contract->status, $allowed, true)) {
                    throw new \RuntimeException('Nhân viên chỉ ký khi hợp đồng đang chờ chữ ký nhân viên.');
                }
                $employee = $contract->employee;
                $owns = $employee && (
                    ((int) $employee->user_id === (int) $actor->id)
                    || strcasecmp((string) $employee->email, (string) $actor->email) === 0
                );
                if (! $owns) {
                    throw new \RuntimeException('Chỉ nhân viên của hợp đồng này được ký ở bước nhân viên. HR không ký thay.');
                }
                if ($contract->employee_signed_at) {
                    throw new \RuntimeException('Nhân viên đã ký hợp đồng này. Không ký lại.');
                }
                $contract->employee_signed_at = now();
            } elseif ($party === 'director') {
                if (! $actor->is_director) {
                    throw new \RuntimeException('Chỉ Giám đốc được ký hợp đồng phía công ty.');
                }
                if (! $contract->employee_signed_at) {
                    throw new \RuntimeException('Giám đốc chỉ ký sau khi nhân viên đã ký.');
                }
                if ($contract->director_signed_at) {
                    throw new \RuntimeException('Giám đốc đã ký hợp đồng này. Không ký lại.');
                }
                $waitingDirector = [Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, 'waiting_director'];
                if (! in_array($contract->status, $waitingDirector, true)) {
                    throw new \RuntimeException('Giám đốc chỉ ký khi hợp đồng đang chờ chữ ký giám đốc.');
                }
                $contract->director_signed_at = now();
            } else {
                throw new \InvalidArgumentException('Loại chữ ký không hợp lệ.');
            }

            $contract->save();
            $this->syncStatus($contract);
            $this->log($contract, $actor, $party === 'employee' ? 'employee_signed' : 'director_signed', 'Ký hợp đồng', ['party' => $party]);

            return $contract->fresh();
        });
    }

    public function syncStatus(Contract $contract): Contract
    {
        if ($contract->status === Contract::STATUS_CANCELLED) {
            return $contract;
        }

        $nextStatus = Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE;
        if ($contract->employee_signed_at && $contract->director_signed_at) {
            $nextStatus = $this->resolveDateBasedStatus($contract->start_date, $contract->end_date);
        } elseif ($contract->employee_signed_at) {
            $nextStatus = Contract::STATUS_WAITING_DIRECTOR_SIGNATURE;
        }

        $contract->status = $nextStatus;
        $contract->contract_status = $nextStatus;
        $contract->save();

        return $contract->fresh();
    }

    public function getSalaryForEmployee(Employee $employee): int
    {
        if (! $employee) {
            return 0;
        }

        if ($employee->relationLoaded('positionDetail') || $employee->position_id) {
            $positionDetail = $employee->positionDetail;
            if ($positionDetail && ! empty($positionDetail->base_salary)) {
                return (int) $positionDetail->base_salary;
            }

            if ($positionDetail && ! empty($positionDetail->salary_range_min)) {
                return (int) $positionDetail->salary_range_min;
            }
        }

        $positionName = trim((string) $employee->position);
        if ($positionName !== '' && Schema::hasTable('positions')) {
            $position = Position::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($positionName)])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($positionName) . '%'])
                ->first();

            if ($position && ! empty($position->base_salary)) {
                return (int) $position->base_salary;
            }

            if ($position && ! empty($position->salary_range_min)) {
                return (int) $position->salary_range_min;
            }
        }

        return match (mb_strtolower($positionName)) {
            'giám đốc', 'director', 'ceo' => 30000000,
            'backend developer', 'developer', 'lập trình viên backend' => 18000000,
            'frontend developer', 'lập trình viên frontend' => 16000000,
            'trưởng phòng nhân sự', 'hr manager', 'hr manager' => 20000000,
            default => 12000000,
        };
    }

    public function resolveContractCode(?string $contractCode = null, ?Contract $contract = null): string
    {
        if (! empty($contractCode)) {
            return Str::upper($contractCode);
        }

        if ($contract && $contract->contract_code) {
            return $contract->contract_code;
        }

        $nextId = (int) (Contract::max('id') ?? 0) + 1;

        return 'HD-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    protected function buildPayload(User $actor, ?Employee $employee, array $data, ?Contract $contract): array
    {
        $salary = (float) ($data['base_salary'] ?? $contract?->base_salary ?? ($employee ? $this->getSalaryForEmployee($employee) : 0));
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        $contractType = $data['contract_type'] ?? $contract?->contract_type ?? null;
        $shouldUseTemplateContent = false;
        $template = null;

        if (! empty($data['contract_template_id'])) {
            $template = ContractTemplate::find($data['contract_template_id']);
            $shouldUseTemplateContent = true;
        } elseif ($contractType) {
            $template = ContractTemplate::query()
                ->where('status', 'active')
                ->where('contract_type', $contractType)
                ->where('is_default', true)
                ->first();

            if ($template) {
                $shouldUseTemplateContent = true;
            }
        }

        $fixedTerms = ContractFixedTerms::forType($contractType);
        $contractContent = $data['contract_content'] ?? ($shouldUseTemplateContent ? ($template?->content ?? $fixedTerms) : ($contract?->contract_content ?? $contract?->terms ?? $fixedTerms));
        $payload = [
            'employee_id' => $data['employee_id'] ?? $contract?->employee_id,
            'title' => $data['title'] ?? $contract?->title ?? $this->resolveContractTitle($data['contract_type'] ?? $contract?->contract_type),
            'contract_code' => $this->resolveContractCode($data['contract_code'] ?? null, $contract),
            'contract_type' => $data['contract_type'] ?? $contract?->contract_type ?? 'official',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $data['notes'] ?? $contract?->notes,
            'salary' => $salary,
            'base_salary' => (float) $salary,
            'allowance' => (float) ($data['allowance'] ?? $contract?->allowance ?? 0),
            'bonus' => (float) ($data['bonus'] ?? $contract?->bonus ?? 0),
            'payment_method' => $data['payment_method'] ?? $contract?->payment_method,
            'terms' => $shouldUseTemplateContent ? ($template?->content ?? $data['terms'] ?? $contract?->terms ?? $fixedTerms) : ($data['terms'] ?? $contract?->terms ?? $fixedTerms),
            'additional_terms' => $data['additional_terms'] ?? $contract?->additional_terms,
            'contract_content' => $contractContent,
            'created_by' => $actor->id,
            'parent_contract_id' => $data['parent_contract_id'] ?? $contract?->parent_contract_id,
            'employee_signed_at' => $contract?->employee_signed_at,
            'director_signed_at' => $contract?->director_signed_at,
            'contract_template_id' => $template?->id ?? $data['contract_template_id'] ?? $contract?->contract_template_id,
            'workplace' => $data['workplace'] ?? $contract?->workplace,
            'working_schedule' => $data['working_schedule'] ?? $contract?->working_schedule,
            'benefits' => $data['benefits'] ?? $contract?->benefits,
            'allowed_unpaid_leave_days_per_month' => (int) ($data['allowed_unpaid_leave_days_per_month'] ?? $contract?->allowed_unpaid_leave_days_per_month ?? 1),
            'allowed_makeup_attendance_per_month' => (int) ($data['allowed_makeup_attendance_per_month'] ?? $contract?->allowed_makeup_attendance_per_month ?? 3),
            'allowed_maternity_leave_days' => (int) ($data['allowed_maternity_leave_days'] ?? $contract?->allowed_maternity_leave_days ?? 180),
            'status' => $contract?->status ?? Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
            'contract_status' => $contract?->contract_status ?? $contract?->status ?? Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ];

        if (! empty($data['document']) && $data['document'] instanceof UploadedFile) {
            $path = $data['document']->store('contracts', 'public');
            $payload['document_path'] = $path;
            $payload['document_name'] = $data['document']->getClientOriginalName();
            $payload['file_path'] = $path;
        } elseif ($contract && ($contract->document_path || $contract->file_path)) {
            $payload['document_path'] = $contract->document_path ?? $contract->file_path;
            $payload['document_name'] = $contract->document_name;
            $payload['file_path'] = $contract->file_path ?? $contract->document_path;
        }

        return $payload;
    }

    protected function resolveDateBasedStatus($startDate, $endDate): string
    {
        $today = Carbon::now()->startOfDay();

        if ($endDate) {
            $end = $this->normalizeDate($endDate);
            if ($end === null) {
                return Contract::STATUS_ACTIVE;
            }

            if ($end->lt($today)) {
                return Contract::STATUS_EXPIRED;
            }

            if ($end->diffInDays($today) <= 30 && $end->diffInDays($today) >= 0) {
                return 'expiring';
            }
        }

        if ($startDate) {
            $start = $this->normalizeDate($startDate);
            if ($start !== null && $start->gt($today)) {
                return Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE;
            }
        }

        return Contract::STATUS_ACTIVE;
    }

    protected function normalizeDate($value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        }

        return null;
    }

    protected function resolveContractTitle(?string $contractType): string
    {
        return match ($contractType) {
            'internship' => 'Hợp đồng thực tập',
            'probation' => 'Hợp đồng thử việc',
            'official' => 'Hợp đồng lao động chính thức',
            'seasonal' => 'Hợp đồng thời vụ',
            'fixed_term' => 'Hợp đồng lao động xác định thời hạn',
            'indefinite' => 'Hợp đồng lao động không xác định thời hạn',
            'consultant' => 'Hợp đồng cộng tác viên',
            default => 'Hợp đồng lao động',
        };
    }

    protected function log(Contract $contract, User $actor, string $action, string $message, array $details = []): void
    {
        ContractLog::create([
            'contract_id' => $contract->id,
            'user_id' => $actor->id,
            'action' => $action,
            'message' => $message,
            'details' => $details,
        ]);
    }

    // =========================================================
    //  ĐỒNG BỘ LƯƠNG PAYROLL → HỢP ĐỒNG
    // =========================================================

    /**
     * Cập nhật lương trực tiếp vào một hợp đồng cụ thể (bất kể trạng thái).
     * Dùng khi user bấm "Đồng bộ lương" từ trang show/index của đúng hợp đồng đó.
     */
    public function syncSalaryToContract(User $actor, Contract $contract, \App\Models\Payroll $payroll): Contract
    {
        $oldBase      = (float) $contract->base_salary;
        $newBase      = (float) $payroll->base_salary;
        $oldAllowance = (float) $contract->allowance;
        $newAllowance = (float) ($payroll->allowance ?? $contract->allowance);

        // Không có gì thay đổi → trả về luôn, không cần ghi log thừa
        if ($oldBase === $newBase && $oldAllowance === $newAllowance) {
            return $contract;
        }

        return DB::transaction(function () use ($actor, $contract, $payroll, $oldBase, $newBase, $oldAllowance, $newAllowance): Contract {
            $contract->base_salary = $newBase;
            $contract->salary      = $newBase;
            $contract->allowance   = $newAllowance;
            $contract->save();

            $this->log($contract, $actor, 'salary_synced', 'Cập nhật lương từ bảng lương', [
                'payroll_id'      => $payroll->id,
                'payroll_period'  => $payroll->month . '/' . $payroll->year,
                'old_base_salary' => $oldBase,
                'new_base_salary' => $newBase,
                'old_allowance'   => $oldAllowance,
                'new_allowance'   => $newAllowance,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Tìm hợp đồng đang hiệu lực (hoặc chờ ký) của nhân viên rồi đồng bộ lương.
     * Dùng cho các luồng tự động (ví dụ: sau khi duyệt bảng lương).
     */
    public function syncSalaryFromPayroll(User $actor, \App\Models\Payroll $payroll): ?Contract
    {
        // Mở rộng: tìm hợp đồng active, expiring, hoặc đang chờ ký
        $contract = Contract::where('employee_id', $payroll->employee_id)
            ->whereIn('status', [
                Contract::STATUS_ACTIVE,
                'expiring',
                Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
            ])
            ->latest('id')
            ->first();

        if (! $contract) {
            return null;
        }

        return $this->syncSalaryToContract($actor, $contract, $payroll);
    }

    /**
     * Kiểm tra chênh lệch lương payroll vs hợp đồng đang hiệu lực / chờ ký.
     */
    public function detectSalaryMismatch(\App\Models\Payroll $payroll): array
    {
        $contract = Contract::where('employee_id', $payroll->employee_id)
            ->whereIn('status', [
                Contract::STATUS_ACTIVE,
                'expiring',
                Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
            ])
            ->latest('id')
            ->first();

        if (! $contract) {
            return ['has_mismatch' => false, 'contract' => null];
        }

        $contractBase = (float) ($contract->base_salary ?? $contract->salary ?? 0);
        $payrollBase  = (float) ($payroll->base_salary ?? 0);
        $diff         = $payrollBase - $contractBase;

        return [
            'has_mismatch'  => abs($diff) > 0,
            'contract'      => $contract,
            'contract_base' => $contractBase,
            'payroll_base'  => $payrollBase,
            'diff'          => $diff,
            'diff_pct'      => $contractBase > 0 ? round($diff / $contractBase * 100, 1) : 0,
        ];
    }
}
