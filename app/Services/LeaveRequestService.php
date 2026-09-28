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
use Illuminate\Support\Facades\Storage;
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
        $isSpouseBirth = ($data['type'] ?? null) === LeaveTypes::SPOUSE_BIRTH;
        $childrenCount = $isSpouseBirth ? (int) ($data['children_count'] ?? 0) : null;
        $birthComplication = $isSpouseBirth && (bool) ($data['birth_complication'] ?? false);
        $isSecondChild = $isSpouseBirth && (bool) ($data['is_second_child'] ?? false);
        $halfDay = ! $isSpouseBirth && (bool) ($data['half_day'] ?? false);

        if ($isSpouseBirth) {
            $schedule = $this->eligibility->spouseBirthSchedule(
                $data['start_date'],
                $childrenCount,
                $birthComplication,
                $isSecondChild
            );
            $data['end_date'] = $schedule['end_date'];
            $data['half_day'] = false;
            $data['reason'] = 'Vợ sinh con';
        }

        $check = $this->eligibility->assertEligible(
            $employee,
            $data['type'],
            $data['start_date'],
            $data['end_date'],
            $halfDay,
            null,
            $childrenCount,
            $birthComplication,
            $isSecondChild
        );

        $this->periodLock->assertWritableRange($data['start_date'], $data['end_date'], 'đơn nghỉ phép');
        $document = $data['document'] ?? null;
        $documentPath = $document?->store('leave-evidence', 'local');

        try {
            return DB::transaction(function () use ($employee, $actor, $data, $check, $isSpouseBirth, $childrenCount, $birthComplication, $isSecondChild, $document, $documentPath) {
                $leave = LeaveRequest::create([
                    'employee_id' => $employee->id,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'half_day' => (bool) ($data['half_day'] ?? false),
                    'type' => $data['type'],
                    'children_count' => $isSpouseBirth ? $childrenCount : null,
                    'birth_complication' => $isSpouseBirth ? $birthComplication : null,
                    'is_second_child' => $isSpouseBirth ? $isSecondChild : null,
                    'document_path' => $documentPath,
                    'document_name' => $document?->getClientOriginalName(),
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
        } catch (\Throwable $e) {
            if ($documentPath) {
                Storage::disk('local')->delete($documentPath);
            }

            throw $e;
        }
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
            $isSpouseBirthOffDay = $leave->type === LeaveTypes::SPOUSE_BIRTH
                && ! $this->eligibility->isSpouseBirthWorkingDay($cursor);
            if (! $isSpouseBirthOffDay && ($leave->type === LeaveTypes::SPOUSE_BIRTH || ! $cursor->isSunday())) {
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
