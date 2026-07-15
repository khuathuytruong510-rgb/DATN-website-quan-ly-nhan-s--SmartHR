<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\PayrollCalculationService;

$employee = Employee::find(4);
if (! $employee) {
    echo "Employee 4 not found\n";
    exit(1);
}

$month = 6;
$year = 2026;
$service = new PayrollCalculationService();
$payroll = $service->calculate($employee, $month, $year);

echo "Recalculated payroll for employee {$employee->id} month {$year}-{$month}\n";
echo "Base Salary: {$payroll->base_salary}\n";
echo "Working Days: {$payroll->working_days}\n";
echo "Overtime Hours: {$payroll->overtime_hours}\n";
echo "Overtime Hour Salary: {$payroll->overtime_hour_salary}\n";
echo "Overtime Salary: {$payroll->overtime_salary}\n";
echo "Insurance: {$payroll->insurance}\n";
echo "Tax: {$payroll->tax}\n";
echo "Total Salary: {$payroll->total_salary}\n";

echo "\nAttendance metrics from raw data:\n";
$attendances = Attendance::where('employee_id', $employee->id)
    ->whereYear('date', $year)
    ->whereMonth('date', $month)
    ->get();
$counter = 0;
foreach ($attendances as $attendance) {
    $metrics = (new App\Services\AttendanceCalculationService())->calculateAttendanceMetrics($attendance);
    echo sprintf(
        "%s check_in=%s check_out=%s work=%0.2f ot=%0.2f new_status=%s stored_work=%s stored_ot=%s\n",
        $attendance->date->format('Y-m-d'),
        $attendance->check_in ? $attendance->check_in->format('Y-m-d H:i:s') : 'NULL',
        $attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'NULL',
        $metrics['work_hours'],
        $metrics['overtime_hours'],
        $metrics['status'],
        $attendance->work_hours,
        $attendance->overtime_hours
    );
    $counter++;
    if ($counter >= 15) break;
}
