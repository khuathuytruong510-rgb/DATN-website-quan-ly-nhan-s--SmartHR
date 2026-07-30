<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use Illuminate\Support\Facades\Schema;

class PayrollCalculationService
{
    /*
    |--------------------------------------------------------------------------
    | Quy định lương
    |--------------------------------------------------------------------------
    */
    private const REQUIRED_WORKING_DAYS = 26;
    private const HOURS_PER_DAY = 8;
    private const OVERTIME_RATE = 1.5;

    /*
    |--------------------------------------------------------------------------
    | Bảo hiểm theo quy định (tỷ lệ phần trăm nhân với lương cơ bản)
    |   - BHXH NLĐ: 8%
    |   - BHYT NLĐ: 1.5%
    |   - BHTN NLĐ: 1%
    |   Tổng NLĐ: 10.5%
    |--------------------------------------------------------------------------
    */
    private const INSURANCE_RATE = 0.105;

    public function calculateTax(float $taxableIncome): float
    {
        if ($taxableIncome <= 5000000) {
            return 0;
        } elseif ($taxableIncome <= 10000000) {
            return ($taxableIncome - 5000000) * 0.05;
        } elseif ($taxableIncome <= 18000000) {
            return 250000 + (($taxableIncome - 10000000) * 0.10);
        } elseif ($taxableIncome <= 32000000) {
            return 1050000 + (($taxableIncome - 18000000) * 0.15);
        } elseif ($taxableIncome <= 52000000) {
            return 3150000 + (($taxableIncome - 32000000) * 0.20);
        } elseif ($taxableIncome <= 80000000) {
            return 7150000 + (($taxableIncome - 52000000) * 0.25);
        } else {
            return 14150000 + (($taxableIncome - 80000000) * 0.30);
        }
    }

    public function normalizeOvertimeHours($value): float
    {
        if (is_string($value) && str_contains($value, ':')) {
            $parts = explode(':', $value);

            return (float) ($parts[0] ?? 0) + (float) ($parts[1] ?? 0) / 60;
        }
        if ($value > 1000) {
            return round($value / 3600, 2);
        }
        if ($value > 100) {
            return round($value / 60, 2);
        }

        return (float) $value;
    }

    public function calculateNetSalary(float $gross, float $insurance, float $tax): float
    {
        return max(0, $gross - $insurance - $tax);
    }

    public function calculate(Employee $employee, int $month, int $year)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Lương cơ bản theo chức vụ (lấy từ bảng positions)
        |--------------------------------------------------------------------------
        */
        $position = $employee->positionDetail;

