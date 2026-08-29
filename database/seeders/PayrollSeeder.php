<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $month = 7;
        $year = 2026;

        foreach ($employees as $employee) {
            $baseSalary = $this->getBaseSalary($employee);
            $dailySalary = round($baseSalary / 26, 0); // Chia cho 26 ngày làm việc
            $workingDays = rand(24, 26);
            $overtimeDays = rand(0, 3);
            $overtimeHours = rand(0, 20);
            
            $workingSalary = $dailySalary * $workingDays;
            $overtimeDaySalary = $dailySalary * 1.5 * $overtimeDays;
            $overtimeHourSalary = ($dailySalary / 8) * 1.5 * $overtimeHours;
            $overtimeSalary = $overtimeDaySalary + $overtimeHourSalary;
            
            $allowance = 500000;
            $bonus = rand(0, 2000000);
            $insurance = round($baseSalary * 0.105, 0); // 10.5% bảo hiểm
            $tax = round(($baseSalary + $allowance + $bonus - $insurance) * 0.1, 0);
            
            $totalSalary = $workingSalary + $overtimeSalary + $allowance + $bonus - $insurance - $tax;

            Payroll::updateOrCreate([
                'employee_id' => $employee->id,
                'month' => $month,
            ], [
                'year' => $year,
                'base_salary' => $baseSalary,
                'daily_salary' => $dailySalary,
                'required_working_days' => 26,
                'working_days' => $workingDays,
                'working_salary' => $workingSalary,
                'overtime_days' => $overtimeDays,
                'overtime_hours' => $overtimeHours,
                'overtime_day_salary' => $overtimeDaySalary,
                'overtime_hour_salary' => $overtimeHourSalary,
                'overtime_salary' => $overtimeSalary,
                'allowance' => $allowance,
                'bonus' => $bonus,
                'deduction' => 0,
                'insurance' => $insurance,
                'tax' => $tax,
                'total_salary' => $totalSalary,
                'status' => 'calculated',
            ]);
        }
    }

    private function getBaseSalary($employee): float
    {
        // Lấy lương cơ bản từ contract hoặc mặc định
        $contract = \App\Models\Contract::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->latest()
            ->first();
            
        if ($contract && $contract->base_salary) {
            return $contract->base_salary;
        }
        
        // Mặc định theo vị trí
        $position = $employee->position;
        return match($position) {
            'CTO' => 45000000,
            'Senior Developer' => 18000000,
            'HR Manager' => 22000000,
            'Sales Executive' => 15000000,
            'Finance Officer' => 16000000,
            'Marketing Lead' => 14000000,
            default => 12000000,
        };
    }
}
