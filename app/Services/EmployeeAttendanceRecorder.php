<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeAttendanceRecorder
{
    public function __construct(
        private OfficeLocationService $office,
        private AttendanceCalculationService $calculation,
        private PayrollPeriodLockService $periodLock,
    ) {
    }

    public function resolveEmployee(?User $user): Employee
    {
        $employee = $user?->linkedEmployee();

        if (! $employee) {
            throw new AttendanceException(
                'Tài khoản chưa gắn hồ sơ nhân viên. Liên hệ HR để được liên kết tài khoản.',
                422
            );
        }

        return $employee;
    }

    /**
     * @return array{action: string, message: string, attendance: Attendance, distance: float, metrics?: array}
     */
    public function record(
        Employee $employee,
        User $user,
        float $latitude,
        float $longitude,
        ?string $notes,
        string $method,
        string $ipAddress,
    ): array {
        $today = Carbon::today();

        try {
            $this->periodLock->assertWritableDate($today->toDateString(), 'chấm công');
        } catch (RuntimeException $e) {
            throw new AttendanceException($e->getMessage(), 422);
        }

        $geo = $this->office->assertWithinRange($latitude, $longitude);

        return DB::transaction(function () use ($employee, $user, $today, $latitude, $longitude, $notes, $method, $ipAddress, $geo) {
            $attendance = Attendance::lockForEmployeeDate($employee->id, $today);

            if (! $attendance->check_in) {
                $attendance->update([
                    'check_in' => Carbon::now(),
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_location' => $geo['location'],
                    'check_in_ip_address' => $ipAddress,
                    'check_in_distance' => $geo['distance'],
                    'check_in_notes' => $notes,
                    'check_in_location_missing' => false,
                    'attendance_method' => $method,
                    'attendance_status' => 'check_in',
                    'status' => $this->checkInStatus(),
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'attendance_check_in',
                    'meta' => $attendance->check_in?->format('H:i'),
                ]);

                $fresh = $attendance->fresh();

                return [
                    'action' => 'check_in',
                    'message' => 'Chấm công vào lúc '.$fresh->check_in->format('H:i:s').' thành công.',
                    'attendance' => $fresh,
                    'distance' => $geo['distance'],
                ];
            }

            if ($attendance->check_out) {
                throw new AttendanceException(
                    'Bạn đã chấm công vào và ra đủ trong ngày (vào '.$attendance->check_in->format('H:i:s').', ra '.$attendance->check_out->format('H:i:s').').'
                );
            }

            $attendance->update([
                'check_out' => Carbon::now(),
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_location' => $geo['location'],
                'check_out_ip_address' => $ipAddress,
                'check_out_distance' => $geo['distance'],
                'check_out_notes' => $notes,
                'check_out_location_missing' => false,
                'attendance_method' => $method,
                'attendance_status' => 'check_out',
            ]);

            $attendance = $this->calculation->updateAttendanceMetrics($attendance);

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'attendance_check_out',
                'meta' => $attendance->check_out?->format('H:i'),
            ]);

            return [
                'action' => 'check_out',
                'message' => 'Chấm công ra lúc '.$attendance->check_out->format('H:i:s').' thành công.',
                'attendance' => $attendance,
                'distance' => $geo['distance'],
                'metrics' => [
                    'work_hours' => $attendance->work_hours,
                    'late_minutes' => $attendance->late_minutes,
                    'late_penalty_fee' => $attendance->late_penalty_fee,
                    'early_leave_minutes' => $attendance->early_leave_minutes,
                    'overtime_hours' => $attendance->overtime_hours,
                    'status' => $attendance->status_label,
                ],
            ];
        });
    }

    private function checkInStatus(): string
    {
        return Carbon::now()->greaterThan(Carbon::createFromTime(8, 0, 0))
            ? 'late'
            : 'present';
    }
}
