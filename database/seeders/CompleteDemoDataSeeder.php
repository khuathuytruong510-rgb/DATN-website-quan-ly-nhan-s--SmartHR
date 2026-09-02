<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\Benefit;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractLog;
use App\Models\ContractTemplate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Recruitment;
use App\Models\SalaryAdvance;
use App\Models\SalaryHistory;
use App\Models\SalaryReceiveChangeRequest;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\DirectorSuccessionService;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService as W;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Điền đủ trường hồ sơ / danh mục để bảo vệ DATN.
 * Không đổi trạng thái workflow đang chờ duyệt.
 */
class CompleteDemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const OFFICE = 'Trụ sở SmartHR, 12 Nguyễn Trãi, Thanh Xuân, Hà Nội';

    private const OFFICE_LAT = 21.00238;

    private const OFFICE_LNG = 105.80482;

    public function run(): void
    {
        $this->call(PositionSeeder::class);
        $this->fillPositions();

        $hr = User::where('is_hr', true)->first() ?? User::where('email', 'hr@smarthr.com')->first();
        $kt = User::where('is_accountant', true)->first() ?? User::where('email', 'accountant@smarthr.com')->first();
        $gd = User::where('is_director', true)->first() ?? User::where('email', 'giamdoc@smarthr.com')->first();
        if ($gd) {
            app(DirectorSuccessionService::class)->ensureOpenTenureFor($gd);
        }

        $this->fillUsers();
        $this->fillDepartments();
        $this->ensureStaffEmployees($hr, $kt);
        $this->fillEmployees();
        $this->fillContracts($hr, $gd);
        $this->fillContractCatalog();
        $this->fillPayrolls($kt);
        $this->fillAttendances($hr);
        $this->fillLeavesAndOvertime($hr);
        $this->fillBenefits($hr);
        $this->fillEvaluations($hr, $gd);
        $this->fillSupportAndBank($hr);
        $this->fillNotifications($hr);
        $this->fillCatalogExtras($hr, $kt);
        $this->fillWorkingMonth(7, 2026, $hr);
        $this->fillWorkingMonth(8, 2026, $hr);
        $this->rebuildPayrollAmounts();

        $this->command?->info('CompleteDemoDataSeeder: đã điền hồ sơ và danh mục demo.');
    }

    private function fillUsers(): void
    {
        foreach (User::query()->get() as $user) {
            $this->fillEmpty($user, [
                'avatar' => '/images/avatars/default.svg',
                'api_token' => $user->api_token ?: Str::random(60),
                'is_admin' => (bool) $user->is_admin,
                'is_hr' => (bool) $user->is_hr,
                'is_accountant' => (bool) $user->is_accountant,
                'is_director' => (bool) $user->is_director,
                'is_locked' => false,
                'email_verified_at' => $user->email_verified_at ?? now()->subMonths(6),
            ]);
        }
    }

    private function fillPositions(): void
    {
        foreach (Position::query()->get() as $position) {
            $this->fillEmpty($position, [
                'description' => $position->description ?: 'Mô tả công việc cho vị trí '.$position->name.'.',
                'allowance' => $position->allowance ?: 1000000,
                'level' => $position->level ?: 'Staff',
                'salary_range_min' => $position->salary_range_min ?: 8000000,
                'salary_range_max' => $position->salary_range_max ?: 20000000,
                'base_salary' => $position->base_salary ?: 12000000,
            ], true);
        }
    }

    private function fillDepartments(): void
    {
        $managers = [
            'BGD' => 'Phạm Thị Dung',
            'HCNS' => 'Trần Thị Bích',
            'KTTC' => 'Lê Thị Mai',
            'KD' => 'Lê Văn Cường',
            'MKT' => 'Hoàng Văn Nam',
            'CNTT' => 'Nguyễn Văn An',
            'CSKH' => 'Nguyễn Thị Lan',
            'MH' => 'Phạm Văn Hùng',
            'KV' => 'Đỗ Văn Long',
            'SX' => 'Vũ Minh Tuấn',
            'QC' => 'Ngô Thị Hà',
            'R&D' => 'Bùi Đức Anh',
            'PC' => 'Trịnh Mai Phương',
            'DA' => 'Lý Quốc Việt',
            'DT' => 'Đặng Thị Ngọc',
        ];

        foreach (Department::query()->get() as $dept) {
            $code = $dept->code ?: strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($dept->name)) ?: 'PB', 0, 4));
            $this->fillEmpty($dept, [
                'code' => $code,
                'manager' => $managers[$code] ?? ($dept->manager ?: 'Trưởng phòng '.$dept->name),
                'description' => $dept->description ?: 'Phòng ban '.$dept->name.' thuộc hệ thống SmartHR.',
            ]);
            $dept->employee_count = $dept->employees()->count();
            $dept->save();
        }
    }

    private function ensureStaffEmployees(?User $hr, ?User $kt): void
    {
        $hcns = Department::where('code', 'HCNS')->first();
        $kttc = Department::where('code', 'KTTC')->first();

        if ($hr && $hcns) {
            Employee::updateOrCreate(['email' => $hr->email], [
                'user_id' => $hr->id,
                'name' => $hr->name,
                'position' => 'Trưởng phòng Nhân sự',
                'department_id' => $hcns->id,
                'status' => 'active',
                'employee_code' => 'HCNS-HR-01',
            ]);
        }

        if ($kt && $kttc) {
            Employee::updateOrCreate(['email' => $kt->email], [
                'user_id' => $kt->id,
                'name' => $kt->name,
                'position' => 'Trưởng phòng Kế toán',
                'department_id' => $kttc->id,
                'status' => 'active',
                'employee_code' => 'KTTC-KT-01',
                'gender' => 'female',
                'leave_balance' => 12,
            ]);
        }
    }

    private function fillEmployees(): void
    {
        $banks = config('banks', ['Vietcombank', 'BIDV', 'MB Bank', 'Techcombank', 'ACB']);
        $bins = [
            'Vietcombank' => '970436',
            'BIDV' => '970418',
            'MB Bank' => '970422',
            'Techcombank' => '970407',
            'ACB' => '970416',
            'VietinBank' => '970415',
            'Agribank' => '970405',
        ];

        foreach (Employee::with('department')->orderBy('id')->get() as $i => $employee) {
            $this->ensureEmployeeUser($employee);
            $employee->refresh();

            $female = $this->isFemaleName($employee->name);
            $bank = $banks[$i % count($banks)];
            $position = $this->matchPosition($employee->position);
            $code = $employee->employee_code
                ?: sprintf('%s-%03d', $employee->department?->code ?: 'NV', $employee->id);

            $this->fillEmpty($employee, [
                'employee_code' => $code,
                'gender' => $female ? 'female' : 'male',
                'dob' => sprintf('19%02d-%02d-%02d', 80 + ($employee->id % 15), 1 + ($employee->id % 12), 1 + ($employee->id % 27)),
                'cccd' => sprintf('0340%08d', 10000000 + $employee->id),
                'phone' => sprintf('09%08d', 10000000 + $employee->id),
                'address' => ($i + 12).' Nguyễn Trãi, phường Thanh Xuân Trung, quận Thanh Xuân, Hà Nội',
                'address_detail' => 'Căn hộ '.($i + 101).', tầng '.(($i % 8) + 2).', chung cư SmartResidence',
                'start_date' => now()->subYears(2)->subMonths($employee->id % 10)->startOfMonth()->toDateString(),
                'education' => $female ? 'Cử nhân Quản trị kinh doanh — ĐH Ngoại thương' : 'Kỹ sư Công nghệ thông tin — ĐH Bách khoa Hà Nội',
                'experience' => (3 + ($employee->id % 6)).' năm kinh nghiệm tại vị trí '.$employee->position.'.',
                'leave_balance' => $employee->leave_balance ?: 12,
                'position_id' => $position?->id,
                'position' => $employee->position ?: ($position?->name ?? 'Nhân viên'),
                'avatar' => $employee->avatar ?: '/images/avatars/default.svg',
                'bank_name' => $bank,
                'account_number' => sprintf('10%010d', 2200000000 + $employee->id),
                'account_holder' => Str::upper(Str::ascii($employee->name)),
                'bank_account' => sprintf('10%010d', 2200000000 + $employee->id),
                'bank_bin' => $bins[$bank] ?? '970436',
                'status' => $employee->status ?: 'active',
            ]);
        }
    }

    private function fillContracts(?User $hr, ?User $gd): void
    {
        $template = ContractTemplate::query()->first();
        if ($template) {
            $this->fillEmpty($template, [
                'contract_type' => 'official',
                'title' => $template->title ?: 'Hợp đồng lao động xác định thời hạn',
                'content' => $template->content ?: $this->contractBody(),
                'status' => 'active',
            ]);
        }

        foreach (Employee::query()->get() as $employee) {
            $exists = Contract::where('employee_id', $employee->id)->exists();
            if (! $exists) {
                Contract::create([
                    'employee_id' => $employee->id,
                    'title' => 'Hợp đồng lao động — '.$employee->name,
                    'contract_code' => sprintf('HD-%d-%04d', now()->year, $employee->id),
                    'status' => 'active',
                    'start_date' => $employee->start_date?->toDateString() ?? '2025-01-15',
                    'end_date' => '2027-12-31',
                    'base_salary' => $employee->positionDetail?->base_salary ?? 12000000,
                ]);
            }
        }

        foreach (Contract::with('employee.positionDetail')->get() as $contract) {
            $employee = $contract->employee;
            $salary = (float) ($contract->base_salary ?: $contract->salary ?: $employee?->positionDetail?->base_salary ?: 12000000);
            $allowance = (float) ($contract->allowance ?: $employee?->positionDetail?->allowance ?: 1500000);
            $waitingEmployee = in_array($contract->status, ['waiting_employee_signature', 'waiting_employee'], true);
            $waitingDirector = in_array($contract->status, ['waiting_director_signature', 'waiting_director'], true);
            $active = $contract->status === 'active';

            $payload = [
                'title' => $contract->title ?: ('Hợp đồng lao động — '.$employee?->name),
                'contract_code' => $contract->contract_code ?: sprintf('HD-%d-%04d', now()->year, $contract->id),
                'contract_type' => $contract->contract_type ?: 'official',
                'sign_date' => $contract->start_date?->toDateString() ?? now()->subYear()->toDateString(),
                'signed_date' => $contract->start_date?->toDateString() ?? now()->subYear()->toDateString(),
                'base_salary' => $salary,
                'salary' => $salary,
                'allowance' => $allowance,
                'bonus' => $contract->bonus ?: 1000000,
                'payment_method' => 'bank_transfer',
                'probation_salary' => round($salary * 0.85, 0),
                'company_representative' => $gd?->name ?? 'Phạm Thị Dung',
                'signer' => $gd?->name ?? 'Phạm Thị Dung',
                'signer_id' => $gd?->id,
                'notes' => $contract->notes ?: 'Hợp đồng lao động xác định thời hạn theo Bộ luật Lao động 2019.',
                'contract_content' => $this->contractBody(),
                'terms' => $this->contractBody(),
                'additional_terms' => 'Phụ cấp ăn trưa 730.000đ/tháng. Thưởng lễ Tết theo quy chế công ty.',
                'workplace' => self::OFFICE,
                'working_schedule' => 'morning_evening',
                'benefits' => 'BHXH, BHYT, BHTN; 12 ngày phép năm; phụ cấp ăn trưa và điện thoại.',
                'created_by' => $hr?->id,
                'contract_template_id' => $template?->id,
                'allowed_unpaid_leave_days_per_month' => 1,
                'allowed_makeup_attendance_per_month' => 3,
                'allowed_maternity_leave_days' => 180,
                'end_date' => $contract->end_date?->toDateString() ?? '2027-12-31',
            ];

            if ($active) {
                $signedAt = optional($contract->start_date)?->copy()->setTime(9, 30) ?? now()->subYear();
                $payload['employee_signed_at'] = $signedAt;
                $payload['director_signed_at'] = $signedAt->copy()->addHours(2);
                $payload['signed_employee_at'] = $payload['employee_signed_at'];
                $payload['signed_director_at'] = $payload['director_signed_at'];
            }

            $this->fillEmpty($contract, $payload, true);

            if ($contract->logs()->doesntExist() && $hr) {
                ContractLog::create([
                    'contract_id' => $contract->id,
                    'user_id' => $hr->id,
                    'action' => 'created',
                    'message' => 'HR tạo hợp đồng và điền đầy đủ điều khoản.',
                    'details' => ['status' => $contract->status],
                ]);
                if ($active) {
                    ContractLog::create([
                        'contract_id' => $contract->id,
                        'user_id' => $employee?->user_id,
                        'action' => 'employee_signed',
                        'message' => 'Nhân viên đã ký hợp đồng.',
                    ]);
                    ContractLog::create([
                        'contract_id' => $contract->id,
                        'user_id' => $gd?->id,
                        'action' => 'director_signed',
                        'message' => 'Giám đốc đã ký hợp đồng. Hợp đồng có hiệu lực.',
                    ]);
                }
            }
        }
    }

    private function fillContractCatalog(): void
    {
        $clauses = [
            ['1', 'THỎA THUẬN CHUNG', 'Hai bên thỏa thuận ký kết hợp đồng lao động theo pháp luật Việt Nam.'],
            ['2', 'VỊ TRÍ VÀ NHIỆM VỤ', 'Người lao động thực hiện công việc theo mô tả vị trí và sự phân công của quản lý.'],
            ['3', 'THỜI GIỜ LÀM VIỆC', '40 giờ/tuần, 08:00–17:00, nghỉ trưa 12:00–13:00, thứ Hai đến thứ Sáu.'],
            ['4', 'TIỀN LƯƠNG VÀ PHÚC LỢI', 'Lương trả ngày 25 hàng tháng. Công ty đóng BHXH, BHYT, BHTN đầy đủ.'],
            ['5', 'BẢO MẬT', 'Người lao động cam kết bảo mật thông tin công ty trong và sau thời hạn hợp đồng.'],
        ];

        foreach (['internship', 'probation', 'official', 'seasonal'] as $type) {
            foreach ($clauses as $i => [$number, $title, $content]) {
                ContractClause::firstOrCreate(
                    ['contract_type' => $type, 'section_number' => $number],
                    [
                        'section_title' => $title,
                        'content' => $content,
                        'order' => $i + 1,
                        'is_mandatory' => true,
                        'status' => 'active',
                    ]
                );
            }
        }
    }

    private function fillPayrolls(?User $kt): void
    {
        $issued = array_merge(W::directorApprovedStatuses(), W::payableStatuses(), [W::PAID]);

        foreach (Payroll::with('employee')->get() as $payroll) {
            $employee = $payroll->employee;
            $base = (float) ($payroll->base_salary ?: 12000000);
            $days = (float) $payroll->working_days;
            if ($days <= 0) {
                $days = 22;
            }
            $daily = (float) $payroll->daily_salary;
            if ($daily <= 0) {
                $daily = round($base / 26, 0);
            }
            $workingSalary = (float) $payroll->working_salary;
            if ($workingSalary <= 0) {
                $workingSalary = $daily * $days;
            }
            $allowance = (float) ($payroll->allowance ?: 500000);
            $insurance = (float) $payroll->insurance;
            if ($insurance <= 0) {
                $insurance = round($base * 0.105, 0);
            }

            $payload = [
                'daily_salary' => $daily,
                'required_working_days' => $payroll->required_working_days ?: 26,
                'working_days' => $days,
                'working_salary' => $workingSalary,
                'paid_leave_days' => $payroll->paid_leave_days ?: 0,
                'unpaid_leave_days' => $payroll->unpaid_leave_days ?: 0,
                'overtime_days' => $payroll->overtime_days ?: 0,
                'overtime_hours' => $payroll->overtime_hours ?: 0,
                'overtime_day_salary' => $payroll->overtime_day_salary ?: 0,
                'overtime_hour_salary' => $payroll->overtime_hour_salary ?: 0,
                'overtime_salary' => $payroll->overtime_salary ?: 0,
                'allowance' => $allowance,
                'bonus' => ((float) $payroll->bonus) > 0 ? $payroll->bonus : 500000,
                'deduction' => $payroll->deduction ?: 0,
                'late_penalty_fee' => $payroll->late_penalty_fee ?: 0,
                'insurance' => $insurance > 0 ? $insurance : round($base * 0.105, 0),
                'tax' => ((float) $payroll->tax) > 0 ? $payroll->tax : round(max($base - $insurance, 0) * 0.1, 0),
                'locked' => false,
                'payout_bank_name' => $employee?->bank_name,
                'payout_account_number' => $employee?->account_number,
                'payout_account_holder' => $employee?->account_holder,
                'email_status' => $payroll->email_status ?: (in_array($payroll->status, $issued, true) ? 'sent' : 'pending'),
            ];

            if (in_array($payroll->status, W::payableStatuses(), true) || $payroll->status === W::PAID) {
                $payload['confirmation_status'] = 'confirmed';
                $payload['confirmed_at'] = $payroll->confirmed_at ?? now()->subDays(2);
            } elseif (in_array($payroll->status, W::directorApprovedStatuses(), true)) {
                $payload['confirmation_status'] = $payroll->confirmation_status ?: 'pending';
                $payload['confirmation_deadline'] = $payroll->confirmation_deadline ?? now()->addDays(3);
                $payload['sent_at'] = $payroll->sent_at ?? now()->subDay();
                $payload['sent_by'] = $payroll->sent_by ?: $kt?->id;
            }

            if ($payroll->status === W::PAID) {
                $payload['paid_at'] = $payroll->paid_at ?? now()->subDays(5);
                $payload['paid_by'] = $payroll->paid_by ?: $kt?->id;
                $payload['payment_method'] = $payroll->payment_method ?: 'bank_transfer';
            }

            $this->fillEmpty($payroll, $payload, true);

            if ($payroll->status === W::PAID) {
                SalaryHistory::recordFromPaidPayroll($payroll->fresh(), $kt);
            }
        }
    }

    private function fillAttendances(?User $hr): void
    {
        foreach (Attendance::query()->get() as $row) {
            $isLeave = in_array($row->status, ['leave', 'absent'], true);
            $late = $row->status === 'late' ? max(5, (int) ($row->late_minutes ?: 12)) : 0;

            $payload = [
                'late_minutes' => $late,
                'late_penalty_fee' => $late > 0 ? 50000 : 0,
                'early_leave_minutes' => $row->early_leave_minutes ?: 0,
                'overtime_hours' => $row->overtime_hours ?: 0,
                'check_in_location_missing' => 0,
                'check_out_location_missing' => 0,
                'location' => self::OFFICE,
                'attendance_method' => $row->attendance_method ?: 'web',
                'attendance_status' => $row->attendance_status ?: ($isLeave ? 'recorded' : 'approved'),
                'notes' => $row->notes ?: ($isLeave ? 'Nghỉ phép / vắng mặt có ghi nhận' : 'Chấm công tại văn phòng'),
            ];

            if (! $isLeave) {
                $payload['check_in'] = $row->getRawOriginal('check_in') ?: ($late ? '08:12:00' : '08:00:00');
                $payload['check_out'] = $row->getRawOriginal('check_out') ?: '17:05:00';
                $payload['work_hours'] = $row->work_hours ?: 8.0;
                $payload['check_in_latitude'] = self::OFFICE_LAT;
                $payload['check_in_longitude'] = self::OFFICE_LNG;
                $payload['check_out_latitude'] = self::OFFICE_LAT;
                $payload['check_out_longitude'] = self::OFFICE_LNG;
                $payload['check_in_location'] = self::OFFICE;
                $payload['check_out_location'] = self::OFFICE;
                $payload['check_in_ip_address'] = '192.168.1.20';
                $payload['check_out_ip_address'] = '192.168.1.20';
                $payload['check_in_distance'] = 12.5;
                $payload['check_out_distance'] = 15.2;
                $payload['check_in_notes'] = $late ? 'Đến muộn do giao thông' : 'Đúng giờ';
                $payload['check_out_notes'] = 'Tan ca đúng giờ';
                $payload['approved_by'] = $hr?->id;
                $payload['approved_at'] = now()->subDays(1);
            }

            $this->fillEmpty($row, $payload);
        }

        $pending = AttendanceAdjustmentRequest::where('status', AttendanceAdjustmentRequest::PENDING)->first();
        if ($pending) {
            $this->fillEmpty($pending, [
                'reason' => $pending->reason ?: 'Quên checkout, đề nghị HR điều chỉnh giờ ra.',
                'requested_check_in' => $pending->requested_check_in ?: '08:00:00',
                'requested_check_out' => $pending->requested_check_out ?: '17:00:00',
            ]);
        }
    }

    private function fillLeavesAndOvertime(?User $hr): void
    {
        foreach (LeaveRequest::query()->get() as $leave) {
            $payload = [
                'half_day' => (bool) $leave->half_day,
                'is_urgent' => (bool) $leave->is_urgent,
                'days' => $leave->days ?: max(1, optional($leave->start_date)->diffInDays($leave->end_date) + 1),
                'type' => $leave->type ?: 'annual',
                'reason' => $leave->reason ?: 'Nghỉ phép năm theo kế hoạch cá nhân.',
            ];
            if ($leave->status === 'approved') {
                $payload['approved_by'] = $leave->approved_by ?: $hr?->id;
                $payload['approved_at'] = $leave->approved_at ?? now()->subDays(3);
            }
            $this->fillEmpty($leave, $payload);
        }

        foreach (OvertimeRequest::query()->get() as $ot) {
            if ($ot->status === 'approved') {
                $this->fillEmpty($ot, [
                    'approved_by' => $hr?->id,
                    'approved_at' => $ot->approved_at ?? now()->subDays(1),
                    'reason' => $ot->reason ?: 'Hoàn thành công việc đột xuất.',
                ]);
            } else {
                $this->fillEmpty($ot, [
                    'reason' => $ot->reason ?: 'Đăng ký tăng ca hỗ trợ công việc.',
                ]);
            }
        }
    }

    private function fillBenefits(?User $hr): void
    {
        $packs = [
            ['allowance', 'PC-ANTRUA', 'Phụ cấp ăn trưa', 730000, 'VNĐ/tháng', 'Toàn bộ nhân viên chính thức', 'Đi làm tối thiểu 15 ngày/tháng'],
            ['insurance', 'BH-SUCKHOE', 'Bảo hiểm sức khỏe bổ sung', 2500000, 'VNĐ/năm', 'Nhân viên sau thử việc', 'Hợp đồng còn hiệu lực'],
            ['bonus', 'TH-CHUYENC', 'Thưởng chuyên cần', 500000, 'VNĐ/tháng', 'Không đi muộn quá 2 lần', 'Chấm công đủ công chuẩn'],
        ];

        foreach (Employee::query()->get() as $i => $employee) {
            [$type, $code, $title, $amount, $unit, $applies, $condition] = $packs[$i % count($packs)];
            $benefit = Benefit::updateOrCreate(
                ['employee_id' => $employee->id, 'code' => $code.'-'.$employee->id],
                [
                    'created_by' => $hr?->id,
                    'title' => $title,
                    'description' => $title.' áp dụng theo quy chế phúc lợi SmartHR 2026.',
                    'type' => $type,
                    'amount' => $amount,
                    'unit' => $unit,
                    'applies_to' => $applies,
                    'condition' => $condition,
                    'effective_date' => '2026-01-01',
                    'expiry_date' => '2026-12-31',
                    'application_status' => 'active',
                    'approval_status' => 'approved',
                    'status' => 'approved',
                    'approved_by' => $hr?->id,
                    'approved_at' => now()->subMonths(2),
                    'notes' => 'Đã phê duyệt và chi trả cùng kỳ lương.',
                ]
            );

            EmployeeBenefit::updateOrCreate(
                ['employee_id' => $employee->id, 'benefit_id' => $benefit->id],
                [
                    'applied_at' => '2026-01-15',
                    'status' => 'active',
                    'notes' => 'Đang hưởng phúc lợi.',
                ]
            );
        }
    }

    private function fillEvaluations(?User $hr, ?User $gd): void
    {
        foreach (Employee::query()->get() as $employee) {
            $scores = [
                'punctuality' => 8,
                'task_completion' => 26,
                'quality' => 16,
                'technical_skill' => 8,
                'responsibility' => 9,
                'teamwork' => 8,
                'attitude' => 9,
            ];
            $total = array_sum($scores);
            EmployeeEvaluation::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => '2026-07'],
                array_merge($scores, [
                    'evaluator_id' => $hr?->id,
                    'rating' => 4,
                    'score_total' => $total,
                    'classification' => 'Tốt',
                    'status' => 'approved',
                    'approved_by' => $gd?->id ?? $hr?->id,
                    'approved_at' => now()->subDays(10),
                    'self_evaluation' => false,
                    'summary' => 'Hoàn thành công việc đúng hạn, thái độ hợp tác tốt.',
                    'comments' => 'Duy trì chuyên cần và chủ động hỗ trợ đồng nghiệp.',
                ])
            );
        }
    }

    private function fillSupportAndBank(?User $hr): void
    {
        foreach (SupportRequest::query()->get() as $ticket) {
            if ($ticket->status === SupportRequest::PENDING || $ticket->status === SupportRequest::PROCESSING) {
                continue;
            }
            DB::table('support_requests')->where('id', $ticket->id)->update([
                'hr_reply' => $ticket->hr_reply ?: 'HR đã xử lý và phản hồi đầy đủ.',
                'follow_up' => $ticket->follow_up ?: 'Không cần theo dõi thêm.',
                'reply_message' => $ticket->reply_message ?? 'Đã giải quyết theo quy trình.',
                'replied_by' => $ticket->replied_by ?? $hr?->id,
                'replied_at' => $ticket->replied_at ?? now()->subDay(),
                'closed_at' => $ticket->closed_at ?? now()->subDay(),
            ]);
        }

        $employee = Employee::whereNotNull('user_id')->orderBy('id')->first();
        if ($employee && $hr) {
            SalaryReceiveChangeRequest::firstOrCreate(
                ['employee_id' => $employee->id, 'status' => 'approved'],
                [
                    'bank_name' => $employee->bank_name ?: 'Vietcombank',
                    'account_number' => $employee->account_number,
                    'account_holder' => $employee->account_holder,
                    'note' => 'Cập nhật STK lương sau khi mở tài khoản mới.',
                    'reviewed_by' => $hr->id,
                    'reviewed_at' => now()->subDays(4),
                    'review_note' => 'Đã đối chiếu CCCD và xác nhận chủ tài khoản.',
                ]
            );
        }
    }

    private function fillNotifications(?User $hr): void
    {
        foreach (Notification::query()->get() as $n) {
            $this->fillEmpty($n, [
                'data' => $n->data ?: ['source' => 'system'],
                'is_read' => (bool) $n->is_read,
                'message' => $n->message ?: $n->title,
            ]);
        }

        if ($hr && Notification::count() < 3) {
            Notification::create([
                'sender_id' => $hr->id,
                'target' => 'all',
                'title' => 'Nội quy chấm công tháng 8',
                'message' => 'Nhân viên chấm công tại văn phòng trước 08:15. Liên hệ HR nếu quên checkout.',
                'data' => ['module' => 'attendance'],
                'is_read' => false,
            ]);
        }
    }

    private function fillCatalogExtras(?User $hr, ?User $kt): void
    {
        $position = Position::where('name', 'Backend Developer')->first() ?? Position::first();
        if ($position && Schema::hasTable('recruitments')) {
            try {
                Recruitment::updateOrCreate(
                    ['title' => 'Tuyển Backend Developer'],
                    [
                        'position_id' => $position->id,
                        'description' => 'Phát triển và bảo trì hệ thống SmartHR, Laravel 13, MySQL 8.',
                        'requirements' => '2+ năm PHP/Laravel, nắm REST API, Git, làm việc nhóm.',
                        'salary_min' => 18000000,
                        'salary_max' => 28000000,
                        'status' => Schema::hasColumn('recruitments', 'status') ? 'open' : null,
                        'posted_at' => now()->subDays(20),
                    ]
                );
            } catch (\Throwable) {
                // Bảng tuyển dụng có thể dùng enum status khác — không chặn các phần còn lại.
            }
        }

        $nv = Employee::where('email', 'nv@smarthr.com')->first() ?? Employee::whereNotNull('user_id')->first();
        if ($nv && $hr && $kt) {
            SalaryAdvance::updateOrCreate(
                ['code' => 'UA-2026-0001'],
                [
                    'employee_id' => $nv->id,
                    'amount' => 3000000,
                    'reason' => 'Tạm ứng lương phục vụ việc gia đình.',
                    'requested_at' => now()->subDays(12),
                    'status' => 'processed',
                    'approved_by' => $hr->id,
                    'approved_at' => now()->subDays(10),
                    'processed_by' => $kt->id,
                    'processed_at' => now()->subDays(9),
                    'payment_method' => 'bank_transfer',
                    'bank' => $nv->bank_name,
                    'account_holder' => $nv->account_holder,
                    'account_number' => $nv->account_number,
                    'notes' => 'Đã chuyển khoản. Khấu trừ vào lương kỳ 08/2026.',
                    'is_deducted' => true,
                ]
            );
        }

        if (Schema::hasTable('holidays')) {
            app(\App\Services\VietnamHolidayCalendar::class)->syncYear(2026);
        }

        $shiftId = null;
        if (Schema::hasTable('shifts')) {
            $shiftId = DB::table('shifts')->where('code', 'HC')->value('id');
            if (! $shiftId) {
                $shiftId = DB::table('shifts')->insertGetId([
                    'name' => 'Ca hành chính',
                    'code' => 'HC',
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($shiftId && Schema::hasTable('work_schedules')) {
            foreach (Employee::query()->get() as $employee) {
                for ($weekday = 1; $weekday <= 5; $weekday++) {
                    DB::table('work_schedules')->updateOrInsert(
                        [
                            'employee_id' => $employee->id,
                            'weekday' => $weekday,
                            'shift_id' => $shiftId,
                        ],
                        [
                            'department_id' => $employee->department_id,
                            'effective_from' => '2026-01-01',
                            'effective_to' => '2026-12-31',
                            'is_active' => 1,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }

        foreach (Employee::query()->get() as $employee) {
            if (Schema::hasTable('bank_accounts')) {
                DB::table('bank_accounts')->updateOrInsert(
                    ['employee_id' => $employee->id, 'account_number' => $employee->account_number ?: ('10'.str_pad((string) $employee->id, 10, '0', STR_PAD_LEFT))],
                    [
                        'bank_code' => $employee->bank_bin ?: '970436',
                        'bank_name' => $employee->bank_name ?: 'Vietcombank',
                        'account_holder' => $employee->account_holder ?: Str::upper(Str::ascii($employee->name)),
                        'branch' => 'Chi nhánh Thanh Xuân',
                        'is_primary' => 1,
                        'is_active' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
            if (Schema::hasTable('bonuses')) {
                DB::table('bonuses')->updateOrInsert(
                    ['employee_id' => $employee->id, 'month' => 8, 'year' => 2026],
                    ['amount' => 500000, 'reason' => 'Thưởng chuyên cần tháng 8', 'updated_at' => now(), 'created_at' => now()]
                );
            }
            if (Schema::hasTable('deductions')) {
                DB::table('deductions')->updateOrInsert(
                    ['employee_id' => $employee->id, 'month' => 8, 'year' => 2026],
                    ['amount' => 0, 'reason' => 'Không phát sinh khấu trừ khác trong kỳ', 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    private function ensureEmployeeUser(Employee $employee): void
    {
        if ($employee->user_id && User::whereKey($employee->user_id)->exists()) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $employee->email],
            [
                'name' => $employee->name,
                'password' => Hash::make('123456'),
                'api_token' => Str::random(60),
                'is_admin' => false,
                'is_hr' => false,
                'is_accountant' => false,
                'is_director' => false,
                'is_locked' => false,
                'avatar' => '/images/avatars/default.svg',
            ]
        );

        $employee->user_id = $user->id;
        $employee->save();
    }

    private function matchPosition(?string $name): ?Position
    {
        $map = [
            'Giám đốc' => 'Giám đốc',
            'CTO' => 'Trưởng phòng IT',
            'Senior Developer' => 'Backend Developer',
            'Lập trình viên' => 'Backend Developer',
            'Tester' => 'Frontend Developer',
            'HR Manager' => 'Trưởng phòng Nhân sự',
            'Trưởng phòng Nhân sự' => 'Trưởng phòng Nhân sự',
            'Chuyên viên nhân sự' => 'Chuyên viên Nhân sự',
            'Sales Executive' => 'Nhân viên Kinh doanh',
            'Finance Officer' => 'Kế toán viên',
            'Kế toán viên' => 'Kế toán viên',
            'Trưởng phòng Kế toán' => 'Trưởng phòng Kế toán',
            'Marketing Lead' => 'Nhân viên Văn phòng',
        ];

        $target = $map[$name] ?? $name;

        return Position::where('name', $target)->first()
            ?? Position::where('name', 'like', '%'.$name.'%')->first();
    }

    private function isFemaleName(string $name): bool
    {
        return (bool) preg_match('/(Thị|Mai|Hoa|Hà|Lan|Phương|Ngọc|Dung|Bích)/u', $name);
    }

    private function fillWorkingMonth(int $month, int $year, ?User $hr): void
    {
        $holidays = Schema::hasTable('holidays')
            ? DB::table('holidays')->whereYear('date', $year)->whereMonth('date', $month)->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all()
            : [];

        foreach (Employee::query()->where('status', 'active')->get() as $employee) {
            $leaveDates = [];
            if ($employee->id % 4 === 0) {
                $leaveStart = Carbon::create($year, $month, 10);
                while ($leaveStart->isSunday()) {
                    $leaveStart->addDay();
                }
                $leaveEnd = $leaveStart->copy()->addDay();
                LeaveRequest::updateOrCreate([
                    'employee_id' => $employee->id,
                    'start_date' => $leaveStart->toDateString(),
                    'end_date' => $leaveEnd->toDateString(),
                ], [
                    'days' => 2,
                    'half_day' => false,
                    'type' => 'annual',
                    'reason' => 'Nghỉ phép năm đã được HR duyệt.',
                    'is_urgent' => false,
                    'status' => 'approved',
                    'approved_by' => $hr?->id,
                    'approved_at' => $leaveStart->copy()->subDays(5),
                ]);
                $leaveDates = [$leaveStart->toDateString(), $leaveEnd->toDateString()];
            }

            $cursor = Carbon::create($year, $month, 1);
            $end = $cursor->copy()->endOfMonth();
            $lateLeft = $employee->id % 3 === 0 ? 2 : 1;
            $otFridays = $employee->id % 2 === 0;

            for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
                if ($day->isSunday() || in_array($day->toDateString(), $holidays, true)) {
                    continue;
                }

                $onLeave = in_array($day->toDateString(), $leaveDates, true);
                $isLate = ! $onLeave && $lateLeft > 0 && ($day->day % 7 === ($employee->id % 5) + 1);
                if ($isLate) {
                    $lateLeft--;
                }
                $overtime = (! $onLeave && $otFridays && $day->isFriday()) ? 2.0 : 0.0;

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $day->toDateString(),
                    ],
                    [
                        'check_in' => $onLeave ? null : ($isLate ? '08:18:00' : '07:55:00'),
                        'check_out' => $onLeave ? null : ($overtime > 0 ? '19:00:00' : '17:05:00'),
                        'status' => $onLeave ? 'leave' : ($isLate ? 'late' : ($overtime > 0 ? 'overtime' : 'present')),
                        'work_hours' => $onLeave ? 0 : ($overtime > 0 ? 10 : ($isLate ? 8.2 : 8.5)),
                        'late_minutes' => $isLate ? 18 : 0,
                        'late_penalty_fee' => $isLate ? 50000 : 0,
                        'early_leave_minutes' => 0,
                        'overtime_hours' => $overtime,
                        'check_in_latitude' => $onLeave ? null : self::OFFICE_LAT,
                        'check_in_longitude' => $onLeave ? null : self::OFFICE_LNG,
                        'check_out_latitude' => $onLeave ? null : self::OFFICE_LAT,
                        'check_out_longitude' => $onLeave ? null : self::OFFICE_LNG,
                        'check_in_location' => $onLeave ? null : self::OFFICE,
                        'check_out_location' => $onLeave ? null : self::OFFICE,
                        'check_in_ip_address' => $onLeave ? null : '192.168.1.20',
                        'check_out_ip_address' => $onLeave ? null : '192.168.1.20',
                        'check_in_distance' => $onLeave ? null : 12.5,
                        'check_out_distance' => $onLeave ? null : 15.2,
                        'check_in_notes' => $onLeave ? 'Nghỉ phép năm' : ($isLate ? 'Đến muộn 18 phút' : 'Đúng giờ'),
                        'check_out_notes' => $onLeave ? null : ($overtime > 0 ? 'Tăng ca đến 19:00' : 'Tan ca'),
                        'check_in_location_missing' => 0,
                        'check_out_location_missing' => 0,
                        'location' => self::OFFICE,
                        'attendance_method' => 'web',
                        'attendance_status' => 'approved',
                        'approved_by' => $hr?->id,
                        'approved_at' => $day->copy()->setTime(18, 0),
                        'notes' => $onLeave ? 'Nghỉ phép có lương' : 'Chấm công tại văn phòng',
                    ]
                );
            }
        }
    }

    private function rebuildPayrollAmounts(): void
    {
        $service = app(PayrollCalculationService::class);
        foreach (Payroll::with('employee.positionDetail', 'employee.contracts')->orderBy('id')->get() as $payroll) {
            $service->rebuildStoredAmounts($payroll);
        }
    }

    private function fillEmpty($model, array $values, bool $replaceZero = false): void
    {
        $table = $model->getTable();
        $patch = [];

        foreach ($values as $column => $value) {
            if ($value === null || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $current = $model->getAttributes()[$column] ?? $model->getAttribute($column);
            $empty = $current === null || $current === '';
            if (
                ! $empty
                && $replaceZero
                && is_numeric($current)
                && (float) $current == 0.0
                && is_numeric($value)
                && (float) $value != 0.0
            ) {
                $empty = true;
            }
            if ($empty) {
                $patch[$column] = $value;
            }
        }

        if ($patch !== []) {
            $model->forceFill($patch)->save();
        }
    }

    private function contractBody(): string
    {
        return "Điều 1. Người lao động cam kết thực hiện công việc theo mô tả vị trí, nội quy lao động và sự phân công của quản lý trực tiếp.\n"
            ."Điều 2. Thời giờ làm việc 08:00–17:00, thứ Hai đến thứ Sáu, nghỉ giữa ca 12:00–13:00.\n"
            ."Điều 3. Tiền lương, phụ cấp và phúc lợi được chi trả theo hợp đồng và quy chế lương thưởng của công ty.\n"
            ."Điều 4. Công ty đóng BHXH, BHYT, BHTN theo quy định pháp luật.\n"
            ."Điều 5. Hai bên có quyền chấm dứt hợp đồng theo Bộ luật Lao động hiện hành.";
    }
}
