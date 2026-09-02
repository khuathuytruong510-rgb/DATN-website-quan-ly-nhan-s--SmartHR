<?php

namespace App\Services;

use App\Contracts\DigitalSignatureProvider;
use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Position;
use App\Models\User;
use App\Support\ContractFixedTerms;
use App\Support\HrApprovalNotifier;
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
            $this->assertSalaryAboveFloor($employee, (float) ($data['base_salary'] ?? 0));
            $contract = Contract::create($this->buildPayload($actor, $employee, $data, null));
            $this->syncStatus($contract);
            $this->log($contract, $actor, 'created', 'Tạo hợp đồng', ['contract_code' => $contract->contract_code]);

            return $contract->fresh();
        });
    }

    public function updateContract(User $actor, Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $contract, $data): Contract {
            $this->assertContentEditable($contract);
            $employee = $contract->employee ?? Employee::find($contract->employee_id);
            $oldBase      = (float) $contract->base_salary;
            $oldAllowance = (float) $contract->allowance;

            $payload = $this->buildPayload($actor, $employee, $data, $contract);
            $this->assertSalaryAboveFloor($employee, (float) $payload['base_salary'], $contract);

            $contract->fill($payload);
            $contract->save();
            $this->syncStatus($contract);
            $this->log($contract, $actor, 'updated', 'Cập nhật hợp đồng', ['contract_code' => $contract->contract_code]);

            $newBase      = (float) $contract->base_salary;
            $newAllowance = (float) $contract->allowance;
            if ($oldBase !== $newBase || $oldAllowance !== $newAllowance) {
                $details = [
                    'old_base_salary' => $oldBase,
                    'new_base_salary' => $newBase,
                    'old_allowance'   => $oldAllowance,
                    'new_allowance'   => $newAllowance,
                ];
                $this->log($contract, $actor, 'salary_updated', 'Cập nhật mức lương trên hợp đồng', $details);
                $this->notifyEmployeeSalaryChanged($actor, $contract, $details);
            }

            return $contract->fresh();
        });
    }

    public function renewContract(User $actor, Contract $parentContract, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $parentContract, $data): Contract {
            $parentContract = Contract::query()->whereKey($parentContract->id)->lockForUpdate()->firstOrFail();

            $this->assertSalaryAboveFloor($employee, (float) ($data['base_salary'] ?? 0));

            $this->assertSalaryAboveFloor($employee, (float) ($data['base_salary'] ?? 0));

            $contract = Contract::create($this->buildPayload($actor, $employee, $data, null));

            // Đảm bảo parent_contract_id được lưu (buildPayload đã nhận, nhưng ghi lại cho chắc)
            if ($contract->parent_contract_id !== $parentContract->id) {
                $contract->parent_contract_id = $parentContract->id;
                $contract->save();
            }

            $renewed = $parentContract->replicate([
                'contract_code',
                'status',
                'contract_status',
                'employee_signed_at',
                'director_signed_at',
                'signed_employee_at',
                'signed_director_at',
                'content_locked_at',
                'canonical_document_path',
                'document_hash',
                'start_date',
                'end_date',
                'sign_date',
                'parent_contract_id',
                'created_by',
                'signer_id',
            ]);

            $renewed->parent_contract_id = $parentContract->id;
            $renewed->employee_id = $parentContract->employee_id;
            $renewed->contract_code = $this->resolveContractCode(null, null);
            $renewed->start_date = $startDate;
            $renewed->end_date = $data['end_date'] ?? null;
            $renewed->created_by = $actor->id;
            $renewed->employee_signed_at = null;
            $renewed->director_signed_at = null;
            $renewed->signed_employee_at = null;
            $renewed->signed_director_at = null;
            $renewed->content_locked_at = null;
            $renewed->canonical_document_path = null;
            $renewed->document_hash = null;
            $renewed->status = Contract::STATUS_DRAFT;
            $renewed->contract_status = Contract::STATUS_DRAFT;
            $renewed->save();

            $this->log($renewed, $actor, 'renewed', 'Gia hạn hợp đồng (chỉ kéo dài thời hạn, giữ nguyên nội dung)', [
                'parent_contract_id' => $parentContract->id,
                'parent_code' => $parentContract->contract_code,
                'new_start_date' => $renewed->start_date?->toDateString(),
                'new_end_date' => $renewed->end_date?->toDateString(),
            ]);

            return $renewed->fresh();
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
                $employee = $contract->employee;
                $owns = $employee && (
                    ((int) $employee->user_id === (int) $actor->id)
                    || strcasecmp((string) $employee->email, (string) $actor->email) === 0
                );
                if (! $owns) {
                    throw new \RuntimeException('Chỉ nhân viên của hợp đồng này được ký phía người lao động. HR không ký thay.');
                }
                if ($contract->employee_signed_at) {
                    throw new \RuntimeException('Nhân viên đã ký hợp đồng này. Không ký lại.');
                }
                if (! $contract->director_signed_at) {
                    throw new \RuntimeException('Nhân viên ký sau khi Giám đốc đã ký phía doanh nghiệp.');
                }
                if (! $contract->isPendingEmployeeEsign() && $contract->status !== Contract::STATUS_DIRECTOR_SIGNED) {
                    throw new \RuntimeException('Nhân viên chỉ ký khi hợp đồng đang chờ chữ ký người lao động.');
                }
                $this->applyPartyEsign($actor, $contract, ContractSignature::ROLE_EMPLOYEE);
            } elseif ($party === 'director') {
                if (! $actor->is_director) {
                    throw new \RuntimeException('Chỉ Giám đốc được ký hợp đồng phía doanh nghiệp.');
                }
                if ($actor->is_admin && ! $actor->is_director) {
                    throw new \RuntimeException('Admin không được ký thay Giám đốc.');
                }
                if ($contract->director_signed_at) {
                    throw new \RuntimeException('Giám đốc đã ký hợp đồng này. Không ký lại.');
                }
                if (! $contract->isPendingDirectorEsign()) {
                    throw new \RuntimeException('Giám đốc chỉ ký khi HR đã gửi hợp đồng sang trạng thái chờ ký.');
                }
                $this->applyPartyEsign($actor, $contract, ContractSignature::ROLE_DIRECTOR);
            } else {
                throw new \InvalidArgumentException('Loại chữ ký không hợp lệ.');
            }

            $contract->save();
            $this->syncStatus($contract);
            $this->log(
                $contract,
                $actor,
                $party === 'employee' ? 'employee_signed' : 'director_signed',
                $party === 'employee' ? 'Nhân viên ký hợp đồng (phía người lao động)' : 'Giám đốc ký hợp đồng (phía doanh nghiệp)',
                ['party' => $party]
            );

            $fresh = $contract->fresh(['directorSignature', 'employeeSignature', 'employee']);
            if ($fresh) {
                $valid = $this->verifyPartySignature($fresh, $party === 'employee' ? ContractSignature::ROLE_EMPLOYEE : ContractSignature::ROLE_DIRECTOR);
                $this->log(
                    $fresh,
                    $actor,
                    'signature_verified',
                    $valid
                        ? 'Hệ thống xác thực chữ ký '.($party === 'employee' ? 'nhân viên' : 'Giám đốc').' — hợp lệ'
                        : 'Hệ thống xác thực chữ ký — không khớp',
                    ['valid' => $valid, 'party' => $party]
                );

                if ($party === 'director') {
                    $this->notifyEmployeeNeedsToSign($fresh, $actor);
                }

                if ($fresh->isFullySigned()) {
                    $this->log($fresh, $actor, 'status_signed', 'Đã đủ chữ ký hai bên — khóa hợp đồng, chuyển sang '.$fresh->statusLabel(), [
                        'status' => $fresh->status,
                    ]);
                    $this->notifyContractFullySigned($fresh, $actor);
                    if ($fresh->parent_contract_id && $fresh->status === Contract::STATUS_ACTIVE) {
                        app(ContractExpiryAlertService::class)->notifyRenewalActivated($fresh, $actor);
                    }
                }
            }

            return $fresh;
        });
    }

    public function sendForDirectorSignature(User $actor, Contract $contract): Contract
    {
        if (! $actor->is_hr) {
            throw new \RuntimeException('Chỉ HR được gửi hợp đồng cho Giám đốc ký số.');
        }

        return DB::transaction(function () use ($actor, $contract): Contract {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($contract->director_signed_at || $contract->isFullySigned()) {
                throw new \RuntimeException('Hợp đồng đã có chữ ký. Không gửi lại. Hãy tạo hợp đồng/gia hạn mới nếu cần sửa.');
            }
            if ($contract->isPendingDirectorEsign() && $contract->content_locked_at) {
                throw new \RuntimeException('Hợp đồng đã ở trạng thái chờ ký. Giám đốc có thể mở và ký.');
            }
            if (! $contract->isAwaitingHrSend() && $contract->status !== Contract::STATUS_DRAFT) {
                throw new \RuntimeException('Chỉ gửi ký khi hợp đồng đang ở trạng thái nháp / HR đang kiểm tra.');
            }

            $frozen = app(ContractDocumentService::class)->freeze($contract);
            $frozen->status = Contract::STATUS_PENDING_SIGNATURE;
            $frozen->contract_status = Contract::STATUS_PENDING_SIGNATURE;
            $frozen->save();

            $this->log($frozen, $actor, 'sent_for_signature', 'HR kiểm tra và gửi yêu cầu ký (Giám đốc → nhân viên)', [
                'document_hash' => $frozen->document_hash,
            ]);

            $this->notifyDirectorPendingSignature($frozen->fresh(['employee']), $actor);

            return $frozen->fresh();
        });
    }

    public function rejectDirectorSignature(User $actor, Contract $contract, string $reason): Contract
    {
        if (! $actor->is_director) {
            throw new \RuntimeException('Chỉ Giám đốc được từ chối yêu cầu ký số.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 8) {
            throw new \RuntimeException('Cần lý do từ chối (tối thiểu 8 ký tự).');
        }

        return DB::transaction(function () use ($actor, $contract, $reason): Contract {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($contract->director_signed_at) {
                throw new \RuntimeException('Hợp đồng đã ký số. Không từ chối. Hãy tạo hợp đồng mới nếu cần sửa.');
            }

            $contract->status = Contract::STATUS_DRAFT;
            $contract->contract_status = Contract::STATUS_DRAFT;
            $contract->content_locked_at = null;
            $contract->save();

            ContractSignature::query()->create([
                'contract_id' => $contract->id,
                'signer_id' => $actor->id,
                'signer_role' => ContractSignature::ROLE_DIRECTOR,
                'document_hash' => (string) $contract->document_hash,
                'status' => ContractSignature::STATUS_REJECTED,
                'provider' => app(DigitalSignatureProvider::class)->name(),
                'verify_note' => $reason,
                'signed_at' => now(),
            ]);

            $this->log($contract, $actor, 'signature_rejected', 'Giám đốc từ chối ký số — trả HR chỉnh lại', ['reason' => $reason]);
            $this->syncStatus($contract);
            $this->notifyHrSignatureRejected($contract->fresh(['employee']), $actor, $reason);

            return $contract->fresh();
        });
    }

    public function verifyDirectorSignature(Contract $contract): bool
    {
        return $this->verifyPartySignature($contract, ContractSignature::ROLE_DIRECTOR);
    }

    public function verifyEmployeeSignature(Contract $contract): bool
    {
        return $this->verifyPartySignature($contract, ContractSignature::ROLE_EMPLOYEE);
    }

    public function verifyAllSignatures(Contract $contract): bool
    {
        if ($contract->director_signed_at && ! $this->verifyDirectorSignature($contract)) {
            return false;
        }
        if ($contract->employee_signed_at && ! $this->verifyEmployeeSignature($contract)) {
            return false;
        }

        return $contract->isFullySigned();
    }

    public function verifyPartySignature(Contract $contract, string $role): bool
    {
        $signature = $role === ContractSignature::ROLE_EMPLOYEE
            ? $contract->employeeSignature
            : $contract->directorSignature;
        if (! $signature || ! $signature->isSigned()) {
            return false;
        }

        $documents = app(ContractDocumentService::class);
        if (! $documents->matchesFrozenHash($contract)) {
            return false;
        }

        $provider = app(DigitalSignatureProvider::class);

        return $provider->verify($signature->document_hash, (string) $signature->signature_value, [
            'signer_id' => (int) $signature->signer_id,
            'signer_role' => (string) $signature->signer_role,
            'contract_id' => (int) $contract->id,
        ]);
    }

    public function assertContentEditable(Contract $contract): void
    {
        if ($contract->isContentLocked()) {
            throw new \RuntimeException('Hợp đồng đã khóa tài liệu / đã ký số. Không sửa nội dung. Hãy tạo hợp đồng hoặc gia hạn mới rồi ký lại.');
        }
    }

    protected function applyPartyEsign(User $actor, Contract $contract, string $role): void
    {
        $documents = app(ContractDocumentService::class);
        if (! $contract->document_hash || ! $contract->content_locked_at) {
            $documents->freeze($contract);
            $contract->refresh();
        } elseif (! $documents->matchesFrozenHash($contract)) {
            throw new \RuntimeException('Nội dung hợp đồng đã đổi sau khi khóa. Hash không khớp. HR phải gửi ký lại.');
        }

        $provider = app(DigitalSignatureProvider::class);
        $hash = (string) $contract->document_hash;
        $meta = [
            'signer_id' => (int) $actor->id,
            'signer_role' => $role,
            'contract_id' => (int) $contract->id,
        ];
        $value = $provider->sign($hash, $meta);
        $transactionId = 'MOCK-'.strtoupper(substr($hash, 0, 8)).'-'.$role.'-'.$contract->id;

        ContractSignature::query()->create([
            'contract_id' => $contract->id,
            'signer_id' => $actor->id,
            'signer_role' => $role,
            'document_hash' => $hash,
            'signature_value' => $value,
            'signed_document_path' => $contract->canonical_document_path,
            'signed_at' => now(),
            'status' => ContractSignature::STATUS_SIGNED,
            'provider' => $provider->name(),
            'provider_transaction_id' => $transactionId,
            'verify_note' => config('esign.disclaimer'),
        ]);

        if ($role === ContractSignature::ROLE_DIRECTOR) {
            $contract->director_signed_at = now();
            $contract->signed_director_at = $contract->director_signed_at;
            $contract->signer_id = $actor->id;
            $contract->status = Contract::STATUS_DIRECTOR_SIGNED;
            $contract->contract_status = Contract::STATUS_DIRECTOR_SIGNED;
        } else {
            $contract->employee_signed_at = now();
            $contract->signed_employee_at = $contract->employee_signed_at;
            $contract->status = Contract::STATUS_EMPLOYEE_SIGNED;
            $contract->contract_status = Contract::STATUS_EMPLOYEE_SIGNED;
        }
    }

    public function terminateForEmployeeDeletion(Employee $employee, User $actor, ?string $reason = null): int
    {
        $reasonNote = $reason ? ' Lý do: '.$reason : '';
        $count = 0;

        $contracts = Contract::query()
            ->where('employee_id', $employee->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($contracts as $contract) {
            if (in_array($contract->status, [Contract::STATUS_TERMINATED, Contract::STATUS_CANCELLED], true)) {
                continue;
            }

            $previous = $contract->status;
            $contract->status = Contract::STATUS_TERMINATED;
            $contract->contract_status = Contract::STATUS_TERMINATED;
            if ($contract->end_date && $contract->end_date->isFuture()) {
                $contract->end_date = now()->toDateString();
            }

            $line = 'Chấm dứt do xóa hồ sơ nhân viên ('.$actor->name.', '.now()->format('d/m/Y H:i').').'.$reasonNote;
            $contract->notes = trim((string) $contract->notes) === ''
                ? $line
                : rtrim((string) $contract->notes)."\n".$line;
            $contract->save();

            $this->log($contract, $actor, 'terminated', 'Chấm dứt hợp đồng do xóa nhân viên', [
                'previous_status' => $previous,
                'employee_id' => $employee->id,
            ]);
            $count++;
        }

        return $count;
    }

    public function syncStatus(Contract $contract): Contract
    {
        if (in_array($contract->status, [Contract::STATUS_CANCELLED, Contract::STATUS_TERMINATED, Contract::STATUS_REJECTED], true)) {
            return $contract;
        }

        $nextStatus = Contract::STATUS_DRAFT;
        if ($contract->employee_signed_at && $contract->director_signed_at) {
            $dated = $this->resolveDateBasedStatus($contract->start_date, $contract->end_date);
            $nextStatus = in_array($dated, [Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE, Contract::STATUS_DRAFT], true)
                ? Contract::STATUS_SIGNED
                : $dated;
        } elseif ($contract->director_signed_at) {
            $nextStatus = Contract::STATUS_DIRECTOR_SIGNED;
        } elseif ($contract->content_locked_at) {
            $nextStatus = Contract::STATUS_PENDING_SIGNATURE;
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
        $template = $this->defaultTemplateFor($contractType, $data['contract_template_id'] ?? null);

        $fixedTerms = $this->officialTerms($contractType);
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
            'terms' => $fixedTerms,
            'additional_terms' => $data['additional_terms'] ?? $contract?->additional_terms,
            'contract_content' => $fixedTerms,
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
            'status' => $contract?->status ?? Contract::STATUS_DRAFT,
            'contract_status' => $contract?->contract_status ?? $contract?->status ?? Contract::STATUS_DRAFT,
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

    public function officialTerms(?string $contractType, mixed $templateId = null): string
    {
        $template = $this->defaultTemplateFor($contractType, $templateId);
        $fromTemplate = trim((string) ($template?->content ?? ''));
        if ($fromTemplate !== '') {
            return (string) $template->content;
        }

        return ContractFixedTerms::forType($contractType);
    }

    protected function defaultTemplateFor(?string $contractType, mixed $templateId = null): ?ContractTemplate
    {
        if (! empty($templateId)) {
            $selected = ContractTemplate::query()->find($templateId);
            if ($selected) {
                return $selected;
            }
        }

        if (! $contractType) {
            return null;
        }

        return ContractTemplate::query()
            ->active()
            ->where('contract_type', $contractType)
            ->where('is_default', true)
            ->orderByDesc('id')
            ->first();
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

    public function log(Contract $contract, User $actor, string $action, string $message, array $details = []): void
    {
        ContractLog::create([
            'contract_id' => $contract->id,
            'user_id' => $actor->id,
            'action' => $action,
            'message' => $message,
            'details' => $details,
        ]);
    }

    /**
     * Mức sàn = lương cơ bản của hợp đồng đang hiệu lực (active) của nhân viên.
     * Nếu đang sửa đúng hợp đồng active thì sàn là lương CB hiện tại của chính nó.
     */
    protected function salaryFloorFor(Employee $employee, ?Contract $editing = null): float
    {
        $contract = ($editing && $editing->status === Contract::STATUS_ACTIVE)
            ? $editing
            : Contract::query()
                ->where('employee_id', $employee->id)
                ->where('status', Contract::STATUS_ACTIVE)
                ->latest('id')
                ->first();

        return (float) ($contract?->base_salary ?: $contract?->salary ?: 0);
    }

    /**
     * Lương cơ bản không được giảm xuống dưới mức sàn (lương CB hợp đồng đang hiệu lực).
     */
    protected function assertSalaryAboveFloor(Employee $employee, float $salary, ?Contract $editing = null): void
    {
        $floor = $this->salaryFloorFor($employee, $editing);

        if ($floor > 0 && $salary + 0.0001 < $floor) {
            throw new \RuntimeException(sprintf(
                'Lương cơ bản (%s₫) không được thấp hơn mức lương cơ bản đang hiệu lực (%s₫) của nhân viên.',
                number_format($salary, 0, ',', '.'),
                number_format($floor, 0, ',', '.')
            ));
        }
    }

    /**
     * Gửi thông báo cho nhân viên khi mức lương trên hợp đồng thay đổi.
     * Không chặn tính lương — chỉ ghi log + thông báo, lương mới áp dụng ngay.
     */
    protected function notifyEmployeeSalaryChanged(User $actor, Contract $contract, array $details): void
    {
        if (! $contract->employee) {
            return;
        }

        $userId = $contract->employee->user_id
            ?? User::where('email', $contract->employee->email)->value('id');

        if (! $userId) {
            return;
        }

        $oldBase      = (float) ($details['old_base_salary'] ?? 0);
        $newBase      = (float) ($details['new_base_salary'] ?? 0);
        $oldAllowance = (float) ($details['old_allowance'] ?? 0);
        $newAllowance = (float) ($details['new_allowance'] ?? 0);

        Notification::create([
            'sender_id' => $actor->id,
            'target'    => 'employee',
            'title'     => 'Mức lương trên hợp đồng thay đổi',
            'message'   => sprintf(
                'Hợp đồng %s của bạn vừa được cập nhật: lương cơ bản %sđ → %sđ, phụ cấp %sđ → %sđ. Bạn có thể xem chi tiết trong phần Hợp đồng.',
                $contract->contract_code,
                number_format($oldBase, 0, ',', '.'),
                number_format($newBase, 0, ',', '.'),
                number_format($oldAllowance, 0, ',', '.'),
                number_format($newAllowance, 0, ',', '.')
            ),
            'data' => [
                'type'          => 'contract_salary_updated',
                'contract_id'   => $contract->id,
                'employee_id'   => $contract->employee_id,
                'old_base_salary' => $oldBase,
                'new_base_salary' => $newBase,
            ],
            'is_read' => false,
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
        if ($contract->isContentLocked()) {
            throw new \RuntimeException('Hợp đồng đã khóa tài liệu / đã ký số. Không đồng bộ lương vào bản đã ký. Tạo hợp đồng mới nếu cần điều chỉnh.');
        }

        $oldBase      = (float) $contract->base_salary;
        $newBase      = (float) $payroll->base_salary;
        $oldAllowance = (float) $contract->allowance;
        $newAllowance = (float) ($payroll->allowance ?? $contract->allowance);

        // Không được giảm lương hợp đồng xuống dưới mức lương cơ bản đang hiệu lực
        if ($newBase < $oldBase) {
            throw new \RuntimeException(sprintf(
                'Không thể giảm lương hợp đồng %s từ %s₫ xuống %s₫. Lương không được thấp hơn mức lương cơ bản đang hiệu lực (%s₫).',
                $contract->contract_code,
                number_format($oldBase, 0, ',', '.'),
                number_format($newBase, 0, ',', '.'),
                number_format($oldBase, 0, ',', '.')
            ));
        }

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
            $this->notifyEmployeeSalaryChanged($actor, $contract, [
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

    protected function notifyDirectorPendingSignature(Contract $contract, User $actor): void
    {
        $name = optional($contract->employee)->name ?: 'nhân viên';
        Notification::create([
            'sender_id' => $actor->id,
            'target' => 'director',
            'title' => 'Hợp đồng chờ ký số',
            'message' => sprintf(
                'HR đã khóa tài liệu hợp đồng %s của %s. Vui lòng xem PDF, kiểm tra hash rồi ký số hoặc từ chối.',
                $contract->contract_code,
                $name
            ),
            'is_read' => false,
            'data' => [
                'type' => 'contract_esign',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
            ],
        ]);
    }

    protected function notifyHrSignatureRejected(Contract $contract, User $actor, string $reason): void
    {
        $name = optional($contract->employee)->name ?: 'nhân viên';
        Notification::create([
            'sender_id' => $actor->id,
            'target' => 'hr',
            'title' => 'Giám đốc từ chối ký số hợp đồng',
            'message' => sprintf('Hợp đồng %s của %s bị từ chối. Lý do: %s. HR có thể sửa và gửi ký lại.', $contract->contract_code, $name, $reason),
            'is_read' => false,
            'data' => [
                'type' => 'contract_esign',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
            ],
        ]);
    }

    protected function notifyEmployeeNeedsToSign(Contract $contract, User $actor): void
    {
        if (! $contract->employee_id) {
            return;
        }

        HrApprovalNotifier::send(
            (int) $contract->employee_id,
            $actor,
            'Hợp đồng cần bạn ký',
            sprintf(
                'Giám đốc đã ký hợp đồng %s phía doanh nghiệp. Hãy đăng nhập cổng nhân viên, xem nội dung rồi ký phía người lao động.',
                $contract->contract_code
            ),
            [
                'type' => 'contract_esign',
                'contract_id' => $contract->id,
            ]
        );
    }

    protected function notifyContractFullySigned(Contract $contract, User $actor): void
    {
        $name = optional($contract->employee)->name ?: 'nhân viên';
        Notification::create([
            'sender_id' => $actor->id,
            'target' => 'hr',
            'title' => 'Hợp đồng đã đủ chữ ký hai bên',
            'message' => sprintf('Hợp đồng %s của %s đã được Giám đốc và nhân viên ký. Tài liệu đã khóa.', $contract->contract_code, $name),
            'is_read' => false,
            'data' => [
                'type' => 'contract_esign',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
            ],
        ]);

        if ($contract->employee_id) {
            HrApprovalNotifier::send(
                (int) $contract->employee_id,
                $actor,
                'Hợp đồng đã hoàn tất ký',
                sprintf(
                    'Hợp đồng %s đã đủ chữ ký hai bên và được khóa. Bạn có thể xem/tải tài liệu đã ký (mô phỏng).',
                    $contract->contract_code
                ),
                [
                    'type' => 'contract_esign',
                    'contract_id' => $contract->id,
                ]
            );
        }
    }
}
