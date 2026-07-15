<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;

$duplicates = Payroll::selectRaw('employee_id, month, COUNT(*) as total')
    ->groupBy('employee_id', 'month')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicates->isEmpty()) {
    echo "No duplicate payroll records found\n";
    exit;
}

foreach ($duplicates as $dup) {
    echo sprintf("employee_id=%d month=%s duplicates=%d\n", $dup->employee_id, $dup->month, $dup->total);
}
