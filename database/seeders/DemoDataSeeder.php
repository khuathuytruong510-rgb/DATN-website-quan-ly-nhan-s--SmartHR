<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryHistory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            $this->command->info('No employees found — run DatabaseSeeder first.');
            return;
        }

        $this->command->info('Generating attendances for June 2026...');
        $start = Carbon::create(2026, 6, 1);
        $end = Carbon::create(2026, 6, 30);

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            foreach ($employees as $emp) {
                // Random status distribution
                $rand = rand(1, 100);
                if ($rand <= 5) {
                    $status = 'absent';
                    $checkIn = null;
                    $checkOut = null;
                } elseif ($rand <= 12) {
                    $status = 'leave';
                    $checkIn = null;
                    $checkOut = null;
                } elseif ($rand <= 25) {
                    $status = 'late';
                    $checkIn = $d->copy()->setTime(rand(8,9), rand(1,59), 0);
                    $checkOut = $d->copy()->setTime(17, rand(0,30), 0);
                } else {
                    $status = 'present';
                    $checkIn = $d->copy()->setTime(8, rand(0,15), 0);
                    $checkOut = $d->copy()->setTime(17, rand(0,30), 0);
                }

                $attendance = Attendance::updateOrCreate([
                    'employee_id' => $emp->id,
                    'date' => $d->toDateString(),
                ], [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $status,
                    'check_in_location' => 'Văn phòng chính',
                    'check_out_location' => 'Văn phòng chính',
                    'check_in_ip_address' => '192.168.1.' . rand(2, 254),
                    'check_out_ip_address' => '192.168.1.' . rand(2, 254),
                    // 'device' column may not exist in schema; skip if absent
                    'check_in_notes' => $status === 'late' ? 'Đến muộn do giao thông' : null,
                    'check_out_notes' => null,
                ]);

                // Compute metrics if we have both times
                if ($attendance->check_in && $attendance->check_out) {
                    $min = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
                    $workHours = round($min/60, 2);
                    $late = 0;
                    $early = 0;
                    // standard start 8:00, end 17:00
                    $late = max(0, Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::createFromTime(8,0,0)));
                    $early = max(0, Carbon::createFromTime(17,0,0)->diffInMinutes(Carbon::parse($attendance->check_out)));
                    $overtime = max(0, round((Carbon::parse($attendance->check_out)->diffInMinutes(Carbon::createFromTime(17,0,0)))/60,2));

                    $attendance->update([
                        'work_hours' => $workHours,
                        'late_minutes' => $late,
                        'early_leave_minutes' => $early,
                        'overtime_hours' => $overtime,
                    ]);
                }
            }
        }

        $this->command->info('Generating payrolls for June 2026...');
        foreach ($employees as $emp) {
            $base = $emp->base_salary ?? (rand(7,20) * 1000000);
            $monthKey = '2026-06';
            $allowance = 500000;
            $deduction = 0;
            $total = $base + $allowance - $deduction;

            Payroll::updateOrCreate([
                'employee_id' => $emp->id,
                'month' => $monthKey,
            ], [
                'month' => $monthKey,
                'base_salary' => $base,
                'allowance' => $allowance,
                'deduction' => $deduction,
                'total_salary' => $total,
                'status' => 'approved',
            ]);
        }

        // Add a few salary history entries
        $this->command->info('Adding salary history samples...');
        $hrUser = User::where('is_hr', true)->first();
        foreach ($employees->take(6) as $emp) {
            SalaryHistory::updateOrCreate([
                'employee_id' => $emp->id,
                'period' => '2026-06',
            ], [
                'payroll_id' => Payroll::where('employee_id', $emp->id)->where('month','2026-06')->value('id'),
                'code' => 'SH' . $emp->id . '062026',
                'period' => '2026-06',
                'effective_date' => Carbon::now()->toDateString(),
                'change_type' => 'Tăng lương định kỳ',
                'old_salary' => ($emp->base_salary ?? 0),
                'new_salary' => ($emp->base_salary ?? 0) + 1000000,
                'position' => $emp->position,
                'department_id' => $emp->department_id,
                'allowances' => ['position' => 500000],
                'rewards' => 0,
                'deductions' => 0,
                'tax' => 0,
                'insurance' => 0,
                'notes' => 'Tăng lương demo',
                'document_number' => 'QĐ-DEMO-' . rand(100,999),
                'status' => 'applied',
                'updated_by' => $hrUser?->id,
            ]);
        }

        $this->command->info('Demo data generation complete.');
    }
}
