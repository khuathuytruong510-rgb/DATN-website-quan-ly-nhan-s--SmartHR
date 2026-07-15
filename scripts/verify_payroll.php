<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\PayrollCalculationService;

$service = new PayrollCalculationService();
$reflector = new ReflectionClass($service);

$normalize = $reflector->getMethod('normalizeOvertimeHours');
$normalize->setAccessible(true);

$tax = $reflector->getMethod('calculateTax');
$tax->setAccessible(true);

echo json_encode([
    'normalized_120_minutes' => $normalize->invoke($service, 120),
    'normalized_7200_seconds' => $normalize->invoke($service, 7200),
    'normalized_2_5_hours' => $normalize->invoke($service, 2.5),
    'tax_11m' => $tax->invoke($service, 11000000),
    'tax_20m' => $tax->invoke($service, 20000000),
    'tax_32m' => $tax->invoke($service, 32000000),
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
