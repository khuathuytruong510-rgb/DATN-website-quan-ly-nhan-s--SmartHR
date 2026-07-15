<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employee = App\Models\Employee::where('status', 'active')->first();
$attendances = App\Models\Attendance::where('employee_id', $employee->id)
    ->whereMonth('date', 7)
    ->whereYear('date', 2026)
    ->get();

echo 'employee=' . $employee->name . PHP_EOL;
echo 'attendance_count=' . $attendances->count() . PHP_EOL;
foreach ($attendances->take(5) as $a) {
    echo $a->date . ' work=' . ($a->work_hours ?? 0) . ' ot=' . ($a->overtime_hours ?? 0) . PHP_EOL;
}
