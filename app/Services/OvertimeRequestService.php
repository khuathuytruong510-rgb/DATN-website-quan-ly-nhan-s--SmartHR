<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\RequestApprover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OvertimeRequestService
{
    public function __construct(private OvertimeActualCalculator $calculator)
    {
    }

    public function submit(Employee $employee, User $actor, array $data): OvertimeRequest
    {
        $date = Carbon::parse($data['date'])->startOfDay();
        $today = now()->startOfDay();
        $max = $today->copy()->addDays((int) config('overtime.employee_max_days_ahead', 1));
        if ($date->lt($today)) {
            throw new RuntimeException('Không được đăng ký tăng ca trong quá khứ.');
        }
        if ($date->gt($max)) {
            throw new RuntimeException('Nhân viên chỉ được đăng ký tăng ca hôm nay hoặc ngày mai.');
        }

        $this->assertNoActiveRequest($employee, $date->toDateString());

        return DB::transaction(function () use ($employee, $actor, $data) {
            $start = $data['start_time'];
            $end = $data['end_time'];
            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'source' => OvertimeRequest::SOURCE_REQUESTED,
                'date' => $data['date'],
                'start_time' => $start,
                'end_time' => $end,
                'requested_start' => $start,
                'requested_end' => $end,
                'reason' => $data['reason'] ?? null,
                'status' => OvertimeRequest::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'overtime_submitted',
                'meta' => ($data['date'] ?? '').' '.$start.'–'.$end,
            ]);

            RequestApprover::notifyQueue(
                $employee,
                $actor,
                'Đăng ký tăng ca cần duyệt',
                sprintf(
                    '%s đăng ký tăng ca ngày %s, từ %s đến %s. Vui lòng duyệt.',
                    $employee->name,
                    $overtime->date?->format('d/m/Y') ?? $data['date'],
                    $start,
                    $end
                ),
                [
                    'type' => 'overtime_request',
                    'overtime_request_id' => $overtime->id,
                ]
            );

            return $overtime;
        });
    }

    public function assign(User $actor, array $data): OvertimeRequest
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được chỉ định tăng ca.');
        }

        $employee = Employee::findOrFail($data['employee_id']);
        if (! RequestApprover::hrMayManage($actor, $employee)) {
            throw new RuntimeException('HR không chỉ định tăng ca cho Giám đốc.');
        }
        if (RequestApprover::needsDirector($employee)) {
            throw new RuntimeException('Không chỉ định tăng ca cho chính HR. HR đăng ký và Giám đốc duyệt.');
        }

        $date = Carbon::parse($data['date'])->startOfDay();
        if ($date->lt(now()->startOfDay())) {
            throw new RuntimeException('Không chỉ định tăng ca trong quá khứ.');
        }

        $this->assertNoActiveRequest($employee, $date->toDateString());

        $start = $data['start_time'];
        $end = $data['end_time'];

        return DB::transaction(function () use ($actor, $employee, $data, $start, $end) {
            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'source' => OvertimeRequest::SOURCE_ASSIGNED,
                'assigned_by' => $actor->id,
                'date' => $data['date'],
                'start_time' => $start,
                'end_time' => $end,
                'requested_start' => $start,
                'requested_end' => $end,
                'approved_start' => $start,
                'approved_end' => $end,
                'reason' => $data['reason'] ?? null,
                'status' => OvertimeRequest::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            HrApprovalNotifier::send(
                (int) $employee->id,
                $actor,
                'HR chỉ định tăng ca',
                sprintf(
                    'HR chỉ định tăng ca ngày %s, từ %s đến %s. Check-out muộn ngoài khung này không tự tính OT.',
                    $overtime->date?->format('d/m/Y') ?? $data['date'],
                    $start,
                    $end
                ),
                [
                    'type' => 'overtime_request',
                    'overtime_request_id' => $overtime->id,
                ]
            );

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'overtime_assigned',
                'meta' => 'overtime:'.$overtime->id,
            ]);

            return $overtime;
        });
    }

    public function approve(OvertimeRequest $overtime, User $actor, array $window = []): OvertimeRequest
    {
        $overtime->loadMissing('employee');
        if (! RequestApprover::canReview($actor, $overtime->employee)) {
            throw new RuntimeException('Bạn không được duyệt đăng ký tăng ca này.');
        }
        if ($overtime->status !== OvertimeRequest::STATUS_PENDING) {
            throw new RuntimeException('Chỉ duyệt đăng ký đang chờ duyệt.');
        }

        $start = $window['approved_start'] ?? $overtime->requestedStartTime() ?? $overtime->start_time;
        $end = $window['approved_end'] ?? $overtime->requestedEndTime() ?? $overtime->end_time;

        return DB::transaction(function () use ($overtime, $actor, $start, $end) {
            $overtime->update([
                'status' => OvertimeRequest::STATUS_APPROVED,
                'approved_start' => $start,
                'approved_end' => $end,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            HrApprovalNotifier::approved($overtime->employee_id, $actor, 'Đăng ký tăng ca', [
                'type' => 'overtime_request',
                'overtime_request_id' => $overtime->id,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'overtime_approved',
                'meta' => 'overtime:'.$overtime->id,
            ]);

            return $overtime->fresh();
        });
    }

    public function reject(OvertimeRequest $overtime, User $actor, string $reason): OvertimeRequest
    {
        $overtime->loadMissing('employee');
        if (! RequestApprover::canReview($actor, $overtime->employee)) {
            throw new RuntimeException('Bạn không được từ chối đăng ký tăng ca này.');
        }
        if ($overtime->status !== OvertimeRequest::STATUS_PENDING) {
            throw new RuntimeException('Chỉ từ chối đăng ký đang chờ duyệt.');
        }

        $reason = trim($reason) !== '' ? trim($reason) : 'Từ chối đăng ký tăng ca.';

        return DB::transaction(function () use ($overtime, $actor, $reason) {
            $overtime->update([
                'status' => OvertimeRequest::STATUS_REJECTED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            HrApprovalNotifier::rejected($overtime->employee_id, $actor, 'Đăng ký tăng ca', $reason, [
                'type' => 'overtime_request',
                'overtime_request_id' => $overtime->id,
            ]);

            return $overtime->fresh();
        });
    }

    public function applyFromAttendance(Attendance $attendance): ?OvertimeRequest
    {
        if (! $attendance->check_out || ! $attendance->employee_id) {
            return null;
        }

        $date = optional($attendance->date)?->toDateString();
        if (! $date) {
            return null;
        }

        $overtime = OvertimeRequest::query()
            ->where('employee_id', $attendance->employee_id)
            ->whereDate('date', $date)
            ->whereIn('status', [
                OvertimeRequest::STATUS_APPROVED,
                OvertimeRequest::STATUS_IN_PROGRESS,
                OvertimeRequest::STATUS_COMPLETED,
            ])
            ->orderByDesc('id')
            ->first();

        if (! $overtime) {
            return null;
        }

        $computed = $this->calculator->compute($overtime, $attendance->check_out);

        $overtime->forceFill([
            'actual_start' => $computed['actual_start'],
            'actual_end' => $computed['actual_end'],
            'actual_minutes' => $computed['actual_minutes'],
            'attendance_id' => $attendance->id,
            'status' => OvertimeRequest::STATUS_COMPLETED,
        ])->save();

        return $overtime->fresh();
    }

    public function verify(OvertimeRequest $overtime, User $actor): OvertimeRequest
    {
        $overtime->loadMissing('employee');
        if (! RequestApprover::canReview($actor, $overtime->employee)) {
            throw new RuntimeException(
                RequestApprover::needsDirector($overtime->employee)
                    ? 'Tăng ca của HR do Giám đốc xác nhận giờ thực tế.'
                    : 'Chỉ HR được xác nhận giờ tăng ca thực tế.'
            );
        }
        if ($overtime->status !== OvertimeRequest::STATUS_COMPLETED) {
            throw new RuntimeException('Chỉ xác nhận tăng ca đã có giờ thực tế từ chấm công.');
        }

        return DB::transaction(function () use ($overtime, $actor) {
            $overtime->update([
                'status' => OvertimeRequest::STATUS_VERIFIED,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            if ($overtime->attendance_id) {
                $hours = round(((int) $overtime->actual_minutes) / 60, 2);
                Attendance::query()->whereKey($overtime->attendance_id)->update([
                    'overtime_hours' => $hours,
                ]);
            }

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'overtime_verified',
                'meta' => 'overtime:'.$overtime->id.' minutes:'.$overtime->actual_minutes,
            ]);

            return $overtime->fresh();
        });
    }

    protected function assertNoActiveRequest(Employee $employee, string $date): void
    {
        $exists = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->whereNotIn('status', [
                OvertimeRequest::STATUS_REJECTED,
                OvertimeRequest::STATUS_CANCELLED,
            ])
            ->exists();

        if ($exists) {
            throw new RuntimeException('Ngày này đã có đăng ký/chỉ định tăng ca. Không tạo trùng.');
        }
    }
}
