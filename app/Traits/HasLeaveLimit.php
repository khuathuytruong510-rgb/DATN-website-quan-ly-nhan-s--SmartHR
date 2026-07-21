<?php

namespace App\Traits;

use App\Models\LeaveRequest;
use Carbon\Carbon;

trait HasLeaveLimit
{
    /**
     * Maximum allowed leave days per month (excluding rejected requests)
     */
    private const MAX_LEAVE_DAYS_PER_MONTH = 2;

    /**
     * Check if employee has exceeded the monthly leave limit
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeLeaveId Leave request ID to exclude (for updates)
     * @return array{exceeded: bool, used_days: int, requested_days: int, remaining_days: int}
     */
    private function checkLeaveLimit(int $employeeId, string $startDate, string $endDate, ?int $excludeLeaveId = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $requestedDays = (int) ($end->diffInDays($start) + 1);

        $year = $start->year;
        $month = $start->month;

        $query = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year);

        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $usedDays = (int) $query->sum('days');
        $remainingDays = max(0, self::MAX_LEAVE_DAYS_PER_MONTH - $usedDays);

        return [
            'exceeded' => ($usedDays + $requestedDays) > self::MAX_LEAVE_DAYS_PER_MONTH,
            'used_days' => $usedDays,
            'requested_days' => $requestedDays,
            'remaining_days' => $remainingDays,
            'max_days' => self::MAX_LEAVE_DAYS_PER_MONTH,
        ];
    }
}
