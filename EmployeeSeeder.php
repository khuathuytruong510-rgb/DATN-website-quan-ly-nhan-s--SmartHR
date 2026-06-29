<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo phòng ban nếu chưa tồn tại
        $department = Department::firstOrCreate(
            ['name' => 'Phòng Kỹ Thuật'],
            [
                'description' => 'Bộ phận kỹ thuật và phát triển',
            ]
        );

        $deptHR = Department::firstOrCreate(
            ['name' => 'Phòng Nhân Sự'],
            [
                'description' => 'Bộ phận quản lý nhân sự',
            ]
        );

        // Dữ liệu nhân viên mẫu
        $employees = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'nhanvien1@example.com',
                'password' => 'password123',
                'position' => 'Lập Trình Viên',
                'department_id' => $department->id,
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'nhanvien2@example.com',
                'password' => 'password123',
                'position' => 'Tester',
                'department_id' => $department->id,
            ],
            [
                'name' => 'Lê Văn C',
                'email' => 'nhanvien3@example.com',
                'password' => 'password123',
                'position' => 'Nhân Viên HR',
                'department_id' => $deptHR->id,
            ],
        ];

        // Tạo tài khoản cho mỗi nhân viên
        foreach ($employees as $emp) {
            $user = User::firstOrCreate(
                ['email' => $emp['email']],
                [
                    'name' => $emp['name'],
                    'password' => Hash::make($emp['password']),
                    'api_token' => \Str::random(80),
                ]
            );

            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $emp['name'],
                    'email' => $emp['email'],
                    'position' => $emp['position'],
                    'department_id' => $emp['department_id'],
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('✅ Đã tạo ' . count($employees) . ' tài khoản nhân viên!');
        $this->command->newLine();
        $this->command->info('📋 Danh Sách Tài Khoản:');
        $this->command->table(
            ['Email', 'Mật Khẩu', 'Chức Vụ', 'Phòng Ban'],
            [
                ['nhanvien1@example.com', 'password123', 'Lập Trình Viên', 'Phòng Kỹ Thuật'],
                ['nhanvien2@example.com', 'password123', 'Tester', 'Phòng Kỹ Thuật'],
                ['nhanvien3@example.com', 'password123', 'Nhân Viên HR', 'Phòng Nhân Sự'],
            ]
        );
    }
}