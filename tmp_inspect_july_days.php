<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Attendance;

$days = Attendance::whereYear('date', 2026)
    ->whereMonth('date', 7)
    ->distinct()
    ->orderBy('date')
    ->pluck('date');

echo 'unique days: ' . count($days) . "\n";
foreach ($days as $day) {
    echo $day . "\n";
}
