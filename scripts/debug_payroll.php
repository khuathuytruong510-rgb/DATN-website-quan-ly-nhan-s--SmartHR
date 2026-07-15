<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\Attendance;

$payroll = Payroll::orderBy('id')->first();
$attendance = Attendance::where('employee_id', $payroll->employee_id)
    ->where('date', '>=', date('Y-m-01'))
    ->first();

echo "=== PAYROLL ===\n";
echo "Base Salary: " . $payroll->base_salary . "\n";
echo "Daily Salary: " . $payroll->daily_salary . "\n";
echo "Working Days: " . $payroll->working_days . "\n";
echo "Working Salary: " . $payroll->working_salary . "\n";
echo "Overtime Hours: " . $payroll->overtime_hours . "\n";
echo "Overtime Day Salary: " . $payroll->overtime_day_salary . "\n";
echo "Overtime Hour Salary: " . $payroll->overtime_hour_salary . "\n";
echo "Total Salary: " . $payroll->total_salary . "\n";

echo "\n=== FIRST ATTENDANCE ===\n";
if ($attendance) {
    echo "Attendance ID: " . $attendance->id . "\n";
    echo "Work Hours: " . $attendance->work_hours . "\n";
    echo "Overtime Hours: " . $attendance->overtime_hours . "\n";
}
