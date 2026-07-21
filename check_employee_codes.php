<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$employees = \App\Models\Employee::select('id', 'name', 'employee_code', 'department_id')->with('department')->get();

echo "=== Employee Codes ===\n";
echo str_pad('Name', 30) . str_pad('Code', 15) . "Department\n";
echo str_repeat('-', 60) . "\n";

foreach($employees as $e) {
    echo str_pad($e->name, 30) . str_pad($e->employee_code ?? '(empty)', 15) . ($e->department->name ?? 'N/A') . "\n";
}

echo "\n✓ Total: " . $employees->count() . " employees\n";
