<?php

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$positionSalaries = [
    'Giám đốc' => [40000000, 5000000, 'C-level', 30000000, 50000000],
    'Phó Giám đốc' => [30000000, 3000000, 'Director', 25000000, 35000000],
    'Trưởng phòng Nhân sự' => [20000000, 2000000, 'Manager', 18000000, 25000000],
    'Trưởng phòng Kế toán' => [20000000, 2000000, 'Manager', 18000000, 25000000],
    'Trưởng phòng IT' => [25000000, 2000000, 'Manager', 20000000, 30000000],
    'Trưởng phòng Kinh doanh' => [22000000, 2000000, 'Manager', 18000000, 28000000],
    'Chuyên viên Nhân sự' => [12000000, 1000000, 'Staff', 10000000, 15000000],
    'Kế toán viên' => [12000000, 1000000, 'Staff', 10000000, 15000000],
    'Backend Developer' => [18000000, 1500000, 'Staff', 15000000, 25000000],
    'Frontend Developer' => [16000000, 1500000, 'Staff', 13000000, 22000000],
    'Senior Developer' => [22000000, 2000000, 'Senior', 18000000, 30000000],
    'CTO' => [45000000, 5000000, 'C-level', 35000000, 60000000],
    'Phó Phòng Công Nghệ Thông Tin' => [18000000, 1500000, 'Deputy Manager', 15000000, 25000000],
    'Nhân viên Kinh doanh' => [10000000, 800000, 'Staff', 8000000, 15000000],
    'Sales Executive' => [12000000, 1000000, 'Staff', 10000000, 18000000],
    'Marketing Lead' => [18000000, 1500000, 'Lead', 15000000, 25000000],
    'HR' => [12000000, 1000000, 'Staff', 10000000, 15000000],
    'Nhân viên Văn phòng' => [9000000, 500000, 'Staff', 7000000, 12000000],
    'Thực tập sinh' => [4000000, 0, 'Intern', 3000000, 5000000],
];

foreach ($positionSalaries as $name => [$baseSalary, $allowance, $level, $min, $max]) {
    Position::updateOrCreate(
        ['name' => $name],
        [
            'description' => $name,
            'level' => $level,
            'salary_range_min' => $min,
            'salary_range_max' => $max,
            'allowance' => $allowance,
            'base_salary' => $baseSalary,
        ]
    );
}

$positions = Position::all();

foreach (Employee::all() as $employee) {
    if ($employee->position_id && $positions->firstWhere('id', $employee->position_id)) {
        continue;
    }

    $positionName = mb_strtolower(trim((string) $employee->position));
    if ($positionName === '') {
        continue;
    }

    $matched = $positions->first(function (Position $position) use ($positionName) {
        $name = mb_strtolower($position->name);

        return $name === $positionName
            || str_contains($name, $positionName)
            || str_contains($positionName, $name);
    });

    if ($matched) {
        $employee->position_id = $matched->id;
        $employee->position = $matched->name;
        $employee->save();
    }
}

echo "Configured position salaries:\n";
foreach (Position::select('id', 'name', 'base_salary', 'salary_range_min', 'allowance')->orderBy('id')->get() as $position) {
    echo sprintf(
        "%d | %s | base=%s | min=%s | allowance=%s\n",
        $position->id,
        $position->name,
        number_format((int) $position->base_salary, 0, ',', '.'),
        number_format((int) $position->salary_range_min, 0, ',', '.'),
        number_format((int) $position->allowance, 0, ',', '.')
    );
}

echo "\nEmployees without configured position:\n";
$missing = Employee::query()
    ->whereNull('position_id')
    ->orWhereDoesntHave('positionDetail')
    ->get(['id', 'name', 'position', 'position_id']);

if ($missing->isEmpty()) {
    echo "None\n";
} else {
    foreach ($missing as $employee) {
        echo sprintf("%d | %s | position=%s | position_id=%s\n", $employee->id, $employee->name, $employee->position, $employee->position_id);
    }
}
