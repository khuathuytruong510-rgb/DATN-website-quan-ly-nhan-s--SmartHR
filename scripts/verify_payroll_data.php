<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\Employee;

$names = [
    'Đỗ Đức Doanh',
    'Hoàng Văn Nam',
    'Phạm Thị Dung',
    'Khuất Huy Trường',
    'Lê Văn Cường',
];

foreach ($names as $name) {
    $employee = Employee::where('name', $name)->first();
    if (! $employee) {
        echo "Employee not found: {$name}\n";
        continue;
    }

    $payrolls = Payroll::where('employee_id', $employee->id)
        ->whereIn('month', ['2026-06', '2026-07'])
        ->orderBy('month')
        ->get();

    echo "\n=== {$name} (id={$employee->id}) ===\n";
    if ($payrolls->isEmpty()) {
        echo "No payroll rows found for 2026-06/07\n";
        continue;
    }

    foreach ($payrolls as $payroll) {
        echo sprintf(
            "%s: working_days=%s/%s overtime_hours=%s overtime_day_salary=%s overtime_hour_salary=%s overtime_salary=%s insurance=%s tax=%s total_salary=%s\n",
            $payroll->month,
            $payroll->working_days,
            $payroll->required_working_days,
            $payroll->overtime_hours,
            $payroll->overtime_day_salary,
            $payroll->overtime_hour_salary,
            $payroll->overtime_salary,
            $payroll->insurance,
            $payroll->tax,
            $payroll->total_salary
        );
    }
}
