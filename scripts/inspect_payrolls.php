<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;

$payroll = Payroll::with('employee')->orderByDesc('id')->first();

if (!$payroll) {
    echo "No payroll rows found\n";
    exit(0);
}

echo json_encode([
    'employee' => $payroll->employee?->name,
    'month' => $payroll->month,
    'base_salary' => $payroll->base_salary,
    'working_days' => $payroll->working_days,
    'required_working_days' => $payroll->required_working_days,
    'overtime_hours' => $payroll->overtime_hours,
    'overtime_day_salary' => $payroll->overtime_day_salary,
    'overtime_hour_salary' => $payroll->overtime_hour_salary,
    'overtime_salary' => $payroll->overtime_salary,
    'insurance' => $payroll->insurance,
    'tax' => $payroll->tax,
    'total_salary' => $payroll->total_salary,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
