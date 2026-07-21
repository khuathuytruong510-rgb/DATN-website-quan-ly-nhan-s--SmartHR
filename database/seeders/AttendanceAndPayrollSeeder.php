<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceAndPayrollSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        
        // Tạo dữ liệu attendance từ 1/6 đến 16/7
        $this->createAttendanceData($employees);
        
        // Tạo dữ liệu payroll cho tháng 6
        $this->createJunePayroll($employees);
    }

    private function createAttendanceData($employees): void
    {
        $startDate = Carbon::create(2026, 6, 1);
        $endDate = Carbon::create(2026, 7, 16);
        
        $statuses = ['present', 'present', 'present', 'present', 'present', 'late', 'leave_early', 'leave'];
        
        foreach ($employees as $employee) {
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Bỏ qua thứ 7, chủ nhật
                if ($currentDate->dayOfWeek === 6 || $currentDate->dayOfWeek === 0) {
                    $currentDate->addDay();
                    continue;
                }
                
                $status = $statuses[array_rand($statuses)];
                
                $checkIn = null;
                $checkOut = null;
                $notes = '';
                
                if ($status === 'present') {
                    $checkIn = '08:00:00';
                    $checkOut = '17:00:00';
                    $notes = 'Đúng giờ';
                } elseif ($status === 'late') {
                    $checkIn = '08:15:00';
                    $checkOut = '17:00:00';
                    $notes = 'Đến muộn 15 phút';
                } elseif ($status === 'leave_early') {
                    $checkIn = '08:00:00';
                    $checkOut = '16:30:00';
                    $notes = 'Về sớm 30 phút';
                } elseif ($status === 'leave') {
                    $notes = 'Nghỉ phép';
                }
                
                Attendance::updateOrCreate([
                    'employee_id' => $employee->id,
                    'date' => $currentDate->format('Y-m-d'),
                ], [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $status,
                    'notes' => $notes,
                ]);
                
                $currentDate->addDay();
            }
        }
    }

    private function createJunePayroll($employees): void
    {
        $month = 6;
        $year = 2026;
        
        foreach ($employees as $employee) {
            $baseSalary = $this->getBaseSalary($employee);
            $dailySalary = round($baseSalary / 26, 0);
            $workingDays = rand(24, 26);
            $overtimeDays = rand(0, 2);
            $overtimeHours = rand(0, 15);
            
            $workingSalary = $dailySalary * $workingDays;
            $overtimeDaySalary = $dailySalary * 1.5 * $overtimeDays;
            $overtimeHourSalary = ($dailySalary / 8) * 1.5 * $overtimeHours;
            $overtimeSalary = $overtimeDaySalary + $overtimeHourSalary;
            
            $allowance = 500000;
            $bonus = rand(0, 1500000);
            $insurance = round($baseSalary * 0.105, 0);
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
                'status' => 'paid',
                'paid_at' => Carbon::create(2026, 7, 5),
            ]);
        }
    }

    private function getBaseSalary($employee): float
    {
        $contract = \App\Models\Contract::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->latest()
            ->first();
            
        if ($contract && $contract->base_salary) {
            return $contract->base_salary;
        }
        
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
