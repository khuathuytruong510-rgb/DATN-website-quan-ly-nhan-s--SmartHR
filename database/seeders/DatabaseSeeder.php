<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
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
        ]);

        // Departments (idempotent)
        $it = Department::updateOrCreate(['name' => 'IT'], [
            'manager' => 'Nguyễn Văn An',
            'description' => 'Information Technology Department',
            'employee_count' => 4,
        ]);

        $hr = Department::updateOrCreate(['name' => 'HR'], [
            'manager' => 'Trần Thị Bích',
            'description' => 'Human Resources Department',
            'employee_count' => 3,
        ]);

        $sales = Department::updateOrCreate(['name' => 'Sales'], [
            'manager' => 'Lê Văn Cường',
            'description' => 'Sales & Marketing Department',
            'employee_count' => 3,
        ]);

        $finance = Department::updateOrCreate(['name' => 'Finance'], [
            'manager' => 'Phạm Thị Dung',
            'description' => 'Finance and Accounting',
            'employee_count' => 2,
        ]);

        $marketing = Department::updateOrCreate(['name' => 'Marketing'], [
            'manager' => 'Hoàng Văn Nam',
            'description' => 'Marketing and Communications',
            'employee_count' => 2,
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
            'name' => 'Nguyễn Văn An',
            'position' => 'Senior Developer',
            'department_id' => $it->id,
            'status' => 'active',
            'avatar' => '/images/avatars/nguyenvana.svg',
        ]);

        $employee3 = Employee::updateOrCreate(['email' => 'tranthib@example.com'], [
            'name' => 'Trần Thị Bích',
            'position' => 'HR Manager',
            'department_id' => $hr->id,
            'status' => 'active',
            'avatar' => '/images/avatars/tranthib.svg',
        ]);

        $employee4 = Employee::updateOrCreate(['email' => 'levanc@example.com'], [
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
            'department_id' => $marketing->id,
            'status' => 'active',
            'avatar' => '/images/avatars/hoangve.svg',
        ]);

        // Contracts (idempotent)
        Contract::updateOrCreate([
            'employee_id' => $employee2->id,
            'title' => 'Full-time Developer Contract',
        ], [
            'salary' => 18000000,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'active',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee3->id,
            'title' => 'HR Manager Contract',
        ], [
            'salary' => 22000000,
            'start_date' => '2025-03-15',
            'end_date' => '2026-03-14',
            'status' => 'active',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee4->id,
            'title' => 'Sales Executive Contract',
        ], [
            'salary' => 15000000,
            'start_date' => '2025-05-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
        ]);

        Contract::updateOrCreate([
            'employee_id' => $employee1->id,
            'title' => 'Executive Contract',
        ], [
            'salary' => 45000000,
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
            'month' => '2026-06',
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
            'month' => '2026-06',
        ], [
            'base_salary' => 18000000,
            'allowance' => 2000000,
            'deduction' => 500000,
            'total_salary' => 19500000,
            'status' => 'approved',
            'paid_at' => null,
        ]);

        Payroll::updateOrCreate([
            'employee_id' => $employee3->id,
            'month' => '2026-06',
        ], [
            'base_salary' => 22000000,
            'allowance' => 1500000,
            'deduction' => 0,
            'total_salary' => 23500000,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        // Leave requests sample data
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
    }
}
