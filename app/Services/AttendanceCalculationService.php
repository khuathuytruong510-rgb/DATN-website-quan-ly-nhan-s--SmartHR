<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceCalculationService
{
    private const STANDARD_CHECK_IN = '08:00';
    private const STANDARD_CHECK_OUT = '17:00';
    private const BREAK_TIME_START = '12:00';
    private const BREAK_TIME_END = '13:00';
    private const BREAK_DURATION_MINUTES = 60;

    /**
     * Tính số tiền phạt đi muộn theo Phương án 1 (Khung phạt cố định)
     */
    public function calculateLatePenaltyFee(int $lateMinutes): float
    {
        if ($lateMinutes <= 5) {
            return 0;      // Ân hạn 5 phút đầu không phạt
        } elseif ($lateMinutes <= 15) {
            return 30000;  // Muộn 6 - 15 phút: Phạt 30,000 VNĐ
        } elseif ($lateMinutes <= 30) {
            return 50000;  // Muộn 16 - 30 phút: Phạt 50,000 VNĐ
        } elseif ($lateMinutes <= 60) {
            return 100000; // Muộn 31 - 60 phút: Phạt 100,000 VNĐ
        } else {
            return 200000; // Muộn trên 60 phút: Phạt 200,000 VNĐ
        }
    }

    public function calculateAttendanceMetrics(Attendance $attendance): array
    {
        if (!$attendance->check_in) {
            return [
                'status'              => 'absent',
                'work_hours'          => 0,
                'late_minutes'        => 0,
                'late_penalty_fee'    => 0,
                'early_leave_minutes' => 0,
                'overtime_hours'      => 0,
            ];
        }

        $lateMinutes = $this->calculateLateMinutes(Carbon::parse($attendance->check_in));
        $latePenaltyFee = $this->calculateLatePenaltyFee($lateMinutes);

        if (!$attendance->check_out) {
            return [
                'status'              => 'present',
                'work_hours'          => 0,
                'late_minutes'        => $lateMinutes,
                'late_penalty_fee'    => $latePenaltyFee,
                'early_leave_minutes' => 0,
                'overtime_hours'      => 0,
            ];
        }

        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($attendance->check_out);

        $workHours = $this->calculateWorkHours($checkIn, $checkOut);
        $earlyLeaveMinutes = $this->calculateEarlyLeaveMinutes($checkOut);
        $overtimeHours = $this->calculateOvertimeHours($checkOut);

        $status = $this->determineStatus($lateMinutes, $earlyLeaveMinutes, $overtimeHours);

        return [
            'status'              => $status,
            'work_hours'          => round($workHours, 2),
            'late_minutes'        => $lateMinutes,
            'late_penalty_fee'    => $latePenaltyFee,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_hours'      => round($overtimeHours, 2),
        ];
    }

    private function calculateWorkHours(Carbon $checkIn, Carbon $checkOut): float
    {
        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        $breakMinutes = $this->calculateBreakTimeOverlap($checkIn, $checkOut);
        $workMinutes = $totalMinutes - $breakMinutes;

        return min(8, round($workMinutes / 60, 2));
    }

    private function calculateBreakTimeOverlap(Carbon $checkIn, Carbon $checkOut): int
    {
        $breakStart = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_START);
        $breakEnd = $checkIn->clone()->setTimeFromTimeString(self::BREAK_TIME_END);

        if ($checkOut->lessThanOrEqualTo($breakStart) || $checkIn->greaterThanOrEqualTo($breakEnd)) {
            return 0;
        }

        $effectiveBreakStart = $checkIn->greaterThan($breakStart) ? $checkIn : $breakStart;
        $effectiveBreakEnd = $checkOut->lessThan($breakEnd) ? $checkOut : $breakEnd;

        return max(0, $effectiveBreakEnd->diffInMinutes($effectiveBreakStart));
    }

    private function calculateLateMinutes(Carbon $checkIn): int
    {
        $standard = $checkIn->copy()->setTimeFromTimeString(self::STANDARD_CHECK_IN);

        if ($checkIn->lessThanOrEqualTo($standard)) {
            return 0;
        }

        return $standard->diffInMinutes($checkIn);
    }

    private function calculateEarlyLeaveMinutes(Carbon $checkOut): int
    {
        $standard = $checkOut->copy()->setTimeFromTimeString(self::STANDARD_CHECK_OUT);

        if ($checkOut->greaterThanOrEqualTo($standard)) {
            return 0;
        }

        return $checkOut->diffInMinutes($standard);
    }

    private function calculateOvertimeHours(Carbon $checkOut): float
    {
        $standardCheckOut = $checkOut->copy()->setTimeFromTimeString(self::STANDARD_CHECK_OUT);

        if ($checkOut->lessThanOrEqualTo($standardCheckOut)) {
            return 0;
        }

        return round($standardCheckOut->diffInMinutes($checkOut) / 60, 2);
    }

    private function determineStatus(int $lateMinutes, int $earlyLeaveMinutes, float $overtimeHours): string
    {
        if ($lateMinutes > 0) {
            return 'late';
        }
        if ($earlyLeaveMinutes > 0) {
            return 'leave_early';
        }

        return 'present';
    }

    public function updateAttendanceMetrics(Attendance $attendance): Attendance
    {
        $metrics = $this->calculateAttendanceMetrics($attendance);

        $attendance->update([
            'status'              => $metrics['status'],
            'work_hours'          => $metrics['work_hours'],
            'late_minutes'        => $metrics['late_minutes'],
            'late_penalty_fee'    => $metrics['late_penalty_fee'],
            'early_leave_minutes' => $metrics['early_leave_minutes'],
            'overtime_hours'      => $metrics['overtime_hours'],
        ]);

        return $attendance->fresh();
    }
}