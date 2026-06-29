<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Salary;

class SalaryCalculationService
{
    public function calculate(Employee $employee, int $month, int $year)
    {
        $workingDays = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereNotNull('check_in')
            ->count();

        $dailyRate = match ($employee->position) {

            'Giám Đốc' => 500000,

            'Trưởng Phòng Nhân Sự' => 400000,

            default => 300000,
        };

        $allowance = 500000;

        $baseSalary = $workingDays * $dailyRate;

        $totalSalary = $baseSalary + $allowance;

        return Salary::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'working_days' => $workingDays,
                'daily_rate' => $dailyRate,
                'base_salary' => $baseSalary,
                'allowance' => $allowance,
                'total_salary' => $totalSalary,
            ]
        );
    }
}