<?php

namespace App\Traits;

use App\Models\LeaveRequest;
use Carbon\Carbon;

trait HasLeaveLimit
{
    private const MAX_LEAVE_DAYS_PER_MONTH = 2;
    private const MAX_LEAVE_REQUESTS_PER_MONTH = 2;

    /**
     * Tính số ngày nghỉ (hỗ trợ nửa ngày)
     */
    private function calculateLeaveDays(string $startDate, string $endDate, bool $halfDay = false): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $fullDays = (int) (abs($end->diffInDays($start)) + 1);

        if ($halfDay && $fullDays === 1) {
            return 0.5;
        }

        if ($halfDay && $fullDays > 1) {
            return $fullDays - 0.5;
        }

        return (float) $fullDays;
    }

    /**
     * Kiểm tra hạn nghỉ phép: max 2 ngày + max 2 đơn/tháng
     */
    private function checkLeaveLimit(int $employeeId, string $startDate, string $endDate, bool $halfDay = false, ?int $excludeLeaveId = null): array
    {
        $start = Carbon::parse($startDate);
        $year = $start->year;
        $month = $start->month;

        $query = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year);

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $usedDays = (float) $query->sum('days');
        $usedRequests = (int) (clone $query)->count();
        $requestedDays = $this->calculateLeaveDays($startDate, $endDate, $halfDay);
        $remainingDays = max(0, self::MAX_LEAVE_DAYS_PER_MONTH - $usedDays);

        return [
            'days_exceeded' => ($usedDays + $requestedDays) > self::MAX_LEAVE_DAYS_PER_MONTH,
            'requests_exceeded' => $usedRequests >= self::MAX_LEAVE_REQUESTS_PER_MONTH,
            'exceeded' => (($usedDays + $requestedDays) > self::MAX_LEAVE_DAYS_PER_MONTH) || ($usedRequests >= self::MAX_LEAVE_REQUESTS_PER_MONTH),
            'used_days' => $usedDays,
            'used_requests' => $usedRequests,
            'requested_days' => $requestedDays,
            'remaining_days' => $remainingDays,
            'max_days' => self::MAX_LEAVE_DAYS_PER_MONTH,
            'max_requests' => self::MAX_LEAVE_REQUESTS_PER_MONTH,
        ];
    }
}
