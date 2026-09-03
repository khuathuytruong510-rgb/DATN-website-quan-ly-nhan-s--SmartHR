<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use App\Models\EmployeePositionHistory;
use App\Models\FaceProfile;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\RequestApprover;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeletionRequestService
{
    public function __construct(protected ContractService $contracts)
    {
    }

    public function submitEmployee(Employee $employee, User $actor, ?string $reason, ?UploadedFile $document, ?string $lastWorkingDay = null): DeletionRequest
    {
        $this->assertEvidence($reason, $document);

        if (RequestApprover::isDirectorProfile($employee)) {
            throw new RuntimeException('HR không quản lý hồ sơ Giám đốc.');
        }
        if ($employee->isTerminated()) {
            throw new RuntimeException('Nhân viên này đã nghỉ việc. Hồ sơ lịch sử vẫn được lưu, không gửi nghỉ việc lại.');
        }

        app(DirectorSuccessionService::class)->assertMayDeleteEmployee($employee);

        if ($this->pendingFor(DeletionRequest::EMPLOYEE, $employee->id)) {
            throw new RuntimeException('Đã có đề nghị nghỉ việc của nhân viên này đang chờ Giám đốc duyệt.');
        }
        if ($this->pendingTransferIdForEmployee($employee->id)) {
            throw new RuntimeException('Nhân viên này đang chờ Giám đốc duyệt chuyển phòng ban.');
        }

        $lastWorkingDay = $this->normalizeLastWorkingDay($lastWorkingDay, $employee);
        $snapshot = $this->snapshotEmployee($employee);
        $snapshot['previous_status'] = $employee->status ?: Employee::STATUS_ACTIVE;
        $snapshot['last_working_day'] = $lastWorkingDay;

        $employee->status = Employee::STATUS_PENDING_TERMINATION;
        $employee->save();

        return $this->store(
            DeletionRequest::EMPLOYEE,
            $employee->id,
            $this->employeeLabel($employee),
            $snapshot,
            $actor,
            $reason,
            $document,
            $employee->user_id,
            $employee->user?->email ?? $employee->email
        );
    }

    public function submitDepartment(Department $department, User $actor, ?string $reason, ?UploadedFile $document): DeletionRequest
    {
        $this->assertEvidence($reason, $document);

        $headcount = $department->employees()->whereIn('status', Employee::workingStatuses())->count();
        if ($department->isBoard()) {
            throw new RuntimeException('Không xóa Ban Giám đốc.');
        }
        if ($headcount > 0) {
            throw new RuntimeException(
                'Còn '.$headcount.' nhân viên đang làm việc trong phòng ban. Hãy điều chuyển họ sang phòng khác hoặc đề nghị nghỉ việc trước khi gửi Giám đốc duyệt xóa phòng ban.'
            );
        }

        if ($this->pendingFor(DeletionRequest::DEPARTMENT, $department->id)) {
            throw new RuntimeException('Đã có yêu cầu xóa phòng ban này đang chờ Giám đốc duyệt.');
        }

        return $this->store(
            DeletionRequest::DEPARTMENT,
            $department->id,
            '['.$department->code.'] '.$department->name,
            [
                'department' => $department->toArray(),
                'employee_count' => 0,
            ],
            $actor,
            $reason,
            $document
        );
    }

    public function approve(DeletionRequest $request, User $director): DeletionRequest
    {
        if (! $director->is_director) {
            throw new RuntimeException('Chỉ Giám đốc được duyệt yêu cầu này.');
        }
        if (! $request->isPending()) {
            throw new RuntimeException('Yêu cầu này đã được xử lý.');
        }

        return DB::transaction(function () use ($request, $director) {
            $request = DeletionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if (! $request->isPending()) {
                throw new RuntimeException('Yêu cầu này đã được xử lý.');
            }

            if ($request->isEmployee()) {
                $this->executeEmployeeTermination($request, $director);
            } elseif ($request->isTransfer()) {
                $this->executeTransfer($request, $director);
            } else {
                $this->executeDepartmentDeletion($request);
            }

            $request->update([
                'status' => DeletionRequest::APPROVED,
                'reviewed_by' => $director->id,
                'reviewed_at' => now(),
                'executed_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $director->id,
                'action' => $request->isTransfer() ? 'transfer_approved' : 'deletion_approved',
                'meta' => $request->isTransfer()
                    ? $this->transferHistorySentence($request->fresh(), $director)
                    : $request->subject_type.':'.$request->subject_label,
            ]);

            $fresh = $request->fresh();
            $this->notifyHrExecuted($fresh, $director);
            if ($fresh->isTransfer()) {
                $this->notifyTransferredEmployees($fresh, $director);
            }
            if ($fresh->isEmployee() && $fresh->account_user_id) {
                $this->notifyAdmin($fresh, $director);
            }

            return $fresh;
        });
    }

    public function reject(DeletionRequest $request, User $director, string $reason): DeletionRequest
    {
        if (! $director->is_director) {
            throw new RuntimeException('Chỉ Giám đốc được từ chối yêu cầu này.');
        }
        if (! $request->isPending()) {
            throw new RuntimeException('Yêu cầu này đã được xử lý.');
        }

        $reason = trim($reason) !== '' ? trim($reason) : 'Giám đốc từ chối yêu cầu.';
        $isTransfer = $request->isTransfer();
        $isEmployee = $request->isEmployee();

        return DB::transaction(function () use ($request, $director, $reason, $isTransfer, $isEmployee) {
            if ($isEmployee && $request->subject_id) {
                $this->restorePendingEmployee($request);
            }

            $request->update([
                'status' => DeletionRequest::REJECTED,
                'reviewed_by' => $director->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            ActivityLog::create([
                'user_id' => $director->id,
                'action' => $isTransfer ? 'transfer_rejected' : 'deletion_rejected',
                'meta' => $request->subject_type.':'.$request->subject_label,
            ]);

            Notification::create([
                'sender_id' => $director->id,
                'target' => 'hr',
                'title' => $isTransfer
                    ? 'Từ chối chuyển nhân viên'
                    : ($isEmployee ? 'Từ chối nghỉ việc' : 'Từ chối xóa phòng ban'),
                'message' => $isTransfer
                    ? $request->subject_label.' không được chuyển. Lý do: '.$reason
                    : ($isEmployee
                        ? $request->subject_label.' tiếp tục làm việc. Lý do: '.$reason
                        : $request->subject_label.' không được xóa. Lý do: '.$reason),
                'is_read' => false,
                'data' => [
                    'type' => $isTransfer ? 'transfer_request' : 'deletion_request',
                    'deletion_request_id' => $request->id,
                ],
            ]);

            return $request->fresh();
        });
    }

    public function markAccountCleared(User $deletedUser, User $admin): void
    {
        DeletionRequest::query()
            ->where('account_user_id', $deletedUser->id)
            ->whereNull('account_cleared_at')
            ->update(['account_cleared_at' => now()]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'account_deleted_after_employee',
            'meta' => $deletedUser->email,
        ]);
    }

    public function pendingFor(string $type, int $id): ?DeletionRequest
    {
        return DeletionRequest::query()
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->where('status', DeletionRequest::PENDING)
            ->first();
    }

    public function transferEmployees(Department $from, Department $to, array $employeeIds, User $actor, ?string $reason, ?UploadedFile $document): DeletionRequest
    {
        $this->assertEvidence($reason, $document);

        if ($from->is($to)) {
            throw new RuntimeException('Phòng ban đích phải khác phòng ban hiện tại.');
        }

        if ($this->pendingFor(DeletionRequest::DEPARTMENT, $to->id)) {
            throw new RuntimeException('Không chuyển vào phòng ban đang chờ Giám đốc duyệt xóa.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if ($ids === []) {
            throw new RuntimeException('Chọn ít nhất một nhân viên để chuyển sang phòng ban khác.');
        }

        $employees = Employee::query()
            ->with(['department', 'positionDetail', 'user'])
            ->where('department_id', $from->id)
            ->whereIn('id', $ids)
            ->whereIn('status', Employee::workingStatuses())
            ->orderBy('name')
            ->get()
            ->reject(fn (Employee $row) => RequestApprover::isDirectorProfile($row))
            ->values();

        if ($employees->isEmpty()) {
            throw new RuntimeException('Không có nhân viên hợp lệ để điều chuyển (Giám đốc không thuộc luồng này).');
        }

        $busy = $this->pendingTransferMap($employees->pluck('id')->all());
        $blockedDelete = DeletionRequest::query()
            ->where('subject_type', DeletionRequest::EMPLOYEE)
            ->where('status', DeletionRequest::PENDING)
            ->whereIn('subject_id', $employees->pluck('id'))
            ->pluck('subject_id')
            ->all();

        $employees = $employees->reject(
            fn (Employee $row) => isset($busy[$row->id])
                || in_array($row->id, $blockedDelete, true)
                || RequestApprover::isDirectorProfile($row)
        );
        if ($employees->isEmpty()) {
            throw new RuntimeException('Các nhân viên đã chọn đang chờ Giám đốc duyệt, thuộc hồ sơ Giám đốc, hoặc không chuyển được.');
        }

        $ids = $employees->pluck('id')->map(fn ($id) => (int) $id)->all();
        $label = $employees->count().' NV: ['.$from->code.'] '.$from->name.' → ['.$to->code.'] '.$to->name;

        return $this->store(
            DeletionRequest::TRANSFER,
            $from->id,
            $label,
            [
                'from' => $from->only(['id', 'name', 'code']),
                'to' => $to->only(['id', 'name', 'code']),
                'employee_ids' => $ids,
                'employees' => $employees->map(fn (Employee $row) => $row->only(['id', 'name', 'email', 'employee_code', 'position']))->all(),
            ],
            $actor,
            $reason,
            $document
        );
    }

    public function pendingTransferMap(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $map = [];
        $rows = DeletionRequest::query()
            ->where('subject_type', DeletionRequest::TRANSFER)
            ->where('status', DeletionRequest::PENDING)
            ->get(['id', 'snapshot']);

        foreach ($rows as $row) {
            foreach ((array) data_get($row->snapshot, 'employee_ids', []) as $id) {
                $id = (int) $id;
                if (in_array($id, $employeeIds, true)) {
                    $map[$id] = $row->id;
                }
            }
        }

        return $map;
    }

    public function pendingTransferIdForEmployee(int $employeeId): ?int
    {
        return $this->pendingTransferMap([$employeeId])[$employeeId] ?? null;
    }

    private function store(
        string $type,
        int $subjectId,
        string $label,
        array $snapshot,
        User $actor,
        ?string $reason,
        ?UploadedFile $document,
        ?int $accountUserId = null,
        ?string $accountEmail = null,
    ): DeletionRequest {
        $path = null;
        $name = null;
        if ($document) {
            $path = $document->store('deletion-documents', 'public');
            $name = $document->getClientOriginalName();
        }

        return DB::transaction(function () use ($type, $subjectId, $label, $snapshot, $actor, $reason, $path, $name, $accountUserId, $accountEmail) {
            $request = DeletionRequest::create([
                'subject_type' => $type,
                'subject_id' => $subjectId,
                'subject_label' => $label,
                'snapshot' => $snapshot,
                'reason' => $reason,
                'document_path' => $path,
                'document_name' => $name,
                'status' => DeletionRequest::PENDING,
                'requested_by' => $actor->id,
                'account_user_id' => $accountUserId,
                'account_email' => $accountEmail,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => $type === DeletionRequest::TRANSFER ? 'transfer_requested' : 'deletion_requested',
                'meta' => $type.':'.$label,
            ]);

            $isTransfer = $type === DeletionRequest::TRANSFER;
            $isEmployee = $type === DeletionRequest::EMPLOYEE;
            Notification::create([
                'sender_id' => $actor->id,
                'target' => 'director',
                'title' => $isTransfer
                    ? 'Yêu cầu chuyển nhân viên cần duyệt'
                    : ($isEmployee ? 'Đề nghị nghỉ việc cần duyệt' : 'Yêu cầu xóa phòng ban cần duyệt'),
                'message' => $isTransfer
                    ? sprintf(
                        'HR đề nghị chuyển nhân viên: %s. %s Vui lòng xem rồi duyệt.',
                        $label,
                        $reason ? 'Lý do: '.$reason.'.' : 'Đã đính kèm biên bản/tài liệu.'
                    )
                    : ($isEmployee
                        ? sprintf(
                            'HR đề nghị chấm dứt quan hệ lao động với “%s”. %s Vui lòng xem lý do/biên bản rồi duyệt.',
                            $label,
                            $reason ? 'Lý do: '.$reason.'.' : 'Đã đính kèm biên bản/tài liệu.'
                        )
                        : sprintf(
                            'HR đề nghị xóa phòng ban “%s”. %s Vui lòng xem lý do/biên bản rồi duyệt.',
                            $label,
                            $reason ? 'Lý do: '.$reason.'.' : 'Đã đính kèm biên bản/tài liệu.'
                        )),
                'is_read' => false,
                'data' => [
                    'type' => $isTransfer ? 'transfer_request' : 'deletion_request',
                    'deletion_request_id' => $request->id,
                ],
            ]);

            return $request;
        });
    }

    private function executeEmployeeTermination(DeletionRequest $request, User $director): void
    {
        $employee = Employee::with('user', 'department')->find($request->subject_id);
        if (! $employee) {
            throw new RuntimeException('Không tìm thấy hồ sơ nhân viên.');
        }
        if ($employee->isTerminated()) {
            throw new RuntimeException('Nhân viên này đã nghỉ việc.');
        }

        app(DirectorSuccessionService::class)->assertMayDeleteEmployee($employee);

        $lastWorkingDay = $this->normalizeLastWorkingDay(
            data_get($request->snapshot, 'last_working_day'),
            $employee
        );

        $this->contracts->terminateForEmployeeDeletion($employee, $director, $request->reason, $lastWorkingDay);
        $this->closePositionOnTermination($employee, $director, $lastWorkingDay, $request->reason);

        $settlement = $this->buildSettlement($employee, $lastWorkingDay);
        $day = Carbon::parse($lastWorkingDay);
        $settlement['final_payroll'] = [
            'month' => (int) $day->month,
            'year' => (int) $day->year,
            'status' => 'ready_for_accountant',
            'note' => sprintf(
                'Đã chốt dữ liệu công/OT/phép đến %s. Kế toán tính lương cuối kỳ %02d/%d trên dữ liệu này; nhân viên đã nghỉ không vào kỳ sau.',
                $day->format('d/m/Y'),
                $day->month,
                $day->year
            ),
        ];

        $user = $employee->user;
        if ($user) {
            $user->is_locked = true;
            $user->save();
        }

        $employee->status = Employee::STATUS_TERMINATED;
        $employee->terminated_at = $lastWorkingDay;
        $employee->save();

        $this->syncEmployeeCount($employee->department_id);

        $snapshot = $this->snapshotEmployee($employee->fresh(['user', 'department']), true);
        $snapshot['previous_status'] = data_get($request->snapshot, 'previous_status', Employee::STATUS_ACTIVE);
        $snapshot['last_working_day'] = $lastWorkingDay;
        $snapshot['settlement'] = $settlement;

        $request->snapshot = $snapshot;
        $request->account_user_id = $employee->user_id ?: $request->account_user_id;
        $request->account_email = $user?->email ?? $employee->email;
        $request->account_cleared_at = now();
        $request->save();
    }

    private function executeDepartmentDeletion(DeletionRequest $request): void
    {
        $department = Department::find($request->subject_id);
        if (! $department) {
            throw new RuntimeException('Không tìm thấy phòng ban để xóa. Có thể đã bị xóa.');
        }
        if ($department->employees()->whereIn('status', Employee::workingStatuses())->exists()) {
            throw new RuntimeException('Phòng ban vẫn còn nhân viên đang làm việc. Hãy điều chuyển hoặc hoàn tất nghỉ việc trước.');
        }

        $request->snapshot = ['department' => $department->toArray(), 'employee_count' => 0];
        $request->subject_id = null;
        $request->save();

        $department->delete();
    }

    private function executeTransfer(DeletionRequest $request, User $director): void
    {
        $fromId = (int) data_get($request->snapshot, 'from.id', $request->subject_id);
        $toId = (int) data_get($request->snapshot, 'to.id');
        $ids = array_map('intval', (array) data_get($request->snapshot, 'employee_ids', []));

        $to = Department::find($toId);
        if (! $to) {
            throw new RuntimeException('Không tìm thấy phòng ban đích để chuyển.');
        }
        if ($this->pendingFor(DeletionRequest::DEPARTMENT, $to->id)) {
            throw new RuntimeException('Phòng ban đích đang chờ duyệt xóa, không chuyển được.');
        }

        $employees = Employee::query()
            ->where('department_id', $fromId)
            ->whereIn('id', $ids)
            ->whereIn('status', Employee::workingStatuses())
            ->get();

        $moved = 0;
        foreach ($employees as $employee) {
            $employee->update(['department_id' => $to->id]);
            $this->recordTransferHistory($employee, $fromId, $to, $director, $request->reason);
            $moved++;
        }

        if ($moved === 0) {
            throw new RuntimeException('Nhân viên không còn ở phòng ban hiện tại, không chuyển được.');
        }

        $this->syncEmployeeCount($fromId);
        $this->syncEmployeeCount($to->id);

        $from = data_get($request->snapshot, 'from', []);
        $snapshot = $request->snapshot ?? [];
        $snapshot['moved_count'] = $moved;
        $snapshot['executed_to'] = $to->only(['id', 'name', 'code']);
        $snapshot['feedback'] = $snapshot['feedback'] ?? [];
        $snapshot['history'] = [
            'title' => 'Điều chuyển nhân viên',
            'from' => $from,
            'to' => $to->only(['id', 'name', 'code']),
            'approved_by_id' => $director->id,
            'approved_by' => $director->name,
            'approved_at' => now()->toDateTimeString(),
            'reason' => $request->reason,
            'employees' => $employees->map(fn (Employee $row) => $row->only(['id', 'name', 'email', 'employee_code']))->all(),
        ];
        $request->snapshot = $snapshot;
        $request->save();
    }

    private function recordTransferHistory(Employee $employee, int $fromId, Department $to, User $director, ?string $reason): void
    {
        $today = now()->toDateString();
        $from = Department::find($fromId);

        $closed = EmployeePositionHistory::query()
            ->where('employee_id', $employee->id)
            ->where('is_director_role', false)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->get();

        $closed->each(function (EmployeePositionHistory $row) use ($today, $reason) {
            $row->update([
                'ended_at' => $today,
                'status' => EmployeePositionHistory::STATUS_ENDED,
                'end_reason' => EmployeePositionHistory::REASON_TRANSFER,
                'note' => $reason ?: $row->note,
            ]);
        });

        if ($closed->isEmpty() && $from) {
            $started = optional($employee->created_at)->toDateString() ?: $today;
            EmployeePositionHistory::create([
                'employee_id' => $employee->id,
                'user_id' => $employee->user_id,
                'holder_name' => $employee->name,
                'holder_email' => $employee->email,
                'position_id' => $employee->position_id,
                'position_name' => $employee->position ?: 'Nhân viên',
                'department_id' => $from->id,
                'department_name' => $from->name,
                'started_at' => $started > $today ? $today : $started,
                'ended_at' => $today,
                'end_reason' => EmployeePositionHistory::REASON_TRANSFER,
                'is_director_role' => false,
                'status' => EmployeePositionHistory::STATUS_ENDED,
                'note' => $reason,
                'created_by' => $director->id,
            ]);
        }

        EmployeePositionHistory::create([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'holder_name' => $employee->name,
            'holder_email' => $employee->email,
            'position_id' => $employee->position_id,
            'position_name' => $employee->position ?: 'Nhân viên',
            'department_id' => $to->id,
            'department_name' => $to->name,
            'started_at' => $today,
            'ended_at' => null,
            'end_reason' => null,
            'is_director_role' => false,
            'status' => EmployeePositionHistory::STATUS_HOLDING,
            'note' => trim(sprintf(
                'Điều chuyển nhân viên. Từ: %s. Đến: %s. Người duyệt: %s. Ngày duyệt: %s. Lý do: %s',
                $from?->name ?: '—',
                $to->name,
                $director->name,
                now()->format('d/m/Y'),
                $reason ?: '—'
            )),
            'created_by' => $director->id,
        ]);
    }

    private function transferHistorySentence(DeletionRequest $request, User $director): string
    {
        $names = collect(data_get($request->snapshot, 'employees', []))
            ->pluck('name')
            ->filter()
            ->implode(', ');
        $from = data_get($request->snapshot, 'from.name') ?: '—';
        $to = data_get($request->snapshot, 'to.name') ?: '—';

        return sprintf(
            'Điều chuyển nhân viên. Nhân viên: %s. Từ: %s. Đến: %s. Người duyệt: %s. Ngày duyệt: %s. Lý do: %s',
            $names !== '' ? $names : $request->subject_label,
            $from,
            $to,
            $director->name,
            now()->format('d/m/Y'),
            $request->reason ?: '—'
        );
    }

    public function submitTransferFeedback(DeletionRequest $request, Employee $employee, User $actor, bool $agree, string $message): DeletionRequest
    {
        if (! $request->isTransfer() || $request->status !== DeletionRequest::APPROVED) {
            throw new RuntimeException('Chỉ phản hồi được sau khi Giám đốc đã duyệt điều chuyển.');
        }

        $ids = array_map('intval', (array) data_get($request->snapshot, 'employee_ids', []));
        if (! in_array((int) $employee->id, $ids, true)) {
            throw new RuntimeException('Bạn không thuộc đợt điều chuyển này.');
        }

        if ($request->feedbackFor($employee->id)) {
            throw new RuntimeException('Bạn đã gửi phản hồi cho đợt điều chuyển này.');
        }

        $message = trim($message);
        if (! $agree && $message === '') {
            throw new RuntimeException('Hãy nêu ý kiến hoặc lý do không đồng ý để HR giải quyết.');
        }

        $snapshot = $request->snapshot ?? [];
        $feedback = $snapshot['feedback'] ?? [];
        $feedback[(string) $employee->id] = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_code' => $employee->employee_code,
            'agree' => $agree,
            'message' => $message !== '' ? $message : ($agree ? 'Đã nắm thông tin điều chuyển.' : ''),
            'submitted_at' => now()->toDateTimeString(),
            'status' => 'pending',
            'hr_reply' => null,
            'hr_replied_at' => null,
            'hr_replied_by' => null,
        ];
        $snapshot['feedback'] = $feedback;
        $request->snapshot = $snapshot;
        $request->save();

        ActivityLog::create([
            'user_id' => $actor->id,
            'action' => 'transfer_feedback',
            'meta' => $request->subject_label,
        ]);

        $from = data_get($snapshot, 'from.name', 'phòng cũ');
        $to = data_get($snapshot, 'to.name', 'phòng mới');
        Notification::create([
            'sender_id' => $actor->id,
            'target' => 'hr',
            'title' => $agree ? 'Phản hồi điều chuyển nhân sự' : 'Không đồng ý điều chuyển nhân sự',
            'message' => sprintf(
                '%s phản hồi đợt chuyển từ %s sang %s: %s',
                $employee->name,
                $from,
                $to,
                $feedback[(string) $employee->id]['message']
            ),
            'is_read' => false,
            'data' => [
                'type' => 'transfer_feedback',
                'deletion_request_id' => $request->id,
                'employee_id' => $employee->id,
            ],
        ]);

        return $request->fresh();
    }

    public function replyTransferFeedback(DeletionRequest $request, int $employeeId, User $hr, string $reply): DeletionRequest
    {
        if (! $hr->canManageHr()) {
            throw new RuntimeException('Chỉ HR được trả lời phản hồi điều chuyển.');
        }
        if (! $request->isTransfer()) {
            throw new RuntimeException('Đây không phải yêu cầu chuyển phòng ban.');
        }

        $snapshot = $request->snapshot ?? [];
        $key = (string) $employeeId;
        $entry = $snapshot['feedback'][$key] ?? $snapshot['feedback'][$employeeId] ?? null;
        if (! is_array($entry)) {
            throw new RuntimeException('Chưa có phản hồi của nhân viên này.');
        }

        $reply = trim($reply);
        if ($reply === '') {
            throw new RuntimeException('Nhập nội dung giải quyết gửi lại nhân viên.');
        }

        $entry['hr_reply'] = $reply;
        $entry['hr_replied_at'] = now()->toDateTimeString();
        $entry['hr_replied_by'] = $hr->name;
        $entry['status'] = 'resolved';
        $snapshot['feedback'][$key] = $entry;
        unset($snapshot['feedback'][$employeeId]);
        $request->snapshot = $snapshot;
        $request->save();

        ActivityLog::create([
            'user_id' => $hr->id,
            'action' => 'transfer_feedback_reply',
            'meta' => ($entry['employee_name'] ?? '#'.$employeeId).' — '.$request->subject_label,
        ]);

        HrApprovalNotifier::send(
            $employeeId,
            $hr,
            'HR đã phản hồi điều chuyển nhân sự',
            'HR: '.$reply,
            [
                'type' => 'transfer_feedback_reply',
                'deletion_request_id' => $request->id,
            ]
        );

        return $request->fresh();
    }

    private function notifyTransferredEmployees(DeletionRequest $request, User $director): void
    {
        $from = data_get($request->snapshot, 'from.name', 'phòng ban cũ');
        $to = data_get($request->snapshot, 'to.name', 'phòng ban mới');
        $fromCode = data_get($request->snapshot, 'from.code');
        $toCode = data_get($request->snapshot, 'to.code');
        $ids = array_map('intval', (array) data_get($request->snapshot, 'employee_ids', []));
        $reason = $request->reason ? ' Lý do: '.$request->reason.'.' : '';

        foreach ($ids as $employeeId) {
            if (! Employee::whereKey($employeeId)->exists()) {
                continue;
            }

            HrApprovalNotifier::send(
                $employeeId,
                $director,
                'Thông báo điều chuyển nhân sự',
                sprintf(
                    'Bạn đã được chuyển từ [%s] %s sang [%s] %s.%s Nếu có ý kiến hoặc không đồng ý, hãy gửi phản hồi để bộ phận Nhân sự giải quyết.',
                    $fromCode,
                    $from,
                    $toCode,
                    $to,
                    $reason
                ),
                [
                    'type' => 'transfer_notice',
                    'deletion_request_id' => $request->id,
                ]
            );
        }
    }

    private function notifyHrExecuted(DeletionRequest $request, User $director): void
    {
        $isTransfer = $request->isTransfer();
        $isEmployee = $request->isEmployee();
        $settlementNote = data_get($request->snapshot, 'settlement.final_payroll.note');
        Notification::create([
            'sender_id' => $director->id,
            'target' => 'hr',
            'title' => $isTransfer
                ? 'Đã chuyển nhân viên'
                : ($isEmployee ? 'Đã duyệt nghỉ việc' : 'Đã xóa phòng ban'),
            'message' => $isTransfer
                ? sprintf('Giám đốc đã duyệt. %s đã được chuyển.', $request->subject_label)
                : ($isEmployee
                    ? trim(sprintf(
                        'Giám đốc đã duyệt nghỉ việc “%s”. Hồ sơ, hợp đồng, chấm công và lương được giữ lại. Tài khoản đăng nhập đã khóa.%s',
                        $request->subject_label,
                        $settlementNote ? ' '.$settlementNote : ''
                    ))
                    : sprintf('Giám đốc đã duyệt. “%s” đã được xóa và lưu vào lịch sử.', $request->subject_label)),
            'is_read' => false,
            'data' => [
                'type' => $isTransfer ? 'transfer_request' : 'deletion_request',
                'deletion_request_id' => $request->id,
            ],
        ]);
    }

    private function notifyAdmin(DeletionRequest $request, User $director): void
    {
        Notification::create([
            'sender_id' => $director->id,
            'target' => 'admin',
            'title' => 'Đã khóa tài khoản sau khi nhân viên nghỉ việc',
            'message' => sprintf(
                'Hồ sơ “%s” đã nghỉ việc. Tài khoản %s đã bị khóa đăng nhập. Không xóa dữ liệu lịch sử (hợp đồng, chấm công, lương).',
                $request->subject_label,
                $request->account_email ?: ('#'.$request->account_user_id)
            ),
            'is_read' => false,
            'data' => [
                'type' => 'account_deletion',
                'deletion_request_id' => $request->id,
                'account_user_id' => $request->account_user_id,
            ],
        ]);
    }

    private function snapshotEmployee(Employee $employee, bool $withRelated = false): array
    {
        $employee->loadMissing('department', 'user');
        $id = $employee->id;
        $data = [
            'employee' => $employee->toArray(),
            'department' => $employee->department?->only(['id', 'name', 'code']),
            'account' => $employee->user?->only(['id', 'name', 'email', 'is_hr', 'is_accountant', 'is_director']),
            'contracts' => Contract::where('employee_id', $id)->orderByDesc('id')->get()->toArray(),
        ];

        if (! $withRelated) {
            return $data;
        }

        $data['related'] = [
            'contracts' => $data['contracts'],
            'attendances' => Attendance::where('employee_id', $id)->get()->toArray(),
            'payrolls' => Payroll::where('employee_id', $id)->get()->toArray(),
            'salary_payments' => SalaryPayment::where('employee_id', $id)->get()->toArray(),
            'leave_requests' => LeaveRequest::where('employee_id', $id)->get()->toArray(),
            'overtime_requests' => OvertimeRequest::where('employee_id', $id)->get()->toArray(),
            'evaluations' => EmployeeEvaluation::where('employee_id', $id)->get()->toArray(),
            'benefits' => EmployeeBenefit::where('employee_id', $id)->get()->toArray(),
            'support_requests' => SupportRequest::where('employee_id', $id)->get()->toArray(),
            'face_profile' => FaceProfile::where('employee_id', $id)->get(['id', 'status', 'created_at'])->toArray(),
        ];
        $data['related_counts'] = collect($data['related'])->map(fn ($rows) => is_array($rows) ? count($rows) : 0)->all();

        return $data;
    }

    private function employeeLabel(Employee $employee): string
    {
        $code = $employee->employee_code ? $employee->employee_code.' — ' : '';

        return $code.$employee->name;
    }

    private function assertEvidence(?string $reason, ?UploadedFile $document): void
    {
        if (blank($reason) && ! $document) {
            throw new RuntimeException('Cần nhập lý do hoặc đính kèm biên bản/tài liệu để Giám đốc duyệt.');
        }
    }

    private function syncEmployeeCount(?int $departmentId): void
    {
        if (! $departmentId) {
            return;
        }

        Department::whereKey($departmentId)->update([
            'employee_count' => Employee::where('department_id', $departmentId)
                ->whereIn('status', Employee::workingStatuses())
                ->count(),
        ]);
    }

    private function restorePendingEmployee(DeletionRequest $request): void
    {
        $employee = Employee::find($request->subject_id);
        if (! $employee || ! $employee->isPendingTermination()) {
            return;
        }

        $previous = data_get($request->snapshot, 'previous_status', Employee::STATUS_ACTIVE);
        if (! in_array($previous, [Employee::STATUS_ACTIVE, Employee::STATUS_ON_LEAVE], true)) {
            $previous = Employee::STATUS_ACTIVE;
        }

        $employee->status = $previous;
        $employee->save();
    }

    private function normalizeLastWorkingDay(?string $lastWorkingDay, Employee $employee): string
    {
        try {
            $day = $lastWorkingDay ? Carbon::parse($lastWorkingDay) : now();
        } catch (\Throwable) {
            $day = now();
        }

        if ($employee->start_date && $day->lt($employee->start_date)) {
            $day = $employee->start_date->copy();
        }

        return $day->toDateString();
    }

    private function buildSettlement(Employee $employee, string $lastWorkingDay): array
    {
        $day = Carbon::parse($lastWorkingDay);
        $id = $employee->id;

        return [
            'last_working_day' => $day->toDateString(),
            'attendance_days' => Attendance::where('employee_id', $id)->whereDate('date', '<=', $day)->count(),
            'attendance_days_in_final_month' => Attendance::where('employee_id', $id)
                ->whereYear('date', $day->year)
                ->whereMonth('date', $day->month)
                ->whereDate('date', '<=', $day)
                ->count(),
            'overtime_requests' => OvertimeRequest::where('employee_id', $id)->count(),
            'leave_requests' => LeaveRequest::where('employee_id', $id)->count(),
            'leave_balance' => $employee->leave_balance,
        ];
    }

    private function closePositionOnTermination(Employee $employee, User $director, string $lastWorkingDay, ?string $reason): void
    {
        EmployeePositionHistory::query()
            ->where('employee_id', $employee->id)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->get()
            ->each(function (EmployeePositionHistory $row) use ($lastWorkingDay, $reason) {
                $row->update([
                    'ended_at' => $lastWorkingDay,
                    'status' => EmployeePositionHistory::STATUS_ENDED,
                    'end_reason' => EmployeePositionHistory::REASON_TERMINATION,
                    'note' => $reason ?: $row->note,
                ]);
            });
    }
}
