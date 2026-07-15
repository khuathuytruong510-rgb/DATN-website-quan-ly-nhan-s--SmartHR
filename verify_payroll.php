<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;

$p = Payroll::with('employee')->orderBy('id')->first();

echo "=== PAYROLL VERIFICATION ===\n";
echo "Employee: " . $p->employee->name . "\n";
echo "Base Salary: " . number_format($p->base_salary) . "\n";
echo "Daily Salary: " . number_format($p->daily_salary) . "\n";
echo "Working Days: " . $p->working_days . "\n";
echo "Working Salary: " . number_format($p->working_salary) . "\n";
echo "Overtime Hours: " . $p->overtime_hours . "\n";
echo "OT Day Salary: " . number_format($p->overtime_day_salary) . "\n";
echo "OT Hour Salary: " . number_format($p->overtime_hour_salary) . "\n";
echo "OT Total: " . number_format($p->overtime_salary) . "\n";
echo "Allowance: " . number_format($p->allowance) . "\n";
echo "Bonus: " . number_format($p->bonus) . "\n";
echo "Insurance: " . number_format($p->insurance) . "\n";
echo "Tax: " . number_format($p->tax) . "\n";
echo "Total Salary: " . number_format($p->total_salary) . "\n";
