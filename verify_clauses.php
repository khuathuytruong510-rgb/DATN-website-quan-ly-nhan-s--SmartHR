<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$clausesByType = \App\Models\ContractClause::selectRaw('contract_type, COUNT(*) as count')
    ->where('status', 'active')
    ->groupBy('contract_type')
    ->get();

echo "=== Contract Clauses Summary ===\n";
echo str_repeat('-', 50) . "\n";
echo str_pad('Contract Type', 25) . "Clauses\n";
echo str_repeat('-', 50) . "\n";

$total = 0;
foreach($clausesByType as $item) {
    echo str_pad($item->contract_type, 25) . $item->count . "\n";
    $total += $item->count;
}

echo str_repeat('-', 50) . "\n";
echo str_pad('TOTAL', 25) . $total . "\n";
echo "\n✓ Contract clauses ready to use!\n";
?>
