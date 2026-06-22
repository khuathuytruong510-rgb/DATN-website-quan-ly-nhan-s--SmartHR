<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo user nhân viên test
        $user = User::firstOrCreate(
            ['email' => 'employee@test.com'],
            [
                'name' => 'Nhân Viên Test',
                'password' => Hash::make('password'),
                'api_token' => bin2hex(random_bytes(32)),
            ]
        );

        // Tạo hoặc cập nhật employee liên kết với user
        Employee::where('email', 'employee@test.com')->update([
            'user_id' => $user->id,
        ]);

        // Nếu chưa có employee nào, tạo mới
        if (!Employee::where('user_id', $user->id)->exists()) {
            Employee::create([
                'user_id' => $user->id,
                'name' => 'Nhân Viên Test',
                'email' => 'employee@test.com',
                'position' => 'Developer',
                'department_id' => 1,
                'status' => 'active',
            ]);
        }

        // Tạo user admin test
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Test',
                'password' => Hash::make('password'),
                'api_token' => bin2hex(random_bytes(32)),
            ]
        );

        $this->command->info('Employee user seeder completed!');
    }
}
