<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;

class PayrollCalculationService
{
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
            return (float)($parts[0] ?? 0) + (float)($parts[1] ?? 0) / 60;
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
        | 1. Lương cơ bản theo chức vụ
        |--------------------------------------------------------------------------
        */

        $baseSalary = match ($employee->position) {

            'Giám Đốc' => 13000000,

            'Trưởng Phòng Nhân Sự' => 10400000,

            default => 7800000,
        };

        /*
        |--------------------------------------------------------------------------
        | 2. Quy định công chuẩn
        |--------------------------------------------------------------------------
        */

        $requiredWorkingDays = 26;

        $dailySalary = $baseSalary / $requiredWorkingDays;

        $hourSalary = $dailySalary / 8;

        /*
        |--------------------------------------------------------------------------
        | 3. Lấy dữ liệu chấm công
        |--------------------------------------------------------------------------
        */

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. Đếm số ngày làm thực tế
        |--------------------------------------------------------------------------
        */

        $actualWorkingDays = 0;

        foreach ($attendances as $attendance) {

            if (
                $attendance->check_in &&
                $attendance->check_out &&
                $attendance->work_hours >= 8
            ) {
                $actualWorkingDays++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Công tính lương (tối đa 26)
        |--------------------------------------------------------------------------
        */

        $workingDays = min(
            $actualWorkingDays,
            $requiredWorkingDays
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Ngày tăng ca
        |--------------------------------------------------------------------------
        */

        $overtimeDays = max(
            0,
            $actualWorkingDays - $requiredWorkingDays
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Tổng giờ tăng ca
        |--------------------------------------------------------------------------
        */

        $overtimeHours = $attendances->sum('overtime_hours');

        /*
        |--------------------------------------------------------------------------
        | 8. Nghỉ phép
        |--------------------------------------------------------------------------
        */

        $paidLeaveDays = 0;

        $unpaidLeaveDays = max(
            0,
            $requiredWorkingDays - $workingDays
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Lương ngày công
        |--------------------------------------------------------------------------
        */

        $workingSalary =
            $workingDays
            * $dailySalary;

        /*
        |--------------------------------------------------------------------------
        | 10. Tiền tăng ca theo ngày (150%)
        |--------------------------------------------------------------------------
        */

        $overtimeDaySalary =
            $overtimeDays
            * $dailySalary
            * 1.5;

        /*
        |--------------------------------------------------------------------------
        | 11. Tiền tăng ca theo giờ (150%)
        |--------------------------------------------------------------------------
        */

        $overtimeHourSalary =
            $overtimeHours
            * $hourSalary
            * 1.5;

        /*
        |--------------------------------------------------------------------------
        | 12. Tổng tiền tăng ca
        |--------------------------------------------------------------------------
        */

        $totalOvertimeSalary =
            $overtimeDaySalary
            + $overtimeHourSalary;
                    /*
        |--------------------------------------------------------------------------
        | 13. Phụ cấp
        |--------------------------------------------------------------------------
        */

        $allowance = 500000;

        /*
        |--------------------------------------------------------------------------
        | 14. Thưởng
        | Làm đủ hoặc trên 26 công được thưởng 500.000
        |--------------------------------------------------------------------------
        */

        $bonus = $actualWorkingDays >= 26
            ? 500000
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 15. Khấu trừ khác
        |--------------------------------------------------------------------------
        */

        $deduction = 0;

        /*
        |--------------------------------------------------------------------------
        | 16. Bảo hiểm (10.5% lương cơ bản)
        |--------------------------------------------------------------------------
        */

        $insurance = $baseSalary * 0.105;

        /*
        |--------------------------------------------------------------------------
        | 17. Thu nhập trước thuế
        |--------------------------------------------------------------------------
        */

        $taxableIncome =
            $workingSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance;

        /*
        |--------------------------------------------------------------------------
        | 18. Thuế thu nhập cá nhân
        |--------------------------------------------------------------------------
        */

        if ($taxableIncome <= 5000000) {

            $tax = 0;

        } elseif ($taxableIncome <= 10000000) {

            $tax = ($taxableIncome - 5000000) * 0.05;

        } elseif ($taxableIncome <= 18000000) {

            $tax =
                250000
                + (($taxableIncome - 10000000) * 0.10);

        } elseif ($taxableIncome <= 32000000) {

            $tax =
                1050000
                + (($taxableIncome - 18000000) * 0.15);

        } elseif ($taxableIncome <= 52000000) {

            $tax =
                3150000
                + (($taxableIncome - 32000000) * 0.20);

        } elseif ($taxableIncome <= 80000000) {

            $tax =
                7150000
                + (($taxableIncome - 52000000) * 0.25);

        } else {

            $tax =
                14150000
                + (($taxableIncome - 80000000) * 0.30);

        }

        /*
        |--------------------------------------------------------------------------
        | 19. Lương thực nhận
        |--------------------------------------------------------------------------
        */

        $totalSalary =
            $workingSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance
            - $tax
            - $deduction;
                    /*
        |--------------------------------------------------------------------------
        | 20. Lưu bảng lương
        |--------------------------------------------------------------------------
        */

        return Payroll::updateOrCreate(

            [
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
            ],

            [

                'base_salary' => $baseSalary,

                'daily_salary' => round($dailySalary, 2),

                'required_working_days' => $requiredWorkingDays,

                /*
                |----------------------------------------------------------
                | Lưu số ngày làm thực tế
                | (VD: làm 30 ngày thì DB lưu 30)
                | Blade sẽ hiển thị 26/26
                |----------------------------------------------------------
                */
                'working_days' => $actualWorkingDays,

                'paid_leave_days' => $paidLeaveDays,

                'unpaid_leave_days' => $unpaidLeaveDays,

                /*
                |----------------------------------------------------------
                | Lương ngày công (chỉ tối đa 26 ngày)
                |----------------------------------------------------------
                */
                'working_salary' => round($workingSalary, 2),

                /*
                |----------------------------------------------------------
                | Tăng ca
                |----------------------------------------------------------
                */
                'overtime_days' => $overtimeDays,

                'overtime_hours' => round($overtimeHours, 2),

                'overtime_day_salary' => round($overtimeDaySalary, 2),

                'overtime_hour_salary' => round($overtimeHourSalary, 2),

                'overtime_salary' => round($totalOvertimeSalary, 2),

                /*
                |----------------------------------------------------------
                | Phụ cấp - Thưởng
                |----------------------------------------------------------
                */
                'allowance' => $allowance,

                'bonus' => $bonus,

                /*
                |----------------------------------------------------------
                | Khấu trừ
                |----------------------------------------------------------
                */
                'deduction' => $deduction,

                'insurance' => round($insurance, 2),

                'tax' => round($tax, 2),

                /*
                |----------------------------------------------------------
                | Thực nhận
                |----------------------------------------------------------
                */
                'total_salary' => round($totalSalary, 2),

            ]

        );
    }
}