<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalculationService
{
    // Standard work hours configuration
    private const WORKING_HOURS_PER_DAY = 8;
    private const STANDARD_CHECK_IN = '08:00';
    private const STANDARD_CHECK_OUT = '17:30';
    private const BREAK_TIME_START = '12:00';
    private const BREAK_TIME_END = '13:30';
    private const BREAK_DURATION_MINUTES = 90; // 1.5 hours

    /**
     * Calculate all attendance metrics for a single record
     */
    public function calculateAttendanceMetrics(Attendance $attendance): array
    {
        if (!$attendance->check_in) {
            return [
                'status' => 'absent',
                'work_hours' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_hours' => 0,
            ];
        }

        if (!$attendance->check_out) {
            // Only checked in, not checked out yet
            return [
                'status' => 'present', // Will be updated when checking out
                'work_hours' => 0,
                'late_minutes' => $this->calculateLateMinutes($attendance->check_in),
                'early_leave_minutes' => 0,
                'overtime_hours' => 0,
            ];
        }

        // Calculate all metrics
        $workHours = $this->calculateWorkHours($attendance->check_in, $attendance->check_out);
        $lateMinutes = $this->calculateLateMinutes($attendance->check_in);
        $earlyLeaveMinutes = $this->calculateEarlyLeaveMinutes($attendance->check_out);
        $overtimeHours = $this->calculateOvertimeHours($workHours);

        $status = $this->determineStatus($lateMinutes, $earlyLeaveMinutes, $overtimeHours);

        return [
            'status' => $status,
            'work_hours' => round($workHours, 2),
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_hours' => round($overtimeHours, 2),
        ];
    }

    /**
     * Calculate total work hours between check-in and check-out, minus break time
     *
     * Formula: work_hours = (check_out - check_in) - break_time
     * Example: 08:00 to 17:30 = 9.5 hours - 1.5 hours break = 8 hours
     */
    private function calculateWorkHours(Carbon $checkIn, Carbon $checkOut): float
    {
        $totalMinutes = (int) floor(($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 60);

        $breakTimeInMinutes = $this->calculateBreakTimeOverlap($checkIn, $checkOut);

        $workMinutes = max(0, $totalMinutes - $breakTimeInMinutes);

        return max(0, $workMinutes / 60); // Convert to hours
    }

    /**
     * Calculate how many minutes of break time fall within check-in and check-out
     */
    private function calculateBreakTimeOverlap(Carbon $checkIn, Carbon $checkOut): int
    {
        $breakStart = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_START);
        $breakEnd = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_END);

        if ($checkOut->lessThanOrEqualTo($breakStart) || $checkIn->greaterThanOrEqualTo($breakEnd)) {
            return 0;
        }

        $effectiveBreakStart = $checkIn->greaterThan($breakStart) ? $checkIn : $breakStart;
        $effectiveBreakEnd = $checkOut->lessThan($breakEnd) ? $checkOut : $breakEnd;

        $breakMinutes = (int) floor(($effectiveBreakEnd->getTimestamp() - $effectiveBreakStart->getTimestamp()) / 60);

        return max(0, $breakMinutes);
    }

    /**
     * Calculate late minutes
     *
     * Formula: late_minutes = check_in - standard_check_in
     * If negative, return 0
     * Example: check_in at 08:15, standard is 08:00 => late 15 minutes
     */
    private function calculateLateMinutes(Carbon $checkIn): int
    {
        $standardCheckInTime = $checkIn->clone()->setTimeFromTimeString(self::STANDARD_CHECK_IN);

        $lateMinutes = (int) floor(($checkIn->getTimestamp() - $standardCheckInTime->getTimestamp()) / 60);

        return max(0, $lateMinutes);
    }

    /**
     * Calculate early leave minutes
     *
     * Formula: early_leave = standard_check_out - check_out
     * If negative, return 0
     * Example: check_out at 17:00, standard is 17:30 => early 30 minutes
     */
    private function calculateEarlyLeaveMinutes(Carbon $checkOut): int
    {
        $standardCheckOutTime = $checkOut->clone()->setTimeFromTimeString(self::STANDARD_CHECK_OUT);

        $earlyMinutes = (int) floor(($standardCheckOutTime->getTimestamp() - $checkOut->getTimestamp()) / 60);

        return max(0, $earlyMinutes);
    }

    /**
     * Calculate overtime hours
     *
     * Formula: overtime = check_out - standard_check_out
     * If negative, return 0
     * Example: check_out at 19:30, standard is 17:30 => overtime 2 hours
     */
    private function calculateOvertimeHours(float $workHours): float
    {
        $overtimeHours = max(0, $workHours - self::WORKING_HOURS_PER_DAY);

        return round($overtimeHours, 2);
    }

    /**
     * Determine attendance status based on metrics
     * Status: present, late, leave_early, late_and_leave_early, overtime, absent
     */
    private function determineStatus(int $lateMinutes, int $earlyLeaveMinutes, float $overtimeHours): string
    {
        $isLate = $lateMinutes > 0;
        $isEarlyLeave = $earlyLeaveMinutes > 0;
        $hasOvertime = $overtimeHours > 0;

        if ($isLate && $isEarlyLeave) {
            return 'late_and_leave_early';
        } elseif ($isLate) {
            return 'late';
        } elseif ($isEarlyLeave && !$hasOvertime) {
            return 'leave_early';
        } elseif ($hasOvertime) {
            return 'overtime';
        }

        return 'present';
    }

    /**
     * Update attendance record with calculated metrics
     */
    public function updateAttendanceMetrics(Attendance $attendance): Attendance
    {
        $metrics = $this->calculateAttendanceMetrics($attendance);

        $attendance->update([
            'status' => $metrics['status'],
            'work_hours' => $metrics['work_hours'],
            'late_minutes' => $metrics['late_minutes'],
            'early_leave_minutes' => $metrics['early_leave_minutes'],
            'overtime_hours' => $metrics['overtime_hours'],
        ]);

        return $attendance->fresh();
    }

    /**
     * Get monthly statistics for an employee
     */
    public function getMonthlyStatistics(int $employeeId, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        return [
            'total_days_worked' => $attendances->filter(function ($a) {
                return $a->status !== 'absent' && $a->check_in !== null;
            })->count(),
            'total_days_absent' => $attendances->filter(fn($a) => $a->status === 'absent')->count(),
            'total_late_days' => $attendances->filter(fn($a) => in_array($a->status, ['late', 'late_and_leave_early']))->count(),
            'total_early_leave_days' => $attendances->filter(fn($a) => in_array($a->status, ['leave_early', 'late_and_leave_early']))->count(),
            'total_work_hours' => $attendances->sum('work_hours'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'average_work_hours_per_day' => $attendances->where('work_hours', '>', 0)->avg('work_hours') ?? 0,
            'attendances' => $attendances,
        ];
    }

    /**
     * Get daily summary
     */
    public function getDailySummary(Attendance $attendance): array
    {
        $metrics = $this->calculateAttendanceMetrics($attendance);

        return [
            'date' => $attendance->date->format('Y-m-d'),
            'day_of_week' => $attendance->date->format('l'),
            'check_in' => $attendance->check_in?->format('H:i:s'),
            'check_out' => $attendance->check_out?->format('H:i:s'),
            'work_hours' => $metrics['work_hours'],
            'late_minutes' => $metrics['late_minutes'],
            'early_leave_minutes' => $metrics['early_leave_minutes'],
            'overtime_hours' => $metrics['overtime_hours'],
            'status' => $metrics['status'],
            'check_in_location' => $attendance->check_in_location,
            'check_out_location' => $attendance->check_out_location,
            'notes' => $attendance->notes,
        ];
    }

    /**
     * Get standard working times
     */
    public static function getStandardTimes(): array
    {
        return [
            'standard_check_in' => self::STANDARD_CHECK_IN,
            'standard_check_out' => self::STANDARD_CHECK_OUT,
            'break_time_start' => self::BREAK_TIME_START,
            'break_time_end' => self::BREAK_TIME_END,
            'break_duration_minutes' => self::BREAK_DURATION_MINUTES,
        ];
    }
}
