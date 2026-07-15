<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Services\PayrollCalculationService;

$employee = Employee::where('status', 'active')->first();

if (!$employee) {
    echo "No active employee found\n";
    exit;
}

echo "Employee: " . $employee->name . "\n";
echo "Position: " . $employee->position . "\n";

$service = new PayrollCalculationService();

// Manually calculate for June 2026
try {
    $payroll = $service->calculate($employee, 6, 2026);
    
    echo "\n=== CALCULATED PAYROLL ===\n";
    echo "Base Salary: " . $payroll->base_salary . "\n";
    echo "Daily Salary: " . $payroll->daily_salary . "\n";
    echo "Working Days: " . $payroll->working_days . "\n";
    echo "Working Salary: " . $payroll->working_salary . "\n";
    echo "Overtime Hours: " . $payroll->overtime_hours . "\n";
    echo "Overtime Day Salary: " . $payroll->overtime_day_salary . "\n";
    echo "Overtime Hour Salary: " . $payroll->overtime_hour_salary . "\n";
    echo "Insurance: " . $payroll->insurance . "\n";
    echo "Tax: " . $payroll->tax . "\n";
    echo "Total Salary: " . $payroll->total_salary . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
