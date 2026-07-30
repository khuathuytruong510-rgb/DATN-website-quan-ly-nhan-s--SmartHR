<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;

class PayrollCalculationService
{
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
        | 5. Công tính lương
        |--------------------------------------------------------------------------
        */
        $workingDays = min($actualWorkingDays, $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 6. Ngày tăng ca
        |--------------------------------------------------------------------------
        */
        $overtimeDays = max(0, $actualWorkingDays - $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 7. Tổng giờ tăng ca
        |--------------------------------------------------------------------------
        */
        $overtimeHours = $attendances->sum('overtime_hours');

        /*
        |--------------------------------------------------------------------------
        | 8. Tính tổng số phút đi muộn & Tổng tiền phạt đi muộn
        |--------------------------------------------------------------------------
        */
        $attendanceService = new AttendanceCalculationService();
        $totalLateMinutes = 0;
        $totalLatePenalty = 0;

        foreach ($attendances as $attendance) {
            if (!empty($attendance->check_in)) {
                // Parse check_in thành Carbon
                $checkInTime = \Carbon\Carbon::parse($attendance->check_in);

                // Chuẩn giờ vào 08:00:00 đúng theo ngày chấm công
                $standardCheckIn = $checkInTime->copy()->setTime(8, 0, 0);

                $lateMins = 0;
                if ($checkInTime->greaterThan($standardCheckIn)) {
                    $lateMins = (int) $standardCheckIn->diffInMinutes($checkInTime);
                }

                // Tính tiền phạt theo mốc phút
                $penaltyFee = $attendanceService->calculateLatePenaltyFee($lateMins);

                // Cập nhật lại bản ghi attendance trong CSDL
                $attendance->update([
                    'late_minutes'     => $lateMins,
                    'late_penalty_fee' => $penaltyFee,
                ]);

                $totalLateMinutes += $lateMins;
                $totalLatePenalty += $penaltyFee;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Nghỉ phép
        |--------------------------------------------------------------------------
        */
        $paidLeaveDays = 0;
        $unpaidLeaveDays = max(0, $requiredWorkingDays - $workingDays);

        /*
        |--------------------------------------------------------------------------
        | 10. Lương ngày công
        |--------------------------------------------------------------------------
        */
        $workingSalary = $workingDays * $dailySalary;

        /*
        |--------------------------------------------------------------------------
        | 11. Tiền tăng ca theo ngày & giờ (150%)
        |--------------------------------------------------------------------------
        */
        $overtimeDaySalary = $overtimeDays * $dailySalary * 1.5;
        $overtimeHourSalary = $overtimeHours * $hourSalary * 1.5;
        $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

        /*
        |--------------------------------------------------------------------------
        | 12. Phụ cấp
        |--------------------------------------------------------------------------
        */
        $allowance = 500000;

        /*
        |--------------------------------------------------------------------------
        | 13. Thưởng (Làm đủ/trên 26 công)
        |--------------------------------------------------------------------------
        */
        $bonus = $actualWorkingDays >= 26 ? 500000 : 0;

        /*
        |--------------------------------------------------------------------------
        | 14. Khấu trừ khác & Bảo hiểm
        |--------------------------------------------------------------------------
        */
        $deduction = 0;
        $insurance = $baseSalary * 0.105;

        /*
        |--------------------------------------------------------------------------
        | 15. Thu nhập chịu thuế
        |--------------------------------------------------------------------------
        */
        $taxableIncome = $workingSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance;

        /*
        |--------------------------------------------------------------------------
        | 16. Thuế thu nhập cá nhân
        |--------------------------------------------------------------------------
        */
        if ($taxableIncome <= 5000000) {
            $tax = 0;
        } elseif ($taxableIncome <= 10000000) {
            $tax = ($taxableIncome - 5000000) * 0.05;
        } elseif ($taxableIncome <= 18000000) {
            $tax = 250000 + (($taxableIncome - 10000000) * 0.10);
        } elseif ($taxableIncome <= 32000000) {
            $tax = 1050000 + (($taxableIncome - 18000000) * 0.15);
        } elseif ($taxableIncome <= 52000000) {
            $tax = 3150000 + (($taxableIncome - 32000000) * 0.20);
        } elseif ($taxableIncome <= 80000000) {
            $tax = 7150000 + (($taxableIncome - 52000000) * 0.25);
        } else {
            $tax = 14150000 + (($taxableIncome - 80000000) * 0.30);
        }

        /*
        |--------------------------------------------------------------------------
        | 17. Lương thực nhận (Tổng cộng trừ tiền phạt đi muộn)
        |--------------------------------------------------------------------------
        */
        $totalSalary = $workingSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance
            - $tax
            - $deduction
            - $totalLatePenalty;

        /*
        |--------------------------------------------------------------------------
        | 18. Lưu bảng lương
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
                'total_late_minutes'    => $totalLateMinutes,
                'late_deduction'        => round($totalLatePenalty, 2),
                'working_salary'        => round($workingSalary, 2),
                'overtime_days'         => $overtimeDays,
                'overtime_hours'        => round($overtimeHours, 2),
                'overtime_day_salary'   => round($overtimeDaySalary, 2),
                'overtime_hour_salary'  => round($overtimeHourSalary, 2),
                'overtime_salary'       => round($totalOvertimeSalary, 2),
                'allowance'             => $allowance,
                'bonus'                 => $bonus,
                'deduction'             => $deduction,
                'insurance'             => round($insurance, 2),
                'tax'                   => round($tax, 2),
                'total_salary'          => round($totalSalary, 2),
            ]
        );
    }
}