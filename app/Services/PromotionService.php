<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Position;
use App\Models\PromotionRequest;
use App\Models\SalaryHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromotionService
{
    public function create(User $actor, Employee $employee, array $data): PromotionRequest
    {
        return DB::transaction(function () use ($actor, $employee, $data): PromotionRequest {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $pendingExists = PromotionRequest::where('employee_id', $employee->id)
                ->where('status', PromotionRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($pendingExists) {
                throw new RuntimeException('Nhân viên này đang có đề xuất thăng chức/tăng lương chờ duyệt. Hãy xử lý đề xuất cũ trước.');
            }

            // Thăng chức: mức lương cơ bản mới bắt buộc theo lương chuẩn chức vụ mới (server ép).
            if (in_array($data['change_type'] ?? null, [PromotionRequest::CHANGE_PROMOTION, PromotionRequest::CHANGE_BOTH], true)
                && ! empty($data['new_position_id'])) {
                $position = Position::find($data['new_position_id']);
                if ($position) {
                    $base = self::suggestSalaryFromPosition($position, (float) ($data['new_base_salary'] ?? 0));
                    if ($base > 0) {
                        $data['new_base_salary'] = $base;
                    }
                }
            }

            $this->assertGteThanCurrent($employee, $data);

            try {
                $request = PromotionRequest::create([
                    'code' => $this->resolveCode(),
                    'employee_id' => $employee->id,
                    'change_type' => $data['change_type'],
                    'old_position_id' => $employee->position_id,
                    'old_position' => $employee->position,
                    'new_position_id' => $data['new_position_id'] ?? null,
                    'new_position' => $data['new_position'] ?? ($employee->position ?? null),
                    'department_id' => $data['department_id'] ?? null,
                    'old_base_salary' => (float) ($data['old_base_salary'] ?? 0),
                    'new_base_salary' => (float) ($data['new_base_salary'] ?? 0),
                    'old_allowance' => (float) ($data['old_allowance'] ?? 0),
                    'new_allowance' => (float) ($data['new_allowance'] ?? 0),
                    'effective_date' => $data['effective_date'] ?? now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                    'reason' => $data['reason'] ?? null,
                    'document_number' => $data['document_number'] ?? null,
                    'status' => PromotionRequest::STATUS_PENDING,
                    'submitted_by' => $actor->id,
                ]);
            } catch (QueryException) {
                throw new RuntimeException('Nhân viên này đang có đề xuất chờ duyệt. Vui lòng xử lý trước khi tạo mới.');
            }

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'promotion_created',
                'meta' => sprintf('promotion:%d;employee:%d;type:%s', $request->id, $employee->id, $request->change_type),
            ]);

            $this->notify(
                $actor,
                'Đề xuất thăng chức / tăng lương — '.($employee->name ?? 'Nhân viên'),
                ($employee->name ?? 'Nhân viên').' có đề xuất "'.$request->changeTypeLabel().'" mức lương mới '.number_format((float) $request->new_base_salary).' ₫ đang chờ Giám đốc duyệt.',
                ['promotion_request_id' => $request->id, 'employee_id' => $employee->id, 'type' => 'promotion_pending']
            );

            return $request->fresh(['employee', 'submittedBy']);
        });
    }

    public function approve(User $actor, PromotionRequest $request, ?string $note = null): PromotionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): PromotionRequest {
            $request = $this->lockPending($request);

            $request->update([
                'status' => PromotionRequest::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note ?: null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'promotion_approved',
                'meta' => 'promotion:'.$request->id.';employee:'.$request->employee_id,
            ]);

            $this->notify(
                $actor,
                'Đề xuất thăng chức / tăng lương đã được duyệt',
                'Đề xuất của '.($request->employee->name ?? 'nhân viên').' đã được Giám đốc phê duyệt. Hệ thống đã tự động cập nhật mức lương và ghi lịch sử.',
                ['promotion_request_id' => $request->id, 'employee_id' => $request->employee_id, 'type' => 'promotion_approved']
            );

            // Giám đốc duyệt => tự động áp dụng: cập nhật lương/chức vụ, ghi lịch sử
            // và thông báo cho nhân viên (Duyệt → Cập nhật mức lương → Thông báo NV).
            $this->apply($actor, $request->fresh());

            return $request->fresh(['employee', 'submittedBy', 'reviewedBy', 'appliedBy', 'newPosition']);
        });
    }

    public function reject(User $actor, PromotionRequest $request, string $note): PromotionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): PromotionRequest {
            $request = $this->lockPending($request);

            $request->update([
                'status' => PromotionRequest::STATUS_REJECTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'promotion_rejected',
                'meta' => 'promotion:'.$request->id.';employee:'.$request->employee_id,
            ]);

            $this->notify(
                $actor,
                'Đề xuất thăng chức / tăng lương bị từ chối',
                'Đề xuất của '.($request->employee->name ?? 'nhân viên').' đã bị từ chối.'.($note ? ' Lý do: '.$note : ''),
                ['promotion_request_id' => $request->id, 'employee_id' => $request->employee_id, 'type' => 'promotion_rejected']
            );

            return $request->fresh(['employee', 'submittedBy', 'reviewedBy']);
        });
    }

    public function cancel(User $actor, PromotionRequest $request, ?string $note = null): PromotionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): PromotionRequest {
            $request = PromotionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $request->isPending() && ! $request->isApproved()) {
                throw new RuntimeException('Chỉ hủy được đề xuất đang chờ duyệt hoặc đã duyệt nhưng chưa áp dụng.');
            }

            $request->update([
                'status' => PromotionRequest::STATUS_CANCELLED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'cancellation_note' => $note ?: null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'promotion_cancelled',
                'meta' => 'promotion:'.$request->id.';employee:'.$request->employee_id,
            ]);

            return $request->fresh(['employee', 'submittedBy', 'reviewedBy']);
        });
    }

    /**
     * Thực hiện thay đổi: cập nhật nhân viên, hợp đồng đang hiệu lực, ghi lịch sử lương.
     */
    public function apply(User $actor, PromotionRequest $request): PromotionRequest
    {
        return DB::transaction(function () use ($actor, $request): PromotionRequest {
            $request = PromotionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $request->isApproved()) {
                throw new RuntimeException('Chỉ áp dụng đề xuất đã được Giám đốc duyệt.');
            }

            $employee = $request->employee;
            if (! $employee) {
                throw new RuntimeException('Không tìm thấy nhân viên của đề xuất.');
            }

            $this->enforcePositionSalary($request);

            $this->updateEmployee($request, $employee);
            $this->updateActiveContract($actor, $request, $employee);
            $this->recordSalaryHistory($actor, $request, $employee);

            $request->update([
                'status' => PromotionRequest::STATUS_APPLIED,
                'applied_by' => $actor->id,
                'applied_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'promotion_applied',
                'meta' => sprintf(
                    'promotion:%d;employee:%d;new_position:%s;new_salary:%s',
                    $request->id,
                    $employee->id,
                    $request->new_position ?? '-',
                    (float) $request->new_base_salary
                ),
            ]);

            try {
                $this->notify(
                    $actor,
                    $request->hasPositionChange() ? 'Thăng chức đã được thực hiện' : 'Tăng lương đã được thực hiện',
                    ($request->hasPositionChange() ? 'Bạn đã được thăng chức lên vị trí '.$request->new_position.'. ' : '')
                    .'Mức lương mới: '.number_format((float) $request->new_base_salary).' ₫'
                    .($request->effective_date ? ', hiệu lực từ '.$request->effective_date->format('d/m/Y') : '')
                    .'.',
                    ['promotion_request_id' => $request->id, 'employee_id' => $request->employee_id, 'type' => 'promotion_applied']
                );
            } catch (\Throwable) {
            }

            return $request->fresh(['employee', 'submittedBy', 'reviewedBy', 'appliedBy', 'newPosition']);
        });
    }

    protected function updateEmployee(PromotionRequest $request, Employee $employee): void
    {
        $oldDepartmentId = $employee->department_id;

        $changes = [];

        if ($request->hasPositionChange()) {
            $changes['position'] = $request->new_position;
            $changes['position_id'] = $request->new_position_id;
        }

        if (filled($request->department_id)) {
            $changes['department_id'] = $request->department_id;
        }

        if ($changes !== []) {
            $employee->update($changes);
        }

        if (filled($request->department_id) && (int) $request->department_id !== (int) $oldDepartmentId) {
            Department::whereKey($oldDepartmentId)->update([
                'employee_count' => Employee::where('department_id', $oldDepartmentId)->count(),
            ]);
            Department::whereKey($request->department_id)->update([
                'employee_count' => Employee::where('department_id', $request->department_id)->count(),
            ]);
        }
    }

    protected function updateActiveContract(User $actor, PromotionRequest $request, Employee $employee): void
    {
        if (! $request->hasSalaryChange()) {
            return;
        }

        $contract = Contract::where('employee_id', $employee->id)
            ->whereIn('status', [
                Contract::STATUS_ACTIVE,
                'expiring',
                Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
            ])
            ->latest('id')
            ->first();

        if (! $contract) {
            return;
        }

        $oldBase = (float) $contract->base_salary;
        $oldAllowance = (float) $contract->allowance;
        $newBase = (float) $request->new_base_salary;
        $newAllowance = (float) $request->new_allowance;

        $contract->base_salary = $newBase;
        $contract->salary = $newBase;
        $contract->allowance = $newAllowance;
        $contract->save();

        ContractLog::create([
            'contract_id' => $contract->id,
            'user_id' => $actor->id,
            'action' => 'promotion_applied',
            'message' => 'Cập nhật lương theo quyết định thăng chức/tăng lương',
            'details' => [
                'promotion_request_id' => $request->id,
                'document_number' => $request->document_number,
                'effective_date' => optional($request->effective_date)->toDateString(),
                'old_base_salary' => $oldBase,
                'new_base_salary' => $newBase,
                'old_allowance' => $oldAllowance,
                'new_allowance' => $newAllowance,
                'new_position' => $request->new_position,
            ],
        ]);
    }

    protected function recordSalaryHistory(User $actor, PromotionRequest $request, Employee $employee): SalaryHistory
    {
        $notes = trim(implode("\n", array_filter([
            $request->changeTypeLabel().' theo '.($request->document_number ? 'QĐ '.$request->document_number : 'đề xuất '.$request->code),
            $request->reason,
        ])));

        return SalaryHistory::create([
            'employee_id' => $employee->id,
            'code' => 'TC-'.$request->code,
            'effective_date' => $request->effective_date?->toDateString() ?? now()->toDateString(),
            'change_type' => $request->changeTypeLabel(),
            'old_salary' => (float) $request->old_base_salary,
            'new_salary' => (float) $request->new_base_salary,
            'position' => $request->new_position ?? $employee->position,
            'department_id' => $request->department_id ?? $employee->department_id,
            'allowances' => [
                'other' => (float) $request->new_allowance,
            ],
            'notes' => $notes,
            'document_number' => $request->document_number,
            'status' => SalaryHistory::STATUS_APPLIED,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * Thăng chức: ghi đè lương cơ bản mới theo lương chuẩn chức vụ mới (server ép).
     */
    protected function enforcePositionSalary(PromotionRequest $request): void
    {
        if (! $request->hasPositionChange()) {
            return;
        }

        $position = $request->newPosition
            ?? (filled($request->new_position_id) ? Position::find($request->new_position_id) : null);

        if (! $position) {
            return;
        }

        $base = self::suggestSalaryFromPosition($position, (float) $request->new_base_salary);

        if ($base > 0) {
            $request->new_base_salary = $base;
        }
    }

    protected function lockPending(PromotionRequest $request): PromotionRequest
    {
        $request = PromotionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

        if (! $request->isPending()) {
            throw new RuntimeException('Đề xuất đã được xử lý trước đó.');
        }

        return $request;
    }

    protected function assertGteThanCurrent(Employee $employee, array $data): void
    {
        $newSalary = (float) ($data['new_base_salary'] ?? 0);
        if ($newSalary <= 0) {
            throw new RuntimeException('Mức lương mới phải lớn hơn 0.');
        }

        if (in_array($data['change_type'] ?? null, [PromotionRequest::CHANGE_SALARY_RAISE, PromotionRequest::CHANGE_BOTH], true)) {
            $current = (float) ($data['old_base_salary'] ?? 0);
            if ($newSalary < $current) {
                throw new RuntimeException('Tăng lương không được thấp hơn mức lương hiện tại ('.number_format($current).' ₫).');
            }
        }
    }

    protected function resolveCode(): string
    {
        return 'TC-'.now()->format('Ymd').'-'.str_pad((string) ((PromotionRequest::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function notify(?User $actor, string $title, string $message, array $data = []): void
    {
        Notification::create([
            'sender_id' => $actor?->id,
            'target' => $this->resolveTarget($data),
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => $data,
        ]);
    }

    protected function resolveTarget(array $data): string
    {
        $employeeId = $data['employee_id'] ?? null;
        $type = $data['type'] ?? null;

        if ($type === 'promotion_applied' && $employeeId) {
            return 'employee';
        }

        return 'hr';
    }

    public static function actorCanApprove(?User $user): bool
    {
        return $user !== null && $user->is_director;
    }

    public static function actorCanManage(?User $user): bool
    {
        return $user !== null && ($user->is_hr || $user->is_admin);
    }

    public static function syncDepartmentCount(?int $departmentId): void
    {
        if (! $departmentId) {
            return;
        }

        Department::whereKey($departmentId)->update([
            'employee_count' => Employee::where('department_id', $departmentId)->count(),
        ]);
    }

    public static function suggestSalaryFromPosition(?Position $position, float $fallback = 0): float
    {
        if (! $position) {
            return $fallback;
        }

        if ((float) $position->base_salary > 0) {
            return (float) $position->base_salary;
        }

        if ((float) $position->salary_range_min > 0) {
            return (float) $position->salary_range_min;
        }

        return $fallback;
    }
}