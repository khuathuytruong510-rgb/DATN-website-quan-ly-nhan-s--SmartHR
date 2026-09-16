<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeletionRequestService
{
    public function pendingFor(string $kind, int $targetId): ?DeletionRequest
    {
        $model = match ($kind) {
            DeletionRequest::KIND_EMPLOYEE => Employee::class,
            DeletionRequest::KIND_DEPARTMENT => Department::class,
            default => null,
        };

        if ($model === null) {
            return null;
        }

        return DeletionRequest::query()
            ->where('kind', $kind)
            ->where('requestable_type', $model)
            ->where('requestable_id', $targetId)
            ->where('status', DeletionRequest::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    public function pendingTransferIdForEmployee(int $employeeId): ?int
    {
        return DeletionRequest::query()
            ->where('kind', DeletionRequest::KIND_TRANSFER)
            ->where('requestable_type', Employee::class)
            ->where('requestable_id', $employeeId)
            ->where('status', DeletionRequest::STATUS_PENDING)
            ->latest('id')
            ->value('id');
    }

    public function pendingTransferMap(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return DeletionRequest::query()
            ->where('kind', DeletionRequest::KIND_TRANSFER)
            ->where('requestable_type', Employee::class)
            ->whereIn('requestable_id', $employeeIds)
            ->where('status', DeletionRequest::STATUS_PENDING)
            ->latest('id')
            ->get(['id', 'requestable_id'])
            ->unique('requestable_id')
            ->pluck('id', 'requestable_id')
            ->all();
    }

    public function transferEmployees(
        Department $from,
        Department $target,
        array $employeeIds,
        User $actor,
        ?string $reason = null,
        mixed $document = null
    ): void {
        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));
        if ($employeeIds === []) {
            throw new RuntimeException('Vui lòng chọn ít nhất một nhân viên để điều chuyển.');
        }

        DB::transaction(function () use ($from, $target, $employeeIds, $actor, $reason): void {
            $employees = Employee::query()
                ->whereIn('id', $employeeIds)
                ->where('department_id', $from->id)
                ->lockForUpdate()
                ->get();

            if ($employees->count() !== count($employeeIds)) {
                throw new RuntimeException('Danh sách nhân viên điều chuyển không còn thuộc phòng ban hiện tại.');
            }

            foreach ($employees as $employee) {
                if ($this->pendingTransferIdForEmployee($employee->id) !== null) {
                    throw new RuntimeException('Nhân viên "'.$employee->name.'" đã có yêu cầu điều chuyển đang chờ duyệt.');
                }

                $request = DeletionRequest::create([
                    'code' => $this->resolveCode(),
                    'kind' => DeletionRequest::KIND_TRANSFER,
                    'requestable_id' => $employee->id,
                    'requestable_type' => Employee::class,
                    'name' => $employee->name,
                    'payload' => [
                        'employee' => $employee->getAttributes(),
                        'from_department_id' => $from->id,
                        'target_department_id' => $target->id,
                    ],
                    'reason' => $reason ?: 'Điều chuyển nhân sự theo yêu cầu HR.',
                    'status' => DeletionRequest::STATUS_PENDING,
                    'submitted_by' => $actor->id,
                ]);

                ActivityLog::create([
                    'user_id' => $actor->id,
                    'action' => 'transfer_request_created',
                    'meta' => sprintf('deletion:%d;employee:%d;from:%d;to:%d', $request->id, $employee->id, $from->id, $target->id),
                ]);
            }

            $this->notify(
                $actor,
                'Yêu cầu điều chuyển nhân viên',
                $actor->name.' đề nghị điều chuyển '.count($employeeIds).' nhân viên từ '.$from->name.' sang '.$target->name.'.',
                ['kind' => DeletionRequest::KIND_TRANSFER, 'type' => 'transfer_request_pending']
            );
        });
    }

    public function submit(User $actor, string $kind, int $targetId, string $reason): DeletionRequest
    {
        $target = $this->resolveTarget($kind, $targetId);

        return DB::transaction(function () use ($actor, $kind, $target, $reason): DeletionRequest {
            $model = get_class($target);

            $pendingExists = DeletionRequest::where('requestable_type', $model)
                ->where('requestable_id', $target->id)
                ->where('status', DeletionRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($pendingExists) {
                throw new RuntimeException($this->label($kind).' này đang có yêu cầu xóa chờ Giám đốc duyệt.');
            }

            try {
                $request = DeletionRequest::create([
                    'code' => $this->resolveCode(),
                    'kind' => $kind,
                    'requestable_id' => $target->id,
                    'requestable_type' => $model,
                    'name' => (string) ($target->name ?? ('#'.$target->id)),
                    'payload' => $target->getAttributes(),
                    'reason' => $reason,
                    'status' => DeletionRequest::STATUS_PENDING,
                    'submitted_by' => $actor->id,
                ]);
            } catch (QueryException) {
                throw new RuntimeException($this->label($kind).' này đang có yêu cầu xóa chờ duyệt. Vui lòng xử lý trước.');
            }

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'deletion_request_created',
                'meta' => sprintf('deletion:%d;kind:%s;target:%d', $request->id, $kind, $target->id),
            ]);

            $this->notify(
                $actor,
                'Yêu cầu xóa '.mb_strtolower($this->label($kind)).' — '.$request->name,
                $actor->name.' đề nghị xóa '.mb_strtolower($this->label($kind)).' "'.$request->name.'". Lý do: '.$reason.' — đang chờ Giám đốc duyệt.',
                ['deletion_request_id' => $request->id, 'kind' => $kind, 'type' => 'deletion_request_pending']
            );

            return $request->fresh(['submittedBy']);
        });
    }

    public function approve(User $actor, DeletionRequest $request, ?string $note = null): DeletionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): DeletionRequest {
            $request = $this->lockPending($request);

            $request->update([
                'status' => DeletionRequest::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note ?: null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'deletion_request_approved',
                'meta' => sprintf('deletion:%d;kind:%s;target:%d', $request->id, $request->kind, $request->requestable_id),
            ]);

            $this->notify(
                $actor,
                'Yêu cầu xóa '.mb_strtolower($request->kindLabel()).' đã được duyệt',
                'Yêu cầu xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'" đã được Giám đốc phê duyệt. HR thực hiện xóa.',
                ['deletion_request_id' => $request->id, 'kind' => $request->kind, 'type' => 'deletion_request_approved']
            );

            return $request->fresh(['submittedBy', 'reviewedBy']);
        });
    }

    public function reject(User $actor, DeletionRequest $request, string $note): DeletionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): DeletionRequest {
            $request = $this->lockPending($request);

            $request->update([
                'status' => DeletionRequest::STATUS_REJECTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'deletion_request_rejected',
                'meta' => sprintf('deletion:%d;kind:%s;target:%d', $request->id, $request->kind, $request->requestable_id),
            ]);

            $this->notify(
                $actor,
                'Yêu cầu xóa '.mb_strtolower($request->kindLabel()).' bị từ chối',
                'Yêu cầu xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'" đã bị Giám đốc từ chối. Lý do: '.$note,
                ['deletion_request_id' => $request->id, 'kind' => $request->kind, 'type' => 'deletion_request_rejected']
            );

            return $request->fresh(['submittedBy', 'reviewedBy']);
        });
    }

    public function execute(User $actor, DeletionRequest $request): DeletionRequest
    {
        return DB::transaction(function () use ($actor, $request): DeletionRequest {
            $request = DeletionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $request->isApproved()) {
                throw new RuntimeException('Chỉ thực hiện xóa yêu cầu đã được Giám đốc duyệt.');
            }

            $this->performDeletion($request);

            $request->update([
                'status' => DeletionRequest::STATUS_APPLIED,
                'applied_by' => $actor->id,
                'applied_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'deletion_request_executed',
                'meta' => sprintf('deletion:%d;kind:%s;target:%d', $request->id, $request->kind, $request->requestable_id),
            ]);

            $this->notify(
                $actor,
                'Đã xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'"',
                $actor->name.' đã thực hiện xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'" theo quyết định của Giám đốc.',
                ['deletion_request_id' => $request->id, 'kind' => $request->kind, 'type' => 'deletion_request_applied']
            );

            return $request->fresh(['submittedBy', 'reviewedBy', 'appliedBy']);
        });
    }

    public function cancel(User $actor, DeletionRequest $request, ?string $note = null): DeletionRequest
    {
        return DB::transaction(function () use ($actor, $request, $note): DeletionRequest {
            $request = DeletionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $request->isPending() && ! $request->isApproved()) {
                throw new RuntimeException('Chỉ hủy được yêu cầu đang chờ duyệt hoặc đã duyệt nhưng chưa xóa.');
            }

            $request->update([
                'status' => DeletionRequest::STATUS_CANCELLED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'cancellation_note' => $note ?: null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'deletion_request_cancelled',
                'meta' => sprintf('deletion:%d;kind:%s;target:%d', $request->id, $request->kind, $request->requestable_id),
            ]);

            $this->notify(
                $actor,
                'Đã hủy yêu cầu xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'"',
                'Yêu cầu xóa '.mb_strtolower($request->kindLabel()).' "'.$request->name.'" đã bị hủy.',
                ['deletion_request_id' => $request->id, 'kind' => $request->kind, 'type' => 'deletion_request_cancelled']
            );

            return $request->fresh(['submittedBy', 'reviewedBy']);
        });
    }

    public static function actorCanView(?User $user): bool
    {
        return $user !== null && ($user->is_hr || $user->is_admin || $user->is_director);
    }

    public static function actorCanManage(?User $user): bool
    {
        return $user !== null && ($user->is_hr || $user->is_admin);
    }

    public static function actorCanApprove(?User $user): bool
    {
        return $user !== null && $user->is_director;
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

    protected function resolveTarget(string $kind, int $targetId): Model
    {
        $target = match ($kind) {
            DeletionRequest::KIND_EMPLOYEE => Employee::find($targetId),
            DeletionRequest::KIND_DEPARTMENT => Department::find($targetId),
            default => throw new RuntimeException('Loại đối tượng xóa không hợp lệ.'),
        };

        if (! $target) {
            throw new RuntimeException($this->label($kind).' cần xóa không còn tồn tại.');
        }

        return $target;
    }

    protected function performDeletion(DeletionRequest $request): void
    {
        if ($request->kind === DeletionRequest::KIND_TRANSFER) {
            $employee = Employee::query()->whereKey($request->requestable_id)->lockForUpdate()->first();
            $targetDepartmentId = (int) data_get($request->payload, 'target_department_id');
            $target = Department::query()->whereKey($targetDepartmentId)->first();

            if (! $employee || ! $target) {
                throw new RuntimeException('Không tìm thấy hồ sơ nhân viên hoặc phòng ban đích để điều chuyển.');
            }

            $fromDepartmentId = $employee->department_id;
            $employee->update(['department_id' => $target->id]);
            self::syncDepartmentCount($fromDepartmentId);
            self::syncDepartmentCount($target->id);

            return;
        }

        if ($request->kind === DeletionRequest::KIND_EMPLOYEE) {
            $employee = Employee::query()->whereKey($request->requestable_id)->lockForUpdate()->first();

            if (! $employee) {
                throw new RuntimeException($this->label($request->kind).' cần xóa không còn tồn tại.');
            }

            $departmentId = $employee->department_id;
            $employee->delete();
            self::syncDepartmentCount($departmentId);

            return;
        }

        $department = Department::query()->whereKey($request->requestable_id)->lockForUpdate()->first();

        if (! $department) {
            throw new RuntimeException($this->label($request->kind).' cần xóa không còn tồn tại.');
        }

        if ($department->employees()->exists()) {
            throw new RuntimeException('Phòng ban "'.$department->name.'" còn nhân viên. Hãy chuyển nhân viên sang phòng ban khác trước khi xóa.');
        }

        $department->delete();
    }

    protected function lockPending(DeletionRequest $request): DeletionRequest
    {
        $request = DeletionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

        if (! $request->isPending()) {
            throw new RuntimeException('Yêu cầu đã được xử lý trước đó.');
        }

        return $request;
    }

    protected function resolveCode(): string
    {
        return 'XOA-'.now()->format('Ymd').'-'.str_pad((string) ((DeletionRequest::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function notify(?User $actor, string $title, string $message, array $data = []): void
    {
        Notification::create([
            'sender_id' => $actor?->id,
            'target' => 'hr',
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => $data,
        ]);
    }

    protected function label(string $kind): string
    {
        return $kind === DeletionRequest::KIND_EMPLOYEE ? 'Nhân viên' : 'Phòng ban';
    }
}
