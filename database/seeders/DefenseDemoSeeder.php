<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriodLock;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService as W;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dữ liệu bảo vệ: 4 tài khoản + snapshot đúng trạng thái.
 * Chạy: php artisan db:seed --class=DefenseDemoSeeder
 */
class DefenseDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PASSWORD = '123456';

    public function run(): void
    {
        $hcns = Department::updateOrCreate(['code' => 'HR'], [
            'name' => 'Phòng Nhân sự (HR)',
            'manager' => 'Trần Thị Bích',
            'description' => 'Quản lý nhân sự, hợp đồng, chấm công, nghỉ phép.',
        ]);
        $cntt = Department::updateOrCreate(['code' => 'IT'], [
            'name' => 'Phòng IT',
            'manager' => 'Nguyễn Văn Nam',
            'description' => 'Phát triển và vận hành hệ thống.',
        ]);
        $kttc = Department::updateOrCreate(['code' => 'KTTC'], [
            'name' => 'Phòng Kế toán - Tài chính',
            'manager' => 'Lê Thị Mai',
            'description' => 'Tính lương và thanh toán.',
        ]);
        $bgd = Department::updateOrCreate(['code' => 'BGD'], [
            'name' => 'Ban Giám đốc',
            'manager' => 'Phạm Thị Dung',
            'description' => 'Điều hành và phê duyệt cấp cao.',
        ]);

        $hr = $this->user('hr@smarthr.com', 'Trần Thị Bích', ['is_hr' => true]);
        $kt = $this->user('accountant@smarthr.com', 'Lê Thị Mai', ['is_accountant' => true]);
        $gd = $this->user('giamdoc@smarthr.com', 'Phạm Thị Dung', ['is_director' => true]);
        $nv = $this->user('nv@smarthr.com', 'Nguyễn Văn Nam', []);

        $this->employee($gd, $bgd, 'BGD0001', 'Giám đốc', []);
        $nam = $this->employee($nv, $cntt, 'CNTT-DEMO-01', 'Lập trình viên', [
            'bank_name' => 'MB Bank',
            'account_number' => '111122223333',
            'account_holder' => 'NGUYEN VAN NAM',
        ]);
        $hoa = $this->employeeBare('hoa@smarthr.com', 'Trần Thị Hoa', $hcns, 'HCNS-DEMO-01', 'Chuyên viên nhân sự', [
            'bank_name' => 'Vietcombank',
            'account_number' => '222233334444',
            'account_holder' => 'TRAN THI HOA',
        ]);
        $khoa = $this->employeeBare('khoa@smarthr.com', 'Lê Minh Khoa', $cntt, 'CNTT-DEMO-02', 'Tester', [
            'bank_name' => 'Techcombank',
            'account_number' => '333344445555',
            'account_holder' => 'LE MINH KHOA',
        ]);
        $bao = $this->employeeBare('bao@smarthr.com', 'Phạm Quốc Bảo', $kttc, 'KTTC-DEMO-01', 'Kế toán viên', [
            'bank_name' => 'BIDV',
            'account_number' => '444455556666',
            'account_holder' => 'PHAM QUOC BAO',
        ]);

        $this->lockPeriod(8, 2026, $hr);

        $this->payroll($khoa, 8, 2026, W::CALCULATED, 7800000);
        $this->payroll($hoa, 8, 2026, W::HR_CHECKED, 8200000);
        $this->payroll($nam, 8, 2026, W::DIRECTOR_APPROVED, 8800000);
        $this->payroll($bao, 8, 2026, W::EMPLOYEE_CONFIRMED, 9100000);

        $this->attendances($nam);
        $this->correction($nam);
        $this->leave($nam, $hoa);

        $this->contractWaitingEmployee($nam, $hr);
        $this->contractWaitingDirector($hoa, $hr);

        $this->command?->info('Defense demo ready. Password for all demo accounts: '.self::PASSWORD);
        $this->command?->info('HR  hr@smarthr.com');
        $this->command?->info('KT  accountant@smarthr.com');
        $this->command?->info('GĐ  giamdoc@smarthr.com');
        $this->command?->info('NV  nv@smarthr.com');
    }

    private function user(string $email, string $name, array $roles): User
    {
        return User::updateOrCreate(['email' => $email], array_merge([
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'api_token' => Str::random(60),
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => false,
            'is_locked' => false,
        ], $roles));
    }

    private function employee(User $user, Department $dept, string $code, string $position, array $extra): Employee
    {
        return Employee::updateOrCreate(['email' => $user->email], array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => $position,
            'department_id' => $dept->id,
            'status' => 'active',
            'employee_code' => $code,
            'start_date' => '2025-01-15',
            'leave_balance' => 12,
        ], $extra));
    }

    private function employeeBare(string $email, string $name, Department $dept, string $code, string $position, array $extra): Employee
    {
        return Employee::updateOrCreate(['email' => $email], array_merge([
            'name' => $name,
            'position' => $position,
            'department_id' => $dept->id,
            'status' => 'active',
            'employee_code' => $code,
            'start_date' => '2025-03-01',
            'leave_balance' => 12,
        ], $extra));
    }

    private function lockPeriod(int $month, int $year, User $hr): void
    {
        PayrollPeriodLock::updateOrCreate(
            ['month' => $month, 'year' => $year],
            [
                'is_locked' => true,
                'locked_at' => now()->subDays(2),
                'locked_by' => $hr->id,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'unlock_reason' => null,
            ]
        );

        ActivityLog::updateOrCreate(
            ['action' => 'payroll_period_locked', 'meta' => sprintf('period:%02d/%d', $month, $year)],
            ['user_id' => $hr->id]
        );
    }

    private function payroll(Employee $employee, int $month, int $year, string $status, float $total): Payroll
    {
        $base = 7800000;
        $data = [
            'base_salary' => $base,
            'daily_salary' => round($base / 26, 0),
            'required_working_days' => 26,
            'working_days' => 22,
            'working_salary' => 6600000,
            'allowance' => 500000,
            'bonus' => 0,
            'insurance' => 819000,
            'tax' => 0,
            'deduction' => 0,
            'overtime_salary' => 0,
            'total_salary' => $total,
            'status' => $status,
            'payout_bank_name' => $employee->bank_name,
            'payout_account_number' => $employee->account_number,
            'payout_account_holder' => $employee->account_holder,
            'paid_at' => null,
            'paid_by' => null,
            'issue_report' => null,
        ];

        if ($status === W::DIRECTOR_APPROVED) {
            $data['confirmation_status'] = 'pending';
            $data['confirmation_deadline'] = now()->addDays(3);
            $data['confirmation_token'] = Str::random(48);
            $data['sent_at'] = now()->subDay();
            $data['email_status'] = 'sent';
        }

        if ($status === W::EMPLOYEE_CONFIRMED) {
            $data['confirmation_status'] = 'confirmed';
            $data['confirmed_at'] = now()->subHours(6);
        }

        return Payroll::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            $data
        );
    }

    private function attendances(Employee $nam): void
    {
        foreach (['2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07'] as $date) {
            Attendance::updateOrCreate(
                ['employee_id' => $nam->id, 'date' => $date],
                [
                    'check_in' => '08:00:00',
                    'check_out' => '17:05:00',
                    'status' => 'present',
                    'work_hours' => 8.08,
                    'attendance_method' => 'web',
                    'notes' => 'Demo bảo vệ',
                ]
            );
        }

        Attendance::updateOrCreate(
            ['employee_id' => $nam->id, 'date' => '2026-08-10'],
            [
                'check_in' => '08:42:00',
                'check_out' => '17:00:00',
                'status' => 'late',
                'late_minutes' => 42,
                'work_hours' => 8.3,
                'attendance_method' => 'web',
                'notes' => 'Đến muộn (kỳ 08 đã chốt — chỉ xem).',
            ]
        );
        Attendance::updateOrCreate(
            ['employee_id' => $nam->id, 'date' => '2026-08-28'],
            [
                'check_in' => '08:02:00',
                'check_out' => '17:10:00',
                'status' => 'present',
                'work_hours' => 8.13,
                'attendance_method' => 'web',
                'notes' => 'Demo bảo vệ',
            ]
        );
    }

    private function correction(Employee $nam): void
    {
        $row = Attendance::updateOrCreate(
            ['employee_id' => $nam->id, 'date' => '2026-09-01'],
            [
                'check_in' => '08:45:00',
                'check_out' => '17:00:00',
                'status' => 'late',
                'late_minutes' => 45,
                'work_hours' => 8.25,
                'attendance_method' => 'web',
                'notes' => 'Quên check-in đúng giờ. Chờ HR xử lý (kỳ 09/2026 chưa chốt).',
            ]
        );

        AttendanceAdjustmentRequest::updateOrCreate(
            ['attendance_id' => $row->id, 'status' => AttendanceAdjustmentRequest::PENDING],
            [
                'employee_id' => $nam->id,
                'work_date' => '2026-09-01',
                'current_check_in' => '08:45:00',
                'current_check_out' => '17:00:00',
                'requested_check_in' => '08:00:00',
                'requested_check_out' => '17:00:00',
                'reason' => 'Máy chấm công lỗi. Giờ vào thực tế 08:00.',
            ]
        );
    }

    private function leave(Employee $nam, Employee $hoa): void
    {
        LeaveRequest::updateOrCreate(
            [
                'employee_id' => $nam->id,
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-11',
            ],
            [
                'days' => 2,
                'type' => 'annual',
                'reason' => 'Việc gia đình. Demo: HR duyệt đơn pending (kỳ 09 chưa chốt).',
                'status' => 'pending',
            ]
        );

        LeaveRequest::updateOrCreate(
            [
                'employee_id' => $hoa->id,
                'start_date' => '2026-08-12',
                'end_date' => '2026-08-12',
            ],
            [
                'days' => 1,
                'type' => 'annual',
                'reason' => 'Đã duyệt (kỳ 08 đã chốt — chỉ xem).',
                'status' => 'approved',
                'approved_at' => now()->subDays(10),
            ]
        );
    }

    private function contractWaitingEmployee(Employee $nam, User $hr): void
    {
        Contract::updateOrCreate(
            ['employee_id' => $nam->id, 'contract_code' => 'HD-DEMO-NV-2026'],
            [
                'title' => 'Hợp đồng lao động xác định thời hạn',
                'contract_type' => 'fixed_term',
                'start_date' => '2026-09-01',
                'end_date' => '2027-08-31',
                'salary' => 18000000,
                'base_salary' => 18000000,
                'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                'created_by' => $hr->id,
                'employee_signed_at' => null,
                'director_signed_at' => null,
                'notes' => 'Demo: NV đăng nhập và ký phía nhân viên.',
            ]
        );
    }

    private function contractWaitingDirector(Employee $hoa, User $hr): void
    {
        Contract::updateOrCreate(
            ['employee_id' => $hoa->id, 'contract_code' => 'HD-DEMO-GD-2026'],
            [
                'title' => 'Hợp đồng lao động xác định thời hạn',
                'contract_type' => 'fixed_term',
                'start_date' => '2026-08-01',
                'end_date' => '2027-07-31',
                'salary' => 16000000,
                'base_salary' => 16000000,
                'status' => Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
                'created_by' => $hr->id,
                'employee_signed_at' => now()->subDays(3),
                'director_signed_at' => null,
                'notes' => 'Demo: GĐ ký phía công ty sau khi NV đã ký.',
            ]
        );
    }
}
