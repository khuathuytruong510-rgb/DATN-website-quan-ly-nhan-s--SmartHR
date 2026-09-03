<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService as W;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bộ dữ liệu demo đầy đủ:
 * - Mỗi phòng ban ≥ 5 NV, đủ chức vụ
 * - BGD: Giám đốc + Trợ lý + Thư ký
 * - Email nhận thông báo: thanhbinhk645+…@gmail.com (về hộp thư thanhbinhk645@gmail.com)
 * - Lương T1–T7/2026: đã thanh toán; T8 giữ demo ở bước HR kiểm tra nguồn (chưa chốt, chưa có phiếu); T9 bình thường (đã tính, kỳ đã hết sẽ được hệ thống chốt)
 */
class FullDemoDatasetSeeder extends Seeder
{
    use WithoutModelEvents;

    private const NOTIFY_MAILBOX = 'thanhbinhk645';

    private const PASSWORD = '123456';

    private const OFFICE = 'Trụ sở SmartHR, 12 Nguyễn Trãi, Thanh Xuân, Hà Nội';

    private const OFFICE_LAT = 21.00238;

    private const OFFICE_LNG = 105.80482;

    /** Login demo giữ nguyên — chỉ đổi employee.email để nhận mail. */
    private const RESERVED_LOGINS = [
        'admin@smarthr.com',
        'hr@smarthr.com',
        'accountant@smarthr.com',
        'giamdoc@smarthr.com',
        'nv@smarthr.com',
        'nva@smarthr.com',
    ];

    private const FIRST_NAMES = [
        'An', 'Bình', 'Cường', 'Dũng', 'Em', 'Giang', 'Hà', 'Hùng', 'Khoa', 'Lan',
        'Minh', 'Nam', 'Oanh', 'Phúc', 'Quân', 'Sơn', 'Tâm', 'Uyên', 'Vân', 'Yến',
        'Đạt', 'Hương', 'Linh', 'Mai', 'Ngọc', 'Phương', 'Quỳnh', 'Trang', 'Tuấn', 'Vy',
    ];

    private const LAST_NAMES = [
        'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng',
        'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý',
    ];

    public function run(): void
    {
        $this->ensureDepartments();
        $this->call(PositionSeeder::class);
        $this->ensureFallbackPositions();

        $hr = User::query()->where('is_hr', true)->first();
        $kt = User::query()->where('is_accountant', true)->first();
        $gd = User::query()->where('is_director', true)->first();

        $this->ensureEmployeesPerDepartment($hr, $gd);
        $this->redirectNotificationEmails();
        $this->seedAttendanceAndPayroll($hr, $kt);

        $deptCount = Department::query()->count();
        $empCount = Employee::query()->count();
        $this->command?->info("FullDemoDatasetSeeder: {$deptCount} phòng ban, {$empCount} nhân viên. Mail demo → ".self::NOTIFY_MAILBOX.'@gmail.com');
    }

