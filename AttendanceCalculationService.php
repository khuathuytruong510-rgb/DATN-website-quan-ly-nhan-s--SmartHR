<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalculationService
{
    // Standard work hours configuration
    private const STANDARD_CHECK_IN = '08:00';
    private const STANDARD_CHECK_OUT = '17:00';

    private const BREAK_TIME_START = '12:00';

    private const BREAK_TIME_END = '13:00';

    private const BREAK_DURATION_MINUTES = 60;

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
        $overtimeHours = $this->calculateOvertimeHours($attendance->check_out);

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
        $totalMinutes = $checkIn->diffInMinutes($checkOut);

        $breakMinutes = $this->calculateBreakTimeOverlap(
            $checkIn,
            $checkOut
        );

        $workMinutes = $totalMinutes - $breakMinutes;

        // Không tính quá 8 giờ là giờ làm
        return min(8, round($workMinutes / 60, 2));
    }

    /**
     * Calculate how many minutes of break time fall within check-in and check-out
     */
    private function calculateBreakTimeOverlap(Carbon $checkIn, Carbon $checkOut): int
    {
        $breakStart = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_START);
        $breakEnd = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_END);

        // If check-out is before break starts or check-in is after break ends
        if ($checkOut->lessThanOrEqualTo($breakStart) || $checkIn->greaterThanOrEqualTo($breakEnd)) {
            return 0;
        }

        // Calculate overlapping break period
        $effectiveBreakStart = $checkIn->greaterThan($breakStart)
            ? $checkIn
            : $breakStart;

        $effectiveBreakEnd = $checkOut->lessThan($breakEnd)
            ? $checkOut
            : $breakEnd;

        $breakMinutes = $effectiveBreakEnd->diffInMinutes($effectiveBreakStart);

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
        $standard = $checkIn->copy()
            ->setTimeFromTimeString(self::STANDARD_CHECK_IN);

        if ($checkIn->lessThanOrEqualTo($standard)) {
            return 0;
        }

        return $standard->diffInMinutes($checkIn);
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
        $standard = $checkOut->copy()
            ->setTimeFromTimeString(self::STANDARD_CHECK_OUT);

        if ($checkOut->greaterThanOrEqualTo($standard)) {
            return 0;
        }

        return $checkOut->diffInMinutes($standard);
    }

    /**
     * Calculate overtime hours
     *
     * Formula: overtime = check_out - standard_check_out
     * If negative, return 0
     * Example: check_out at 19:30, standard is 17:30 => overtime 2 hours
     */
    private function calculateOvertimeHours(Carbon $checkOut): float
    {
        $standardCheckOut = $checkOut->copy()
            ->setTimeFromTimeString(self::STANDARD_CHECK_OUT);

        if ($checkOut->lessThanOrEqualTo($standardCheckOut)) {
            return 0;
        }

        return round(
            $standardCheckOut->diffInMinutes($checkOut) / 60,
            2
        );
    }

    /**
     * Determine attendance status based on metrics
     * Status: present, late, leave_early, late_and_leave_early, overtime, absent
     */
    private function determineStatus(int $lateMinutes, int $earlyLeaveMinutes, float $overtimeHours): string
{
    if ($lateMinutes > 0) {
        return 'late';
    }

    if ($earlyLeaveMinutes > 0) {
        return 'leave';
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
    public function calculateWorkingDay(float $hours): float
        {
            if ($hours >= 8) {
                return 1;
            }

            if ($hours >= 4) {
                return 0.5;
            }

            return 0;
        }
}