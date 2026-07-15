<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Employee;
use App\Models\Attendance;

$employees = Employee::all();
foreach ($employees as $employee) {
    $count = Attendance::where('employee_id', $employee->id)
        ->whereYear('date', 2026)
        ->whereMonth('date', 7)
        ->count();
    echo $employee->name . ' attendances: ' . $count . "\n";
}

$sample = Attendance::whereYear('date', 2026)->whereMonth('date', 7)->limit(10)->get();
foreach ($sample as $att) {
    $checkIn = $att->check_in ? $att->check_in->format('H:i:s') : 'null';
    $checkOut = $att->check_out ? $att->check_out->format('H:i:s') : 'null';
    echo sprintf('%s %s %s %s\n', $att->employee_id, $att->date->format('Y-m-d'), $checkIn, $checkOut);
}
