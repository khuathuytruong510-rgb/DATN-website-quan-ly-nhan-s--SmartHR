<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Services\AttendanceCalculationService;

class AttendanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $month = (int) $this->command->ask('Tháng cần tạo dữ liệu?', $now->month);
        $year  = (int) $this->command->ask('Năm?', $now->year);

        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        Attendance::whereMonth('date', $month)->whereYear('date', $year)->delete();

        $employees = Employee::where('status', 'active')->orderBy('id')->get();
        if ($employees->isEmpty()) {
            $this->command->error('Không có nhân viên.');
            return;
        }

        $attendanceService = app(AttendanceCalculationService::class);

        $totalCreated = 0;

        foreach ($employees as $employee) {
            $currentDate = $start->copy();

            while ($currentDate <= $end) {
                if ($currentDate->isSaturday() || $currentDate->isSunday()) {
                    $currentDate->addDay();
                    continue;
                }

                $roll = rand(1, 100);
                if ($roll <= 10) {
                    $currentDate->addDay();
                    continue;
                }

                if ($roll <= 20) {
                    $status = 'late';
                    $inMinute = rand(10, 45);
                } elseif ($roll <= 28) {
                    $status = 'leave';
                    $inMinute = 0;
                } else {
                    $status = 'present';
                    $inMinute = 0;
                }

                $checkIn  = null;
                $checkOut = null;

                if ($status !== 'leave') {
                    $checkIn = Carbon::create($year, $month, $currentDate->day, 8, $inMinute, 0);
                    $outHour = collect([17, 17, 17, 18, 18])->random();
                    $outMin  = collect([0, 0, 15, 30, 30])->random();
                    $checkOut = Carbon::create($year, $month, $currentDate->day, $outHour, $outMin, 0);
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'date'        => $currentDate->toDateString(),
                    'check_in'    => $checkIn?->format('H:i:s'),
                    'check_out'   => $checkOut?->format('H:i:s'),
                    'status'      => $status,
                    'notes'       => match ($status) {
                        'late'  => 'Đến muộn ' . $inMinute . ' phút',
                        'leave' => 'Nghỉ phép',
                        default => 'Đúng giờ',
                    },
                ]);

                $attendanceService->updateAttendanceMetrics($attendance);
                $totalCreated++;
                $currentDate->addDay();
            }
        }

        $this->command->info("Đã tạo {$totalCreated} bản ghi chấm công cho tháng {$month}/{$year}.");
    }
}
