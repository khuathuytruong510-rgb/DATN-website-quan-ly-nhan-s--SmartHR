<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\PayrollCalculationService();
$employee = App\Models\Employee::where('status', 'active')->first();
$service->calculate($employee, 7, 2026);
echo "Payroll recalculated for {$employee->name}\n";
