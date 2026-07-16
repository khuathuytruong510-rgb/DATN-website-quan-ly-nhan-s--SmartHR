<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ContractService
{
    public function createContract(User $actor, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $data): Contract {
            $employee = Employee::findOrFail($data['employee_id']);
            $contract = Contract::create($this->buildPayload($actor, $employee, $data, null));
            $this->syncStatus($contract);

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

            return $contract->fresh();
        });
    }

    public function renewContract(User $actor, Contract $parentContract, array $data): Contract
    {
        return DB::transaction(function () use ($actor, $parentContract, $data): Contract {
            $employee = $parentContract->employee ?? Employee::find($parentContract->employee_id);
            $contract = Contract::create($this->buildPayload($actor, $employee, array_merge($data, [
                'employee_id' => $parentContract->employee_id,
                'parent_contract_id' => $parentContract->id,
            ]), null));

            $contract->parent_contract_id = $parentContract->id;
            $contract->save();
            $this->syncStatus($contract);

            return $contract->fresh();
        });
    }

    public function signContract(User $actor, Contract $contract, string $party): Contract
    {
        return DB::transaction(function () use ($actor, $contract, $party): Contract {
            if ($party === 'employee') {
                if ($contract->employee_signed_at) {
                    return $contract->fresh();
                }
                $contract->employee_signed_at = now();
            } elseif ($party === 'director') {
                if ($contract->director_signed_at) {
                    return $contract->fresh();
                }
                $contract->director_signed_at = now();
            } else {
                throw new \InvalidArgumentException('Loại chữ ký không hợp lệ.');
            }

            $contract->save();
            $this->syncStatus($contract);

            return $contract->fresh();
        });
    }

    public function syncStatus(Contract $contract): Contract
    {
        if ($contract->status === 'cancelled') {
            return $contract;
        }

        $nextStatus = 'waiting_employee';
        if ($contract->employee_signed_at && $contract->director_signed_at) {
            $nextStatus = $this->resolveDateBasedStatus($contract->start_date, $contract->end_date);
        } elseif ($contract->employee_signed_at) {
            $nextStatus = 'waiting_director';
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

        $positionName = trim((string) $employee->position);
        if ($positionName !== '') {
            $position = Position::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($positionName)])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($positionName) . '%'])
                ->first();

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
        $salary = $employee ? $this->getSalaryForEmployee($employee) : 0;
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        $payload = [
            'employee_id' => $data['employee_id'] ?? $contract?->employee_id,
            'title' => $data['title'] ?? $contract?->title ?? 'Hợp đồng lao động',
            'contract_code' => $this->resolveContractCode($data['contract_code'] ?? null, $contract),
            'contract_type' => $data['contract_type'] ?? $contract?->contract_type ?? 'fixed_term',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $data['notes'] ?? $contract?->notes,
            'salary' => $salary,
            'base_salary' => (float) $salary,
            'created_by' => $actor->id,
            'parent_contract_id' => $data['parent_contract_id'] ?? $contract?->parent_contract_id,
            'employee_signed_at' => $data['employee_signed_at'] ?? $contract?->employee_signed_at,
            'director_signed_at' => $data['director_signed_at'] ?? $contract?->director_signed_at,
            'status' => 'waiting_employee',
            'contract_status' => 'waiting_employee',
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
        $today = now()->startOfDay();

        if ($endDate) {
            $end = now()->parse($endDate)->startOfDay();
            if ($end->lt($today)) {
                return 'expired';
            }

            if ($end->diffInDays($today) <= 30) {
                return 'expiring';
            }
        }

        if ($startDate && now()->parse($startDate)->startOfDay()->gt($today)) {
            return 'waiting_employee';
        }

        return 'active';
    }
}