        if ($position && $position->base_salary > 0) {
            $baseSalary = $position->base_salary;
        } else {
            $baseSalary = 7800000;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Quy định công chuẩn
        |--------------------------------------------------------------------------
        */
        $requiredWorkingDays = self::REQUIRED_WORKING_DAYS;
        $dailySalary = $baseSalary / $requiredWorkingDays;
        $hourSalary = $dailySalary / self::HOURS_PER_DAY;

        /*
        |--------------------------------------------------------------------------
        | 3. Lấy dữ liệu chấm công trong tháng (eager load employee)
        |--------------------------------------------------------------------------
        */
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. Đếm số ngày làm thực tế
        |    (mỗi bản ghi có check_in != null = 1 ngày đi làm)
        |--------------------------------------------------------------------------
        */
        $actualWorkingDays = $attendances->filter(fn ($a) => $a->check_in !== null)->count();

        /*
        |--------------------------------------------------------------------------
        | 5. Nghỉ phép trong tháng
        |    - Ưu tiên lấy từ leave_requests đã duyệt (annual/sick = có lương, còn lại = không lương)
        |    - Nếu không có leave_request, đếm attendance status='leave' làm phép không lương
        |    Hỗ trợ half_day (0.5 ngày)
        |--------------------------------------------------------------------------
        */
        $approvedLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->get();

        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;

        // Collect dates covered by approved leave_requests
        $leaveRequestDates = [];

        foreach ($approvedLeaves as $leave) {
            $days = $leave->half_day ? 0.5 : ($leave->days ?? 1);
            $leaveRequestDates[] = $leave->start_date->toDateString();

            if (in_array($leave->type, ['annual', 'sick'])) {
                $paidLeaveDays += $days;
            } else {
                $unpaidLeaveDays += $days;
            }
        }

        // Also count attendance records with status='leave' that have NO matching leave_request
        $attendanceLeaves = $attendances->filter(function ($a) use ($leaveRequestDates) {
            return $a->status === 'leave'
                && $a->check_in === null
                && ! in_array($a->date, $leaveRequestDates);
        });

        $unpaidLeaveDays += $attendanceLeaves->count();

        /*
        |--------------------------------------------------------------------------
        | 6. Công tính lương = ngày đi làm + phép có lương
        |    (tối đa 26 ngày công chuẩn)
        |--------------------------------------------------------------------------
        */
        $totalWorked = $actualWorkingDays + $paidLeaveDays;
        $workingDays = min($totalWorked, $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 7. Ngày tăng ca (số ngày làm vượt quá 26)
        |--------------------------------------------------------------------------
        */
        $overtimeDays = max(0, $actualWorkingDays - $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 8. Tổng giờ tăng ca (lấy từ cả attendance.overtime_hours + overtime_requests đã duyệt)
        |--------------------------------------------------------------------------
        */
        $overtimeHoursFromAttendance = $attendances->sum('overtime_hours');

        $overtimeHoursFromRequests = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->sum(function ($ot) {
                $start = strtotime($ot->start_time);
                $end = strtotime($ot->end_time);

                return max(0, ($end - $start) / 3600);
            });

        $overtimeHours = round($overtimeHoursFromAttendance + $overtimeHoursFromRequests, 2);

        /*
        |--------------------------------------------------------------------------
        | 9. Lương đi làm (công thực tế × lương ngày, tối đa 26 ngày)
        |--------------------------------------------------------------------------
        */
        $workingSalary = $workingDays * $dailySalary;

        /*
        |--------------------------------------------------------------------------
        | 10. Lương nghỉ phép có lương (phép annually/sick × lương ngày)
        |--------------------------------------------------------------------------
        */
        $paidLeaveSalary = $paidLeaveDays * $dailySalary;

        /*
        |--------------------------------------------------------------------------
        | 11. Tiền tăng ca theo ngày (150%)
        |--------------------------------------------------------------------------
        */
        $overtimeDaySalary = $overtimeDays * $dailySalary * self::OVERTIME_RATE;

        /*
        |--------------------------------------------------------------------------
        | 12. Tiền tăng ca theo giờ (150%)
        |--------------------------------------------------------------------------
        */
        $overtimeHourSalary = $overtimeHours * $hourSalary * self::OVERTIME_RATE;

        /*
        |--------------------------------------------------------------------------
        | 13. Tổng tiền tăng ca
        |--------------------------------------------------------------------------
        */
        $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

        /*
        |--------------------------------------------------------------------------
        | 14. Phụ cấp (lấy từ positions.allowance, mặc định 0 nếu không có)
        |--------------------------------------------------------------------------
        */
        $allowance = ($position && $position->allowance > 0)
            ? $position->allowance
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 15. Thưởng (lấy từ bảng bonuses — tổng các khoản thưởng trong tháng)
        |     Nếu không có bản ghi nào trong DB →奖励 = 0
        |--------------------------------------------------------------------------
        */
        $bonus = Schema::hasTable('bonuses')
            ? (float) Bonus::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->sum('amount')
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 16. Khấu trừ khác (lấy từ bảng deductions — tổng các khoản khấu trừ trong tháng)
        |--------------------------------------------------------------------------
        */
        $deduction = Schema::hasTable('deductions')
            ? (float) Deduction::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->sum('amount')
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 17. Tiền phạt đi muộn (lấy từ attendances)
        |--------------------------------------------------------------------------
        */
        $totalLatePenaltyFee = Schema::hasColumn('attendances', 'late_penalty_fee')
            ? (float) $attendances->sum('late_penalty_fee')
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 18. Bảo hiểm (theo quy định: 10.5% lương cơ bản)
        |     - BHXH NLĐ: 8%  -  BHYT NLĐ: 1.5%  -  BHTN NLĐ: 1%
        |--------------------------------------------------------------------------
        */
        $insurance = $baseSalary * self::INSURANCE_RATE;

        /*
        |--------------------------------------------------------------------------
        | 19. Thu nhập chịu thuế (= tổng thu nhập - bảo hiểm)
        |--------------------------------------------------------------------------
        */
        $taxableIncome =
            $workingSalary
            + $paidLeaveSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance;

        /*
        |--------------------------------------------------------------------------
        | 20. Thuế thu nhập cá nhân (thang bậc lũy tiến)
        |--------------------------------------------------------------------------
        */
        $tax = $this->calculateTax($taxableIncome);

        /*
        |--------------------------------------------------------------------------
        | 21. Lương thực nhận
        |     = Lương đi làm + Lương nghỉ phép + Tổng TC + Phụ cấp + Thưởng
        |       - Bảo hiểm - Thuế - Khấu trừ - Phạt đi muộn
        |--------------------------------------------------------------------------
        */
        $totalSalary =
            $workingSalary
            + $paidLeaveSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance
            - $tax
            - $deduction
            - $totalLatePenaltyFee;

        /*
        |--------------------------------------------------------------------------
        | 22. Lưu bảng lương
        |--------------------------------------------------------------------------
        */
        return Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
            ],
            [
                'base_salary'           => $baseSalary,
                'daily_salary'          => round($dailySalary, 2),
                'required_working_days' => $requiredWorkingDays,

                'working_days'          => $actualWorkingDays,

                'paid_leave_days'       => $paidLeaveDays,
                'unpaid_leave_days'     => $unpaidLeaveDays,

                'working_salary'        => round($workingSalary, 2),

                'overtime_days'         => $overtimeDays,
                'overtime_hours'        => $overtimeHours,
                'overtime_day_salary'   => round($overtimeDaySalary, 2),
                'overtime_hour_salary'  => round($overtimeHourSalary, 2),
                'overtime_salary'       => round($totalOvertimeSalary, 2),

                'allowance'             => $allowance,
                'bonus'                 => $bonus,

                'deduction'             => $deduction,
                'late_penalty_fee'      => round($totalLatePenaltyFee, 2),

                'insurance'             => round($insurance, 2),
                'tax'                   => round($tax, 2),

                'total_salary'          => round($totalSalary, 2),
            ]
        );
    }
}
