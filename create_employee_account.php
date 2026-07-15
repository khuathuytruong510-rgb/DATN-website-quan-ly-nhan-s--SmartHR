<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;

$department = Department::firstOrCreate(
    ['name' => 'Phòng IT'],
    ['description' => 'Phòng thử nghiệm']
);

$user = User::firstOrCreate(
    ['email' => 'nhanvien@example.com'],
    [
        'name' => 'Nhân Viên Test',
        'password' => Hash::make('password123'),
    ]
);

$user->update([
    'is_admin' => false,
    'is_hr' => false,
    'is_accountant' => false,
    'is_locked' => false,
]);

Employee::firstOrCreate(
    ['user_id' => $user->id],
    [
        'name' => $user->name,
        'email' => $user->email,
        'position' => 'Nhân viên',
        'department_id' => $department->id,
        'status' => 'active',
    ]
);

echo 'CREATED' . PHP_EOL;
echo $user->email . PHP_EOL;
echo 'password123' . PHP_EOL;
