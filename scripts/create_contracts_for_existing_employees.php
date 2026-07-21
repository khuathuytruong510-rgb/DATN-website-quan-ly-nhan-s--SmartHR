<?php

use App\Models\Contract;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractService;
use App\Support\ContractFixedTerms;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$actor = User::query()
    ->where('is_admin', true)
    ->orWhere('is_hr', true)
    ->orderByDesc('is_admin')
    ->orderBy('id')
    ->first();

if (! $actor) {
    echo "Không tìm thấy tài khoản Admin/HR để tạo hợp đồng.\n";
    exit(1);
}

$service = app(ContractService::class);
$employees = Employee::with(['positionDetail', 'contracts'])->orderBy('id')->get();
$created = [];
$skipped = [];

foreach ($employees as $employee) {
    if ($employee->contracts->isNotEmpty()) {
        $skipped[] = sprintf('%s đã có %d hợp đồng', $employee->name, $employee->contracts->count());
        continue;
    }

    $position = $employee->positionDetail;
    $baseSalary = (int) ($position?->base_salary ?: $position?->salary_range_min ?: 0);
    $allowance = (int) ($position?->allowance ?: 0);
    $startDate = $employee->start_date
        ? Carbon::parse($employee->start_date)->toDateString()
        : now()->toDateString();
    $endDate = Carbon::parse($startDate)->addYear()->subDay()->toDateString();

    $contract = $service->createContract($actor, [
        'employee_id' => $employee->id,
        'title' => 'Hợp đồng lao động xác định thời hạn',
        'contract_type' => 'fixed_term',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'base_salary' => $baseSalary,
        'allowance' => $allowance,
        'bonus' => 0,
        'payment_method' => 'bank_transfer',
        'terms' => ContractFixedTerms::forType('fixed_term'),
        'contract_content' => ContractFixedTerms::forType('fixed_term'),
        'workplace' => 'Văn phòng công ty',
        'working_schedule' => 'morning',
        'benefits' => 'Theo quy chế phúc lợi của công ty.',
        'allowed_unpaid_leave_days_per_month' => 1,
        'allowed_makeup_attendance_per_month' => 3,
        'allowed_maternity_leave_days' => 180,
        'employee_signed_at' => now()->toDateTimeString(),
        'director_signed_at' => now()->toDateTimeString(),
    ]);

    $created[] = sprintf(
        '%s | %s | %s | %s - %s | lương %s | phụ cấp %s | %s',
        $employee->name,
        $employee->positionDetail?->name ?? $employee->position ?? 'N/A',
        $contract->contract_code,
        optional($contract->start_date)->format('d/m/Y'),
        optional($contract->end_date)->format('d/m/Y'),
        number_format((int) $contract->base_salary, 0, ',', '.'),
        number_format((int) $contract->allowance, 0, ',', '.'),
        $contract->status
    );
}

echo "Đã tạo " . count($created) . " hợp đồng:\n";
foreach ($created as $line) {
    echo "- {$line}\n";
}

echo "\nBỏ qua " . count($skipped) . " nhân viên đã có hợp đồng:\n";
foreach ($skipped as $line) {
    echo "- {$line}\n";
}

echo "\nTổng hợp đồng hiện có: " . Contract::count() . "\n";