    private function ensureDepartments(): void
    {
        $depts = [
            ['BGD', 'Ban Giám đốc', 'Điều hành và quản lý toàn bộ hoạt động của công ty.'],
            ['HR', 'Phòng Nhân sự (HR)', 'Quản lý nhân sự, tuyển dụng, hợp đồng, chấm công, đào tạo và phúc lợi.'],
            ['TD', 'Phòng Tuyển dụng', 'Tuyển dụng và thu hút nhân tài.'],
            ['CB', 'Phòng C&B', 'Lương thưởng, bảo hiểm và phúc lợi.'],
            ['DTPT', 'Phòng Đào tạo & Phát triển', 'Đào tạo và phát triển năng lực nhân viên.'],
            ['KTTC', 'Phòng Kế toán - Tài chính', 'Quản lý tài chính, kế toán, thanh toán lương.'],
            ['KD', 'Phòng Kinh doanh', 'Kinh doanh và phát triển doanh thu.'],
            ['MKT', 'Phòng Marketing', 'Marketing và truyền thông thương hiệu.'],
            ['IT', 'Phòng IT', 'Công nghệ thông tin và hạ tầng hệ thống.'],
            ['VH', 'Phòng Vận hành', 'Vận hành chung của công ty.'],
            ['PC', 'Phòng Pháp chế', 'Pháp lý và kiểm soát rủi ro.'],
            ['HC', 'Phòng Hành chính', 'Hành chính văn phòng và hậu cần.'],
            ['CSKH', 'Phòng Chăm sóc khách hàng', 'Hỗ trợ và chăm sóc khách hàng.'],
            ['MH', 'Phòng Mua hàng', 'Mua sắm và quản lý nhà cung cấp.'],
            ['KV', 'Phòng Kho vận', 'Kho hàng và vận chuyển.'],
            ['SX', 'Phòng Sản xuất', 'Quản lý sản xuất.'],
            ['QC', 'Phòng Kiểm soát chất lượng', 'Kiểm soát chất lượng.'],
            ['R&D', 'Phòng Nghiên cứu & Phát triển', 'Nghiên cứu và phát triển sản phẩm.'],
            ['DA', 'Phòng Dự án', 'Quản lý dự án.'],
            ['DT', 'Phòng Đào tạo', 'Đào tạo nội bộ.'],
        ];

        foreach ($depts as [$code, $name, $description]) {
            Department::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description]
            );
        }
    }

    private function ensureFallbackPositions(): void
    {
        $covered = Position::query()->whereNotNull('department_id')->pluck('department_id')->unique();

        foreach (Department::query()->orderBy('id')->get() as $department) {
            if ($covered->contains($department->id)) {
                continue;
            }

            Position::updateOrCreate(
                ['name' => 'Trưởng '.$department->name],
                [
                    'department_id' => $department->id,
                    'level' => 'Manager',
                    'base_salary' => 18000000,
                    'salary_range_min' => 15000000,
                    'salary_range_max' => 22000000,
                    'allowance' => 1500000,
                    'description' => 'Quản lý '.$department->name.'.',
                ]
            );
            Position::updateOrCreate(
                ['name' => 'Nhân viên '.$department->code],
                [
                    'department_id' => $department->id,
                    'level' => 'Staff',
                    'base_salary' => 10000000,
                    'salary_range_min' => 8000000,
                    'salary_range_max' => 13000000,
                    'allowance' => 800000,
                    'description' => 'Nhân viên thuộc '.$department->name.'.',
                ]
            );
            Position::updateOrCreate(
                ['name' => 'Chuyên viên '.$department->code],
                [
                    'department_id' => $department->id,
                    'level' => 'Staff',
                    'base_salary' => 12000000,
                    'salary_range_min' => 9000000,
                    'salary_range_max' => 15000000,
                    'allowance' => 1000000,
                    'description' => 'Chuyên viên thuộc '.$department->name.'.',
                ]
            );
        }
    }

    private function ensureEmployeesPerDepartment(?User $hr, ?User $gd): void
    {
        $nameIndex = 0;

        foreach (Department::query()->with('positions')->orderBy('id')->get() as $department) {
            $positions = $department->positions->values();
            if ($positions->isEmpty()) {
                continue;
            }

            // Bảo đảm mỗi chức vụ trong phòng có ≥ 1 người (trừ khi BGD Giám đốc đã có).
            foreach ($positions as $position) {
                $exists = Employee::query()
                    ->where('department_id', $department->id)
                    ->where(function ($q) use ($position) {
                        $q->where('position_id', $position->id)
                            ->orWhere('position', $position->name);
                    })
                    ->exists();

                if (! $exists) {
                    $this->createStaffEmployee($department, $position, $nameIndex++, $hr, $gd);
                }
            }

            $current = Employee::query()->where('department_id', $department->id)->count();
            $need = max(0, 5 - $current);
            for ($i = 0; $i < $need; $i++) {
                $position = $positions[$i % $positions->count()];
                $this->createStaffEmployee($department, $position, $nameIndex++, $hr, $gd);
            }
        }
    }

    private function createStaffEmployee(
        Department $department,
        Position $position,
        int $index,
        ?User $hr,
        ?User $gd
    ): Employee {
        $code = sprintf('%s-%03d', $department->code, $index + 1);
        while (Employee::query()->where('employee_code', $code)->exists()) {
            $index++;
            $code = sprintf('%s-%03d', $department->code, $index + 1);
        }

        $name = $this->fakeName($index);
        $notifyEmail = $this->notifyEmail($code);
        $isFemale = in_array(mb_strtolower(Str::afterLast($name, ' ')), [
            'an', 'em', 'giang', 'hà', 'lan', 'oanh', 'tâm', 'uyên', 'vân', 'yến', 'hương', 'linh', 'mai', 'ngọc', 'phương', 'quỳnh', 'trang', 'vy',
        ], true);

        $user = User::updateOrCreate(
            ['email' => $notifyEmail],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'api_token' => Str::random(60),
                'is_admin' => false,
                'is_hr' => false,
                'is_accountant' => false,
                'is_director' => false,
                'is_locked' => false,
                'email_verified_at' => now()->subMonths(3),
            ]
        );

        $employee = Employee::updateOrCreate(
            ['employee_code' => $code],
            [
                'user_id' => $user->id,
                'name' => $name,
                'email' => $notifyEmail,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'position' => $position->name,
                'status' => Employee::STATUS_ACTIVE,
                'gender' => $isFemale ? 'female' : 'male',
                'dob' => Carbon::create(1988 + ($index % 12), 1 + ($index % 12), 1 + ($index % 27))->toDateString(),
                'cccd' => sprintf('0012%08d', 20000000 + $index),
                'phone' => sprintf('09%08d', 30000000 + $index),
                'address' => ($index + 5).' Nguyễn Trãi, Thanh Xuân, Hà Nội',
                'start_date' => Carbon::create(2024, 1, 15)->addMonths($index % 18)->toDateString(),
                'education' => $isFemale ? 'Đại học' : 'Cao đẳng',
                'leave_balance' => 12,
                'bank_name' => 'Vietcombank',
                'account_number' => sprintf('10%010d', 3300000000 + $index),
                'account_holder' => Str::upper(Str::ascii($name)),
                'bank_account' => sprintf('10%010d', 3300000000 + $index),
                'bank_bin' => '970436',
                'avatar' => '/images/avatars/default.svg',
            ]
        );

        $this->ensureActiveContract($employee, $hr, $gd);

        return $employee;
    }

    private function ensureActiveContract(Employee $employee, ?User $hr, ?User $gd): void
    {
        $salary = (float) ($employee->positionDetail?->base_salary ?: Position::find($employee->position_id)?->base_salary ?: 10000000);
        $allowance = (float) ($employee->positionDetail?->allowance ?: 1000000);
        $start = $employee->start_date?->toDateString() ?? '2024-01-15';

        $contract = Contract::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'contract_code' => sprintf('HD-FULL-%s', $employee->employee_code),
            ],
            [
                'title' => 'Hợp đồng lao động — '.$employee->name,
                'status' => Contract::STATUS_ACTIVE,
                'contract_type' => 'fixed_term',
                'start_date' => $start,
                'end_date' => '2027-12-31',
                'base_salary' => $salary,
                'salary' => $salary,
                'allowance' => $allowance,
                'bonus' => 500000,
                'payment_method' => 'cash_and_bank_transfer',
                'workplace' => self::OFFICE,
                'created_by' => $hr?->id,
                'signer_id' => $gd?->id,
                'employee_signed_at' => Carbon::parse($start)->setTime(9, 0),
                'director_signed_at' => Carbon::parse($start)->setTime(11, 0),
            ]
        );

        if ($contract->status !== Contract::STATUS_ACTIVE) {
            $contract->forceFill([
                'status' => Contract::STATUS_ACTIVE,
                'base_salary' => $contract->base_salary ?: $salary,
                'salary' => $contract->salary ?: $salary,
            ])->save();
        }

        $employee->forceFill(['status' => Employee::STATUS_ACTIVE])->save();
    }

    private function redirectNotificationEmails(): void
    {
        foreach (Employee::query()->with('user')->orderBy('id')->get() as $employee) {
            $tag = Str::lower(preg_replace('/[^a-zA-Z0-9]+/', '', (string) ($employee->employee_code ?: 'nv'.$employee->id)));
            $notify = $this->notifyEmail($tag ?: ('nv'.$employee->id));

            $employee->forceFill(['email' => $notify])->save();

            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $login = mb_strtolower(trim((string) $user->email));
            if (in_array($login, self::RESERVED_LOGINS, true) || $user->is_admin) {
                // Giữ login demo; employee.email đã trỏ Gmail để nhận thông báo.
                continue;
            }

            if ($user->email !== $notify) {
                // Tránh trùng email khi đổi.
                if (User::query()->where('email', $notify)->where('id', '!=', $user->id)->exists()) {
                    $notify = $this->notifyEmail($tag.'u'.$user->id);
                    $employee->forceFill(['email' => $notify])->save();
                }
                $user->forceFill(['email' => $notify])->save();
            }
        }
    }

    private function seedAttendanceAndPayroll(?User $hr, ?User $kt): void
    {
        $calc = app(PayrollCalculationService::class);
        $employees = Employee::query()
            ->with(['positionDetail', 'contracts'])
            ->whereIn('status', [Employee::STATUS_ACTIVE, Employee::STATUS_ON_LEAVE, Employee::STATUS_PENDING])
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->get();
        $payrollStaff = $employees->reject(fn (Employee $row) => \App\Support\RequestApprover::isDirectorProfile($row))->values();

        // T1–T7/2026: chấm công + phiếu lương đã thanh toán (không tính Giám đốc)
        for ($month = 1; $month <= 7; $month++) {
            $this->seedMonthAttendance($employees, $month, 2026, $hr);
            foreach ($payrollStaff as $employee) {
                $this->upsertPaidPayroll($calc, $employee, $month, 2026, $kt);
            }
        }

        // T8/2026: chấm công + kỳ đã chốt (auto), HR chưa xác nhận — demo bước kiểm tra nguồn
        $this->seedMonthAttendance($employees, 8, 2026, $hr);
        Payroll::query()->where('month', 8)->where('year', 2026)->delete();
        \App\Models\PayrollPeriodLock::updateOrCreate(
            ['month' => 8, 'year' => 2026],
            [
                'is_locked' => true,
                'locked_at' => Carbon::create(2026, 9, 1, 0, 5),
                'locked_by' => null,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'unlock_reason' => null,
                'hr_verified_at' => null,
                'hr_verified_by' => null,
                'unlock_request_status' => null,
                'unlock_requested_at' => null,
                'unlock_requested_by' => null,
                'unlock_request_reason' => null,
            ]
        );

        // T9/2026: tháng hiện tại — chỉ chấm công, chưa tính lương
        $this->seedMonthAttendance($employees, 9, 2026, $hr, untilDay: (int) now()->day);
        Payroll::query()->where('month', 9)->where('year', 2026)->delete();
        \App\Models\PayrollPeriodLock::query()->where('month', 9)->where('year', 2026)->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     */
    private function seedMonthAttendance($employees, int $month, int $year, ?User $hr, ?int $untilDay = null): void
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $untilDay
            ? Carbon::create($year, $month, $untilDay)->endOfDay()
            : $start->copy()->endOfMonth();

        foreach ($employees as $employee) {
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                if ($day->isSunday()) {
                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $day->toDateString(),
                    ],
                    [
                        'check_in' => '07:55:00',
                        'check_out' => '17:05:00',
                        'status' => 'present',
                        'work_hours' => 8.5,
                        'late_minutes' => 0,
                        'late_penalty_fee' => 0,
                        'early_leave_minutes' => 0,
                        'overtime_hours' => 0,
                        'check_in_latitude' => self::OFFICE_LAT,
                        'check_in_longitude' => self::OFFICE_LNG,
                        'check_out_latitude' => self::OFFICE_LAT,
                        'check_out_longitude' => self::OFFICE_LNG,
                        'check_in_location' => self::OFFICE,
                        'check_out_location' => self::OFFICE,
                        'location' => self::OFFICE,
                        'attendance_method' => 'web',
                        'attendance_status' => 'approved',
                        'approved_by' => $hr?->id,
                        'approved_at' => $day->copy()->setTime(18, 0),
                        'notes' => 'Chấm công demo đầy đủ',
                    ]
                );
            }
        }
    }

    private function upsertPaidPayroll(
        PayrollCalculationService $calc,
        Employee $employee,
        int $month,
        int $year,
        ?User $kt
    ): void {
        $existing = Payroll::query()
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && $existing->status === W::PAID) {
            try {
                $calc->rebuildStoredAmounts($existing);
            } catch (\Throwable) {
                // giữ số liệu cũ nếu rebuild lỗi
            }

            return;
        }

        $payroll = $this->forceCalculatePayroll($calc, $employee, $month, $year, $existing);
        if (! $payroll) {
            return;
        }

        $paidAt = Carbon::create($year, $month, min(28, Carbon::create($year, $month, 1)->daysInMonth))->setTime(10, 0);
        $payroll->forceFill([
            'status' => W::PAID,
            'paid_at' => $paidAt,
            'paid_by' => $kt?->id,
            'payment_method' => 'bank_transfer',
            'confirmation_status' => 'confirmed',
            'payout_bank_name' => $employee->bank_name,
            'payout_account_number' => $employee->account_number,
            'payout_account_holder' => $employee->account_holder,
        ])->save();

        if (Schema::hasTable('salary_payments')) {
            SalaryPayment::updateOrCreate(
                ['payroll_id' => $payroll->id],
                [
                    'employee_id' => $employee->id,
                    'code' => sprintf('PAY-%04d%02d-%d', $year, $month, $payroll->id),
                    'month' => $month,
                    'year' => $year,
                    'total' => $payroll->total_salary,
                    'deductions' => (float) ($payroll->insurance ?? 0) + (float) ($payroll->tax ?? 0),
                    'net' => $payroll->total_salary,
                    'status' => 'paid',
                    'payment_method' => 'bank_transfer',
                    'paid_at' => $paidAt,
                    'paid_by' => $kt?->id,
                ]
            );
        }
    }

    private function forceCalculatePayroll(
        PayrollCalculationService $calc,
        Employee $employee,
        int $month,
        int $year,
        ?Payroll $existing = null
    ): ?Payroll {
        try {
            return $calc->calculate($employee, $month, $year);
        } catch (\Throwable) {
            $amounts = $this->filterPayrollColumns($calc->buildAmounts($employee, $month, $year));
            if ($existing) {
                $existing->fill($amounts)->save();

                return $existing->fresh();
            }

            return Payroll::create(array_merge($amounts, [
                'employee_id' => $employee->id,
                'month' => $month,
                'year' => $year,
                'status' => W::CALCULATED,
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $amounts
     * @return array<string, mixed>
     */
    private function filterPayrollColumns(array $amounts): array
    {
        static $columns = null;
        $columns ??= Schema::getColumnListing('payrolls');

        return array_intersect_key($amounts, array_flip($columns));
    }

    private function notifyEmail(string $tag): string
    {
        $safe = Str::lower(preg_replace('/[^a-zA-Z0-9]+/', '', $tag) ?: 'nv');

        return self::NOTIFY_MAILBOX.'+'.$safe.'@gmail.com';
    }

    private function fakeName(int $index): string
    {
        $last = self::LAST_NAMES[$index % count(self::LAST_NAMES)];
        $first = self::FIRST_NAMES[$index % count(self::FIRST_NAMES)];
        $mid = self::FIRST_NAMES[($index * 3) % count(self::FIRST_NAMES)];

        return trim($last.' '.$mid.' '.$first);
    }
}
