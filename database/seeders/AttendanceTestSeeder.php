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
        Attendance::truncate();
        $attendanceService = app(AttendanceCalculationService::class);

        $employees = Employee::orderBy('id')->get();

        if ($employees->count() == 0) {
            $this->command->error('Không có nhân viên.');
            return;
        }

        // Số ngày công mong muốn của từng nhân viên
        $workingDays = [
            18,
            21,
            24,
            26,
            28,
            30,
        ];

        foreach ($employees as $index => $employee) {

            $days = $workingDays[$index] ?? rand(20, 26);

            for ($i = 1; $i <= $days; $i++) {

                // Nếu >30 thì quay vòng từ ngày 1
                $day = $i > 30 ? $i - 30 : $i;

                $date = Carbon::create(2026, 6, $day);

                // Giờ vào ngẫu nhiên
                $checkInHour = 8;
                $checkInMinute = collect([0, 5, 10, 15, 20, 25])->random();

                $checkIn = Carbon::create(
                    2026,
                    6,
                    $day,
                    $checkInHour,
                    $checkInMinute
                );

                // Giờ ra ngẫu nhiên
                $checkOut = $checkIn->copy();

                $checkOut->setTime(
                    collect([17,18,19])->random(),
                    collect([0,15,30])->random()
                );

                $workHours = round(
                    $checkOut->diffInMinutes($checkIn) / 60 - 1.5,
                    2
                );

                $attendance = Attendance::create([

    'employee_id' => $employee->id,

    'date' => $date,

    'check_in' => $checkIn->format('H:i:s'),

    'check_out' => $checkOut->format('H:i:s'),

    'status' => 'present',

]);

$attendanceService->updateAttendanceMetrics($attendance);
            }
        }

        $this->command->info('Đã tạo dữ liệu chấm công test.');
    }
}
