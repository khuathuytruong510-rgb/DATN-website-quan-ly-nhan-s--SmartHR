<?php

use App\Models\Employee;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

foreach (Employee::with('positionDetail')->orderBy('id')->get() as $employee) {
    $position = $employee->positionDetail;

    echo sprintf(
        "%d | %s | %s | base=%s | allowance=%s\n",
        $employee->id,
        $employee->name,
        $position?->name ?? $employee->position ?? 'N/A',
        number_format((int) ($position?->base_salary ?? 0), 0, ',', '.'),
        number_format((int) ($position?->allowance ?? 0), 0, ',', '.')
    );
}
