<?php

namespace Database\Seeders;

use App\Models\SalaryHistory;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalaryHistorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create sample salary history for some existing employees
        $employees = Employee::whereNotNull('email')->take(4)->get();

        foreach ($employees as $idx => $emp) {
            $payroll = Payroll::where('employee_id', $emp->id)->first();

            $old = ($emp->position === 'CTO') ? 40000000 : 15000000 + ($idx * 2000000);
            $new = $old + (1000000 * ($idx + 1));

            SalaryHistory::updateOrCreate([
                'employee_id' => $emp->id,
                'period' => '2026-06',
            ], [
                'payroll_id' => $payroll?->id,
                'code' => 'SH' . ($emp->id) . '06',
                'period' => '2026-06',
                'effective_date' => now()->subDays(10)->toDateString(),
                'change_type' => 'Điều chỉnh',
                'old_salary' => $old,
                'new_salary' => $new,
                'position' => $emp->position,
                'department_id' => $emp->department_id,
                'allowances' => [
                    'position' => 500000,
                    'responsibility' => 200000,
                    'lunch' => 150000,
                ],
                'rewards' => 0,
                'deductions' => 0,
                'tax' => 0,
                'insurance' => 0,
                'notes' => 'Tăng lương thử nghiệm',
                'document_number' => 'QĐ-' . rand(100,999),
                'status' => 'applied',
                'updated_by' => null,
            ]);
        }
    }
}
