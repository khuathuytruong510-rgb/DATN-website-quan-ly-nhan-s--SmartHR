<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Benefit;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriodLock;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FillEmployeeFullDataSeeder extends Seeder
{
    private const HOLIDAYS = [
        '2026-04-30',
        '2026-05-01',
        '2026-05-04',
    ];

    public function run(): void
    {
        $this->seedContracts();
        $this->seedBenefits();
        $this->seedAttendance();
        $this->unlockAugust();
    }

    private function adminId(): ?int
    {
        return User::where('is_admin', true)->value('id') ?? User::first()->id ?? null;
    }

    private function directorId(): ?int
    {
        return User::where('is_director', true)->value('id') ?? $this->adminId();
    }

    private function seedContracts(): void
    {
        $admin = $this->adminId();
        $director = $this->directorId();
        $locations = [
            'Trụ sở chính - Tầng 3, Tòa nhà SmartHr Tower, Hà Nội',
            'Chi nhánh Đà Nẵng - 456 Hải Phòng, Đà Nẵng',
            'VP Hồ Chí Minh - 789 Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh',
        ];

        $employees = Employee::with('positionDetail')->get();
        $made = 0;

        foreach ($employees as $emp) {
            $salary = (int) ($emp->positionDetail?->base_salary ?: $this->fallbackSalary($emp->position));
            $allowance = (int) ($emp->positionDetail?->allowance ?: 0);

            $start = Carbon::parse($emp->start_date ?: now());
            $end = $start->copy()->addYear();
            $signDate = $start->copy()->subWeek();

            $terms = $this->contractTerms();

            $data = [
                'title' => 'Hợp đồng lao động - '.($emp->position ?: 'Nhân viên'),
                'salary' => $salary,
                'contract_type' => 'official',
                'sign_date' => $signDate,
                'base_salary' => $salary,
                'allowance' => $allowance,
                'bonus' => 0,
                'payment_method' => 'bank_transfer',
                'terms' => $terms,
                'contract_content' => $terms,
                'additional_terms' => 'Phụ cấp '.$this->numberText($allowance).'/tháng. Thưởng lễ Tết theo quy chế công ty.',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'employee_signed_at' => $signDate->copy()->subDay()->format('Y-m-d H:i:s'),
                'director_signed_at' => $signDate->format('Y-m-d H:i:s'),
                'contract_template_id' => 1,
                'workplace' => $locations[array_rand($locations)],
                'working_schedule' => 'morning_evening',
                'benefits' => 'BHXH, BHYT, BHTN; 12 ngày phép năm; phụ cấp ăn trưa và điện thoại.',
                'allowed_unpaid_leave_days_per_month' => 2,
                'allowed_makeup_attendance_per_month' => 2,
                'allowed_maternity_leave_days' => 180,
                'notes' => 'Hợp đồng lao động xác định thời hạn theo Bộ luật Lao động 2019.',
            ];

            $extra = [
                'probation_salary' => (int) round($salary * 0.85),
                'company_representative' => 'Phạm Thị Dung',
                'signer' => 'Phạm Thị Dung',
                'signed_date' => $signDate->toDateString(),
                'pdf_file' => '',
                'scan_file' => '',
            ];

            $contract = Contract::where('employee_id', $emp->id)->orderBy('id')->first();

            if ($contract) {
                $contract->fill($data)->forceFill($extra)->save();
            } else {
                $contract = new Contract(array_merge($data, [
                    'employee_id' => $emp->id,
                    'contract_code' => 'HD-'.$emp->employee_code,
                    'status' => 'active',
                    'contract_status' => 'pending',
                    'created_by' => $admin,
                    'signer_id' => $director,
                ]));
                $contract->forceFill($extra)->save();
            }

            $made++;
        }

        $this->command?->info("Đã đồng bộ đầy đủ {$made} hợp đồng lao động.");
    }

    private function contractTerms(): string
    {
        return implode("\n", [
            'Điều 1. Người lao động cam kết thực hiện công việc theo mô tả vị trí, nội quy lao động và sự phân công của quản lý trực tiếp.',
            'Điều 2. Thời giờ làm việc 08:00–17:00, thứ Hai đến thứ Sáu, nghỉ giữa ca 12:00–13:00.',
            'Điều 3. Tiền lương, phụ cấp và phúc lợi được chi trả theo hợp đồng và quy chế lương thưởng của công ty.',
            'Điều 4. Công ty đóng BHXH, BHYT, BHTN theo quy định pháp luật.',
            'Điều 5. Hai bên có quyền chấm dứt hợp đồng theo Bộ luật Lao động hiện hành.',
        ]);
    }

    private function numberText(int $amount): string
    {
        return $amount > 0 ? number_format($amount, 0, ',', '.').'đ' : 'theo quy chế';
    }

    private function fallbackSalary(string $position): int
    {
        $p = mb_strtolower($position . ' ');
        if (str_contains($p, 'giám đốc') || str_contains($p, 'manager') || str_contains($p, 'trưởng phòng')) {
            return 20000000;
        }

        return 10000000;
    }

    private function seedBenefits(): void
    {
        $admin = $this->adminId();
        $employees = Employee::select('id', 'employee_code')->get();
        $titles = [
            'Phụ cấp ăn trưa' => ['type' => 'allowance', 'amount' => 800000],
            'Bảo hiểm sức khỏe bổ sung' => ['type' => 'insurance', 'amount' => 1000000],
            'Thưởng chuyên cần' => ['type' => 'bonus', 'amount' => 500000],
            'Thưởng hiệu quả công việc' => ['type' => 'bonus', 'amount' => 1000000],
            'Phụ cấp điện thoại' => ['type' => 'allowance', 'amount' => 300000],
            'Bảo hiểm tai nạn lao động' => ['type' => 'insurance', 'amount' => 0],
        ];

        $made = 0;
        foreach ($employees as $emp) {
            if (mt_rand(1, 100) > 50 || Benefit::where('employee_id', $emp->id)->exists()) {
                continue;
            }
            $title = array_rand($titles);
            $spec = $titles[$title];

            Benefit::create([
                'employee_id' => $emp->id,
                'created_by' => $admin,
                'code' => 'BH-'.$emp->employee_code,
                'title' => $title,
                'description' => 'Phúc lợi: '.$title,
                'type' => $spec['type'],
                'amount' => $spec['amount'],
                'unit' => 'vnd',
                'applies_to' => 'all',
                'condition' => 'Nhân viên chính thức',
                'effective_date' => '2026-01-01',
                'expiry_date' => '2026-12-31',
                'application_status' => 'active',
                'status' => 'approved',
                'approval_status' => 'approved',
                'approved_by' => $admin,
                'approved_at' => now(),
            ]);
            $made++;
        }

        $this->command?->info("Đã tạo {$made} phúc lợi nhân viên.");
    }

    private function seedAttendance(): void
    {
        $employees = Employee::select('id', 'employee_code')->get()->keyBy('id');
        $cursor = Carbon::parse('2026-04-01')->startOfDay();
        $end = Carbon::parse('2026-08-31')->endOfDay();

        $rows = [];
        $total = 0;

        while ($cursor <= $end) {
            $ymd = $cursor->format('Y-m-d');

            if ($cursor->isWeekend() || in_array($ymd, self::HOLIDAYS, true)) {
                $cursor->addDay();
                continue;
            }

            foreach ($employees as $emp) {
                $row = $this->attendanceRow($emp->id, $ymd);
                if ($row) {
                    $rows[] = $row;
                    $total++;
                }

                if (count($rows) >= 500) {
                    $this->upsertRows($rows);
                    $rows = [];
                }
            }

            $cursor->addDay();
        }

        if ($rows) {
            $this->upsertRows($rows);
        }

        $this->command?->info("Đã đồng bộ {$total} bản ghi chấm công (tháng 4-8/2026).");
    }

    private function upsertRows(array $rows): void
    {
        DB::table('attendances')->upsert($rows, ['employee_id', 'date'], [
            'check_in', 'check_out', 'check_in_location', 'check_out_location',
            'work_hours', 'late_minutes', 'early_leave_minutes', 'overtime_hours',
            'status', 'attendance_method', 'attendance_status', 'created_at', 'updated_at',
        ]);
    }

    private function attendanceRow(int $employeeId, string $ymd): ?array
    {
        mt_srand(crc32($employeeId.'-'.$ymd));
        $roll = mt_rand(1, 100);

        $base = Carbon::parse($ymd);
        $method = mt_rand(1, 3) === 1 ? 'web' : 'manual';
        $statusApproved = 'approved';
        $statusRecorded = 'recorded';

        if ($roll <= 3) {
            return $this->finalize([
                'employee_id' => $employeeId,
                'date' => $ymd,
                'status' => 'absent',
                'check_in' => null,
                'check_out' => null,
                'work_hours' => null,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_hours' => 0,
                'attendance_method' => 'manual',
                'attendance_status' => $statusRecorded,
                'created_at' => $base->format('Y-m-d 18:00:00'),
            ]);
        }

        if ($roll <= 6) {
            return $this->finalize([
                'employee_id' => $employeeId,
                'date' => $ymd,
                'status' => 'leave',
                'check_in' => null,
                'check_out' => null,
                'work_hours' => null,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_hours' => 0,
                'attendance_method' => 'manual',
                'attendance_status' => $statusRecorded,
                'created_at' => $base->format('Y-m-d 18:00:00'),
            ]);
        }

        $inMinutes = 480 + mt_rand(0, 6);
        $outMinutes = 1020 + mt_rand(-10, 20);

        if ($roll <= 16) {
            $inMinutes = 485 + mt_rand(0, 40);
            $status = 'late';
            $lateMinutes = max(0, $inMinutes - 480);
        } elseif ($roll <= 21) {
            $outMinutes = 980 + mt_rand(0, 50);
            $status = 'leave_early';
            $lateMinutes = 0;
        } elseif ($roll <= 24) {
            $inMinutes = 485 + mt_rand(0, 25);
            $outMinutes = 980 + mt_rand(0, 50);
            $status = 'late_and_leave_early';
            $lateMinutes = max(0, $inMinutes - 480);
        } elseif ($roll <= 28) {
            $outMinutes = 1080 + mt_rand(0, 90);
            $status = 'overtime';
            $lateMinutes = 0;
        } else {
            $status = 'present';
            $lateMinutes = 0;
        }

        $earlyLeave = $status === 'leave_early' || $status === 'late_and_leave_early'
            ? max(0, 1020 - $outMinutes)
            : 0;
        $overtime = $status === 'overtime'
            ? max(0, $outMinutes - 1020) / 60
            : 0;
        $workHours = max(0, $outMinutes - $inMinutes - 60) / 60;

        return $this->finalize([
            'employee_id' => $employeeId,
            'date' => $ymd,
            'status' => $status,
            'check_in' => sprintf('%02d:%02d:00', intdiv($inMinutes, 60), $inMinutes % 60),
            'check_out' => sprintf('%02d:%02d:00', intdiv($outMinutes, 60), $outMinutes % 60),
            'work_hours' => round($workHours, 1),
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeave,
            'overtime_hours' => round($overtime, 1),
            'attendance_method' => $method,
            'attendance_status' => $statusApproved,
            'created_at' => $base->format('Y-m-d 18:00:00'),
        ]);
    }

    private function finalize(array $row): array
    {
        $method = $row['attendance_method'] ?? 'manual';
        $presentLike = in_array($row['status'], ['present', 'late', 'leave_early', 'late_and_leave_early', 'overtime'], true);
        $loc = 'Trụ sở chính - Tòa nhà SmartHr Tower, Hà Nội';

        $row['check_in_location'] = $presentLike ? $loc : null;
        $row['check_out_location'] = $presentLike ? $loc : null;
        $row['updated_at'] = $row['created_at'] ?? now()->format('Y-m-d H:i:s');

        return $row;
    }

    private function unlockAugust(): void
    {
        PayrollPeriodLock::where('month', 8)->where('year', 2026)->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
            'unlocked_at' => now(),
            'unlock_reason' => 'Chưa chốt lương tháng 8/2026 - còn tiến hành các bước bổ sung.',
        ]);

        $this->command?->info('Đã mở khóa kỳ lương 8/2026 (chưa chốt lương).');
    }
}