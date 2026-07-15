<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Payroll;

$months = Payroll::select('month')->distinct()->orderBy('month')->get();
foreach ($months as $row) {
    echo '[' . $row->month . ']\n';
}

$invalid = Payroll::whereRaw("month NOT REGEXP '^[0-9]{4}-[0-9]{2}$'")->get();
echo 'Invalid count: ' . $invalid->count() . "\n";
foreach ($invalid as $row) {
    echo 'ID=' . $row->id . ' month=' . $row->month . "\n";
}
