<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$year = (int) date('Y');
$month = (int) date('m');
$today = (int) date('d');

$employees = DB::table('employees')->pluck('id')->toArray();

$dates = [];
for ($d = 1; $d <= $today; $d++) {
    $dt = mktime(0, 0, 0, $month, $d, $year);
    $dow = date('N', $dt);
    if ($dow <= 5) {
        $dates[] = date('Y-m-d', $dt);
    }
}

function makeTime($h, $m) {
    $m = $m % 60;
    return sprintf('%02d:%02d:00', $h, $m);
}

$inserted = 0;
foreach ($employees as $empId) {
    foreach ($dates as $date) {
        $exists = DB::table('attendances')
            ->where('employee_id', $empId)
            ->where('date', $date)
            ->exists();
        if ($exists) continue;

        $rand = rand(1, 100);
        if ($rand <= 75) {
            $lateMin = 0;
            $earlyMin = 0;
            $checkIn = makeTime(8, rand(0, 10));
            $checkOut = makeTime(17, rand(0, 59));
            $workHours = 8;
            $status = 'present';
        } elseif ($rand <= 85) {
            $lateMin = rand(10, 30);
            $checkIn = makeTime(8, $lateMin);
            $checkOut = makeTime(17, rand(0, 30));
            $workHours = 8;
            $status = 'late';
        } elseif ($rand <= 92) {
            $checkIn = makeTime(8, rand(0, 10));
            $checkOut = makeTime(14, rand(0, 59));
            $workHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
            $status = 'leave_early';
            $earlyMin = 180;
            $lateMin = 0;
        } else {
            $checkIn = null;
            $checkOut = null;
            $workHours = 0;
            $status = 'absent';
            $lateMin = 0;
            $earlyMin = 0;
        }

        DB::table('attendances')->insert([
            'employee_id'          => $empId,
            'date'                 => $date,
            'check_in'             => $checkIn,
            'check_out'            => $checkOut,
            'work_hours'           => $workHours,
            'late_minutes'         => $lateMin,
            'early_leave_minutes'  => $earlyMin,
            'overtime_hours'       => 0,
            'status'               => $status,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
        $inserted++;
    }
}

echo "Done: {$inserted} attendance records for {$month}/{$year} (".count($dates)." days x ".count($employees)." employees)\n";
