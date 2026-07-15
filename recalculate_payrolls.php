<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;

$service = new PayrollCalculationService();
$employees = Employee::where('status', 'active')->get();
$processed = 0;

foreach ($employees as $employee) {
    $payrolls = Payroll::where('employee_id', $employee->id)->get();

    foreach ($payrolls as $payroll) {
        $parts = explode('-', (string) $payroll->month);
        if (count($parts) >= 2) {
            $service->calculate($employee, (int) $parts[1], (int) $parts[0]);
            $processed++;
        }
    }
}

echo "Processed payroll rows: {$processed}\n";
