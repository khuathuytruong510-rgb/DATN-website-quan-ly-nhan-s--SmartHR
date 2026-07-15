<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;

// Get all payrolls ordered by employee
$payrolls = Payroll::with('employee')->orderBy('employee_id')->get();

echo "=== ALL PAYROLLS (Sample) ===\n";
foreach ($payrolls->take(3) as $p) {
    echo "\nEmployee: " . $p->employee->name . "\n";
    echo "  Base: " . $p->base_salary . "\n";
    echo "  Daily: " . $p->daily_salary . "\n";
    echo "  Working Days: " . $p->working_days . "\n";
    echo "  Working Salary: " . $p->working_salary . "\n";
    echo "  Overtime Hours: " . $p->overtime_hours . "\n";
    echo "  Overtime Day Salary: " . $p->overtime_day_salary . "\n";
    echo "  Overtime Hour Salary: " . $p->overtime_hour_salary . "\n";
    echo "  Total Salary: " . $p->total_salary . "\n";
}

echo "\n\nTotal Payrolls: " . $payrolls->count() . "\n";
