<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Services\AttendanceCalculationService;

class AttendanceAndPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $month = (int) $this->command->ask('Tháng cần tạo dữ liệu?', $now->month);
        $year  = (int) $this->command->ask('Năm?', $now->year);

        $employees = Employee::where('status', 'active')->get();
        if ($employees->isEmpty()) {
            $this->command->error('Không có nhân viên.');
            return;
        }

        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        $attendanceService = app(AttendanceCalculationService::class);

        Attendance::whereMonth('date', $month)->whereYear('date', $year)->delete();
        LeaveRequest::whereMonth('start_date', $month)->whereYear('start_date', $year)->delete();
        Payroll::where('month', $month)->where('year', $year)->delete();

        $statuses = ['present', 'present', 'present', 'present', 'present', 'late', 'leave_early', 'leave'];
        $leaveTypes = ['annual', 'annual', 'sick', 'unpaid'];
        $createdAttendance = 0;
        $createdPayroll = 0;

        foreach ($employees as $employee) {
            $currentDate = $start->copy();

            while ($currentDate <= $end) {
                if ($currentDate->isSaturday() || $currentDate->isSunday()) {
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
                    $checkIn = '08:' . str_pad(rand(10, 45), 2, '0', STR_PAD_LEFT) . ':00';
                    $checkOut = '17:00:00';
                    $notes = 'Đến muộn';
                } elseif ($status === 'leave_early') {
                    $checkIn = '08:00:00';
                    $checkOut = '16:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                    $notes = 'Về sớm';
                } elseif ($status === 'leave') {
                    $notes = 'Nghỉ phép';

                    $leaveType = $leaveTypes[array_rand($leaveTypes)];
                    $isHalfDay = rand(1, 10) <= 2;

                    LeaveRequest::updateOrCreate([
                        'employee_id' => $employee->id,
                        'start_date'  => $currentDate->toDateString(),
                        'end_date'    => $currentDate->toDateString(),
                    ], [
                        'type'       => $leaveType,
                        'days'       => $isHalfDay ? 0.5 : 1,
                        'half_day'   => $isHalfDay,
                        'reason'     => match ($leaveType) {
                            'annual' => 'Nghỉ phép năm',
                            'sick'   => 'Nghỉ ốm đau',
                            default  => 'Nghỉ không lương',
                        },
                        'status'     => 'approved',
                        'approved_at' => $currentDate->copy()->subDay(),
                    ]);
                }

                $attendance = Attendance::updateOrCreate([
                    'employee_id' => $employee->id,
                    'date'        => $currentDate->format('Y-m-d'),
                ], [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $status,
                    'notes' => $notes,
                ]);

                $attendanceService->updateAttendanceMetrics($attendance);
                $createdAttendance++;
                $currentDate->addDay();
            }

            $position = $employee->positionDetail;
            $baseSalary = $position && $position->base_salary > 0
                ? $position->base_salary
                : 7800000;

            $dailySalary = round($baseSalary / 26, 0);
            $hourSalary = round($dailySalary / 8, 0);

            $empAttendances = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();

            $workingDays = $empAttendances->filter(fn($a) => $a->check_in !== null)->count();
            $overtimeDays = max(0, $workingDays - 26);

            $overtimeHours = $empAttendances->sum('overtime_hours');
            $totalLateFee = $empAttendances->sum('late_penalty_fee');

            $workingSalary = min($workingDays, 26) * $dailySalary;
            $overtimeDaySalary = $overtimeDays * $dailySalary * 1.5;
            $overtimeHourSalary = $overtimeHours * $hourSalary * 1.5;
            $overtimeSalary = $overtimeDaySalary + $overtimeHourSalary;

            $allowance = $position && $position->allowance > 0 ? $position->allowance : 0;
            $bonus = rand(0, 1500000);
            $insurance = round($baseSalary * 0.105, 0);
            $taxableIncome = $workingSalary + $overtimeSalary + $allowance + $bonus - $insurance;
            $tax = $taxableIncome > 5000000
                ? round($taxableIncome * 0.1, 0)
                : 0;
            $totalSalary = $workingSalary + $overtimeSalary + $allowance + $bonus - $insurance - $tax - $totalLateFee;

            Payroll::updateOrCreate([
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
            ], [
                'base_salary'           => $baseSalary,
                'daily_salary'          => $dailySalary,
                'required_working_days' => 26,
                'working_days'          => $workingDays,
                'working_salary'        => $workingSalary,
                'overtime_days'         => $overtimeDays,
                'overtime_hours'        => $overtimeHours,
                'overtime_day_salary'   => $overtimeDaySalary,
                'overtime_hour_salary'  => $overtimeHourSalary,
                'overtime_salary'       => $overtimeSalary,
                'allowance'             => $allowance,
                'bonus'                 => $bonus,
                'deduction'             => 0,
                'late_penalty_fee'      => $totalLateFee,
                'insurance'             => $insurance,
                'tax'                   => $tax,
                'total_salary'          => $totalSalary,
                'status'                => 'pending',
            ]);
            $createdPayroll++;
        }

        $this->command->info("Đã tạo {$createdAttendance} bản ghi chấm công + {$createdPayroll} bảng lương cho {$month}/{$year}.");
    }
}
