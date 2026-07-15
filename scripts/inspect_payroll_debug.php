<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\Attendance;

$payroll = Payroll::where('month', '2026-06')->first();
if (! $payroll) {
    echo "No payroll for 2026-06\n";
    exit(0);
}

$employeeId = $payroll->employee_id;

echo "Payroll ID: {$payroll->id}\n";
echo "Employee ID: {$employeeId}\n";
echo "Month: {$payroll->month}\n";
echo "Base Salary: {$payroll->base_salary}\n";
echo "Working Days: {$payroll->working_days}\n";
echo "Required Working Days: {$payroll->required_working_days}\n";
echo "Overtime Hours: {$payroll->overtime_hours}\n";
echo "Overtime Hour Salary: {$payroll->overtime_hour_salary}\n";
echo "Overtime Salary: {$payroll->overtime_salary}\n";
echo "Insurance: {$payroll->insurance}\n";
echo "Tax: {$payroll->tax}\n";
echo "Total Salary: {$payroll->total_salary}\n";

echo "\nAttendance rows for employee {$employeeId} in 2026-06:\n";
$attendances = Attendance::where('employee_id', $employeeId)
    ->whereYear('date', 2026)
    ->whereMonth('date', 6)
    ->get();

foreach ($attendances as $attendance) {
    $checkIn = $attendance->check_in ? $attendance->check_in->format('Y-m-d H:i:s') : 'NULL';
    $checkOut = $attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'NULL';
    echo sprintf(
        "%s work=%s ot=%s check_in=%s check_out=%s\n",
        $attendance->date?->format('Y-m-d') ?: 'NULL',
        $attendance->work_hours,
        $attendance->overtime_hours,
        $checkIn,
        $checkOut
    );
}
