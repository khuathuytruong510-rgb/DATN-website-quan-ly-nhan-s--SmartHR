<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Database\Seeders\PositionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $user = User::updateOrCreate([
            'email' => 'admin@smarthr.com',
        ], [
            'name' => 'Khuất Huy Trường',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'avatar' => '/images/avatars/truong.svg',
            'is_admin' => true,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => false,
        ]);

        $depts = [
            ['name' => 'Ban Giám đốc',                  'code' => 'BGD',  'manager' => 'Phạm Thị Dung',      'description' => 'Điều hành và quản lý toàn bộ hoạt động của công ty.'],
            ['name' => 'Phòng Nhân sự (HR)',            'code' => 'HR',   'manager' => 'Trần Thị Bích',      'description' => 'Quản lý nhân sự, hồ sơ, chấm công, nghỉ phép và chính sách.'],
            ['name' => 'Phòng Tuyển dụng',              'code' => 'TD',   'manager' => '',                    'description' => 'Tuyển dụng, sàng lọc và onboarding nhân sự mới.'],
            ['name' => 'Phòng C&B',                     'code' => 'CB',   'manager' => '',                    'description' => 'Lương thưởng, chế độ phúc lợi và đãi ngộ cho nhân viên.'],
            ['name' => 'Phòng Đào tạo & Phát triển',    'code' => 'DTPT', 'manager' => '',                    'description' => 'Đào tạo kỹ năng và phát triển năng lực nhân viên.'],
            ['name' => 'Phòng Kế toán - Tài chính',     'code' => 'KTTC', 'manager' => 'Lê Thị Mai',          'description' => 'Quản lý tài chính, kế toán, thu chi, thanh toán lương, báo cáo tài chính.'],
            ['name' => 'Phòng Kinh doanh',              'code' => 'KD',   'manager' => 'Lê Văn Cường',        'description' => 'Tìm kiếm khách hàng, tư vấn, ký kết hợp đồng và phát triển doanh thu.'],
            ['name' => 'Phòng Marketing',               'code' => 'MKT',  'manager' => 'Hoàng Văn Nam',       'description' => 'Xây dựng thương hiệu, quảng bá sản phẩm, triển khai các chiến dịch marketing.'],
            ['name' => 'Phòng IT',                      'code' => 'IT',   'manager' => 'Nguyễn Văn An',       'description' => 'Phát triển phần mềm, vận hành hệ thống và hỗ trợ kỹ thuật.'],
            ['name' => 'Phòng Vận hành',                'code' => 'VH',   'manager' => '',                    'description' => 'Đảm bảo quy trình vận hành và logistics của công ty.'],
            ['name' => 'Phòng Pháp chế',                'code' => 'PC',   'manager' => '',                    'description' => 'Tư vấn pháp lý, soạn thảo hợp đồng và kiểm soát rủi ro.'],
            ['name' => 'Phòng Hành chính',              'code' => 'HC',   'manager' => '',                    'description' => 'Quản lý hành chính, văn phòng và hậu cần nội bộ.'],
        ];

        foreach ($depts as $d) {
            Department::updateOrCreate(['code' => $d['code']], $d);
        }

        $it      = Department::where('code', 'IT')->first();
        $hr      = Department::where('code', 'HR')->first();
        $sales   = Department::where('code', 'KD')->first();
        $finance = Department::where('code', 'KTTC')->first();
        $mkt     = Department::where('code', 'MKT')->first();
        $bgd     = Department::where('code', 'BGD')->first();

        $directorUser = User::updateOrCreate([
            'email' => 'giamdoc@smarthr.com',
        ], [
            'name' => 'Phạm Thị Dung',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => true,
        ]);

        Employee::updateOrCreate(['email' => 'giamdoc@smarthr.com'], [
            'user_id' => $directorUser->id,
            'name' => 'Phạm Thị Dung',
            'position' => 'Giám đốc',
            'department_id' => $bgd?->id,
            'status' => 'active',
            'employee_code' => 'BGD0001',
        ]);

        app(\App\Services\DirectorSuccessionService::class)->ensureOpenTenureFor($directorUser->fresh('employee'));

        $hrUser = User::updateOrCreate([
            'email' => 'hr@smarthr.com',
        ], [
            'name' => 'Trần Thị Bích',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => true,
            'is_accountant' => false,
            'is_director' => false,
        ]);

        Employee::updateOrCreate(['email' => 'hr@smarthr.com'], [
            'user_id' => $hrUser->id,
            'name' => 'Trần Thị Bích',
            'position' => 'Trưởng phòng nhân sự',
            'department_id' => $hr?->id,
            'status' => 'active',
            'employee_code' => 'HCNS-HR-01',
            'gender' => 'female',
            'leave_balance' => 12,
        ]);

        // Employee Users
        $employeeUser1 = User::updateOrCreate([
            'email' => 'nguyenvana@example.com',
        ], [
            'name' => 'Nguyễn Văn An',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
        ]);

        $employeeUser2 = User::updateOrCreate([
            'email' => 'tranthib@example.com',
        ], [
            'name' => 'Trần Thị Bích',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
        ]);

        $employeeUser3 = User::updateOrCreate([
            'email' => 'levanc@example.com',
        ], [
            'name' => 'Lê Văn Cường',
            'password' => Hash::make('123456'),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
        ]);

        // Employees (idempotent by email)
        $employee1 = Employee::updateOrCreate(['email' => 'truongkh@example.com'], [
            'name' => 'Khuất Huy Trường',
            'position' => 'CTO',
            'department_id' => $it->id,
            'status' => 'active',
            'avatar' => '/images/avatars/truong.svg',
        ]);

        $employee2 = Employee::updateOrCreate(['email' => 'nguyenvana@example.com'], [
            'user_id' => $employeeUser1->id,
            'name' => 'Nguyễn Văn An',
            'position' => 'Senior Developer',
            'department_id' => $it->id,
            'status' => 'active',
            'avatar' => '/images/avatars/nguyenvana.svg',
        ]);

        $employee3 = Employee::updateOrCreate(['email' => 'tranthib@example.com'], [
            'user_id' => $employeeUser2->id,
            'name' => 'Trần Thị Bích',
            'position' => 'HR Manager',
            'department_id' => $hr->id,
            'status' => 'active',
            'avatar' => '/images/avatars/tranthib.svg',
        ]);

        $employee4 = Employee::updateOrCreate(['email' => 'levanc@example.com'], [
            'user_id' => $employeeUser3->id,
            'name' => 'Lê Văn Cường',
            'position' => 'Sales Executive',
            'department_id' => $sales->id,
            'status' => 'active',
            'avatar' => '/images/avatars/levanc.svg',
        ]);

        $employee5 = Employee::updateOrCreate(['email' => 'phamtd@example.com'], [
            'name' => 'Phạm Thị Dung',
            'position' => 'Finance Officer',
            'department_id' => $finance->id,
            'status' => 'active',
            'avatar' => '/images/avatars/phamtd.svg',
        ]);

        $employee6 = Employee::updateOrCreate(['email' => 'hoangve@example.com'], [
            'name' => 'Hoàng Văn Nam',
            'position' => 'Marketing Lead',
            'department_id' => $mkt->id,
            'status' => 'active',
            'avatar' => '/images/avatars/hoangve.svg',
        ]);

        // Contracts (idempotent)
        Contract::updateOrCreate([
            'employee_id' => $employee2->id,
            'title' => 'Full-time Developer Contract',
        ], [
            'base_salary' => 18000000,
            'start_date' => '2025-01-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
            'salary' => 18000000,
            'employee_signed_at' => '2025-10-01 09:00:00',
            'director_signed_at' => '2025-10-01 11:00:00',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee3->id,
            'title' => 'HR Manager Contract',
        ], [
            'base_salary' => 22000000,
            'start_date' => '2025-03-15',
            'end_date' => '2026-03-14',
            'status' => 'active',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee4->id,
            'title' => 'Sales Executive Contract',
        ], [
            'base_salary' => 15000000,
            'start_date' => '2025-05-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee1->id,
            'title' => 'Executive Contract',
        ], [
            'base_salary' => 45000000,
            'start_date' => '2024-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        // Attendance sample data
        Attendance::updateOrCreate([
            'employee_id' => $employee1->id,
            'date' => '2026-06-10',
        ], [
            'check_in' => '08:05:00',
            'check_out' => '17:15:00',
            'status' => 'late',
            'notes' => 'Về muộn 5 phút do tắc đường',
        ]);

        Attendance::updateOrCreate([
            'employee_id' => $employee2->id,
            'date' => '2026-06-10',
        ], [
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
            'notes' => 'Đúng giờ',
        ]);

        Attendance::updateOrCreate([
            'employee_id' => $employee3->id,
            'date' => '2026-06-10',
        ], [
            'check_in' => null,
            'check_out' => null,
            'status' => 'leave',
            'notes' => 'Nghỉ ốm',
        ]);

        // Payroll sample data
        Payroll::updateOrCreate([
            'employee_id' => $employee1->id,
            'month' => 6,
            'year' => 2026,
        ], [
            'base_salary' => 45000000,
            'allowance' => 5000000,
            'deduction' => 0,
            'total_salary' => 50000000,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
        ]);

        Payroll::updateOrCreate([
            'employee_id' => $employee2->id,
            'month' => 6,
            'year' => 2026,
        ], [
            'base_salary' => 18000000,
            'allowance' => 2000000,
            'deduction' => 500000,
            'total_salary' => 19500000,
            'status' => 'director_approved',
            'paid_at' => null,
        ]);

        Payroll::updateOrCreate([
            'employee_id' => $employee3->id,
            'month' => 6,
            'year' => 2026,
        ], [
            'base_salary' => 22000000,
            'allowance' => 1500000,
            'deduction' => 0,
            'total_salary' => 23500000,
            'status' => 'calculated',
            'paid_at' => null,
        ]);
        LeaveRequest::updateOrCreate([
            'employee_id' => $employee4->id,
            'start_date' => '2026-06-14',
            'end_date' => '2026-06-16',
        ], [
            'days' => 3,
            'type' => 'annual',
            'reason' => 'Du lịch cùng gia đình',
            'status' => 'approved',
        ]);

        LeaveRequest::updateOrCreate([
            'employee_id' => $employee5->id,
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-21',
        ], [
            'days' => 2,
            'type' => 'sick',
            'reason' => 'Khám sức khỏe',
            'status' => 'pending',
        ]);

        // Sample overtime requests
        \App\Models\OvertimeRequest::updateOrCreate([
            'employee_id' => $employee2->id,
            'date' => '2026-06-12',
        ], [
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'Hoàn thành dự án gấp',
            'status' => 'approved',
        ]);

        \App\Models\OvertimeRequest::updateOrCreate([
            'employee_id' => $employee4->id,
            'date' => '2026-06-15',
        ], [
            'start_time' => '17:30',
            'end_time' => '19:00',
            'reason' => 'Hỗ trợ khách hàng',
            'status' => 'pending',
        ]);

        $this->call(DefenseDemoSeeder::class);
        $this->call(CompleteDemoDataSeeder::class);
    }
}
