<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Schema;
use App\Models\Payroll;

$columns = Schema::getColumnListing('payrolls');
echo 'Columns: ' . implode(', ', $columns) . "\n";

$invalid = Payroll::whereRaw("month NOT REGEXP '^[0-9]{4}-[0-9]{2}$'")->get();
echo 'Invalid count: ' . $invalid->count() . "\n";
foreach ($invalid as $p) {
    echo sprintf('ID=%d employee_id=%d month=%s year=%s total_salary=%s\n', $p->id, $p->employee_id, $p->month, $p->year, $p->total_salary);
}
