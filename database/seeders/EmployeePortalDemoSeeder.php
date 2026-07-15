<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\SupportRequest;
use App\Models\ActivityLog;

class EmployeePortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::limit(3)->get();

        foreach ($employees as $emp) {
            SupportRequest::create([
                'employee_id' => $emp->id,
                'subject' => 'Kiểm tra lương tháng 6',
                'message' => 'Tôi thấy lương tháng 6 chưa đúng, xin kiểm tra giúp.',
                'type' => 'payroll',
                'status' => 'pending',
            ]);

            ActivityLog::create([
                'user_id' => $emp->user_id ?? 1,
                'action' => 'seed_demo',
                'meta' => 'Demo activity for employee ' . $emp->id,
            ]);
        }
    }
}
