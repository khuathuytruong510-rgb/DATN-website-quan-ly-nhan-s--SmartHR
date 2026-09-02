<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\LeaveTypes;
use App\Support\RequestApprover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(
        private LeaveEligibilityService $eligibility,
        private PayrollPeriodLockService $periodLock,
    ) {
    }

    public function submit(Employee $employee, User $actor, array $data): LeaveRequest
    {
        $halfDay = (bool) ($data['half_day'] ?? false);
        $check = $this->eligibility->assertEligible(
            $employee,
            $data['type'],
            $data['start_date'],
            $data['end_date'],
            $halfDay
        );

        $this->periodLock->assertWritableRange($data['start_date'], $data['end_date'], 'đơn nghỉ phép');

        return DB::transaction(function () use ($employee, $actor, $data, $check) {
            $leave = LeaveRequest::create([
                'employee_id' => $employee->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'half_day' => (bool) ($data['half_day'] ?? false),
                'type' => $data['type'],
                'reason' => $data['reason'] ?? null,
                'is_urgent' => (bool) ($data['is_urgent'] ?? false),
                'urgent_reason' => $data['urgent_reason'] ?? null,
                'days' => $check['days'],
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'cancelled_by' => null,
                'cancelled_at' => null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'leave_submitted',
                'meta' => sprintf('%s → %s', $data['start_date'], $data['end_date']),
            ]);

            $this->notifyApprovers($leave, $actor, $employee);

            return $leave;
        });
    }

    public function approve(LeaveRequest $leave, User $hr): LeaveRequest
    {
        $leave->loadMissing('employee');
        if (! RequestApprover::canReview($hr, $leave->employee)) {
            throw new \RuntimeException(
                RequestApprover::needsDirector($leave->employee)
                    ? 'Đơn nghỉ phép của HR do Giám đốc duyệt.'
                    : 'Chỉ HR được duyệt nghỉ phép của nhân viên.'
            );
        }
        if ($leave->status !== 'pending') {
            throw new \RuntimeException('Chỉ duyệt đơn đang chờ duyệt.');
        }

        $this->periodLock->assertWritableRange($leave->start_date, $leave->end_date, 'đơn nghỉ phép');

        return DB::transaction(function () use ($leave, $hr) {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $hr->id,
                'approved_at' => now(),
            ]);

            $this->syncAttendance($leave->fresh());
            HrApprovalNotifier::approved($leave->employee_id, $hr, 'Đơn nghỉ phép', [
                'type' => 'leave_request',
                'leave_request_id' => $leave->id,
            ]);

            ActivityLog::create([
                'user_id' => $hr->id,
                'action' => 'leave_approved',
                'meta' => 'leave:'.$leave->id,
            ]);

            return $leave->fresh();
        });
    }

    public function reject(LeaveRequest $leave, User $hr, string $reason): LeaveRequest
    {
        $leave->loadMissing('employee');
        if (! RequestApprover::canReview($hr, $leave->employee)) {
            throw new \RuntimeException(
                RequestApprover::needsDirector($leave->employee)
                    ? 'Đơn nghỉ phép của HR do Giám đốc duyệt.'
                    : 'Chỉ HR được từ chối nghỉ phép của nhân viên.'
            );
        }
        if ($leave->status !== 'pending') {
            throw new \RuntimeException('Chỉ từ chối đơn đang chờ duyệt.');
        }

        $this->periodLock->assertWritableRange($leave->start_date, $leave->end_date, 'đơn nghỉ phép');

        return DB::transaction(function () use ($leave, $hr, $reason) {
            $leave->update([
                'status' => 'rejected',
                'approved_by' => $hr->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            HrApprovalNotifier::rejected($leave->employee_id, $hr, 'Đơn nghỉ phép', $reason, [
                'type' => 'leave_request',
                'leave_request_id' => $leave->id,
            ]);

            return $leave->fresh();
        });
    }

    public function revertApprovedAttendance(LeaveRequest $leave): void
    {
        Attendance::query()
            ->where('employee_id', $leave->employee_id)
            ->where('notes', 'leave:'.$leave->id)
            ->delete();
    }

    private function syncAttendance(LeaveRequest $leave): void
    {
        $cursor = Carbon::parse($leave->start_date)->startOfDay();
        $end = Carbon::parse($leave->end_date)->startOfDay();

        while ($cursor->lte($end)) {
            if (! $cursor->isSunday()) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $leave->employee_id,
                        'date' => $cursor->toDateString(),
                    ],
                    [
                        'status' => 'leave',
                        'notes' => 'leave:'.$leave->id,
                    ]
                );
            }
            $cursor->addDay();
        }
    }

    private function notifyApprovers(LeaveRequest $leave, User $actor, Employee $employee): void
    {
        RequestApprover::notifyQueue(
            $employee,
            $actor,
            'Đơn nghỉ phép cần duyệt',
            sprintf(
                '%s xin %s %s ngày, từ %s đến %s. Vui lòng duyệt.',
                $employee->name,
                LeaveTypes::label($leave->type),
                $leave->days,
                optional($leave->start_date)->format('d/m/Y'),
                optional($leave->end_date)->format('d/m/Y')
            ),
            [
                'type' => 'leave_request',
                'leave_request_id' => $leave->id,
            ]
        );
    }

}
