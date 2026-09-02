<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Support\LeaveTypes;
use App\Traits\HasLeaveLimit;
use Carbon\Carbon;
use RuntimeException;

class LeaveEligibilityService
{
    use HasLeaveLimit;

    public function activeContract(Employee $employee): ?Contract
    {
        return $employee->contracts()
            ->where('status', Contract::STATUS_ACTIVE)
            ->latest('start_date')
            ->first();
    }

    /**
     * @return array{ok: bool, message: ?string, days: float, contract: ?Contract, quota: array}
     */
    public function assertEligible(Employee $employee, string $type, string $startDate, string $endDate, bool $halfDay, ?int $excludeId = null): array
    {
        $days = $this->calculateLeaveDays($startDate, $endDate, $halfDay);
        $contract = $this->activeContract($employee);
        $quota = $this->quotaSummary($employee, $excludeId);

        if (! $contract) {
            throw new RuntimeException('Bạn chưa có hợp đồng đang hiệu lực. Không thể gửi đơn nghỉ phép.');
        }

        if ($type === 'maternity' && ! $employee->isFemale()) {
            throw new RuntimeException('Nghỉ thai sản chỉ áp dụng cho nhân viên nữ.');
        }

        if (! in_array($type, LeaveTypes::keys($employee), true)) {
            throw new RuntimeException('Loại nghỉ phép không hợp lệ.');
        }

        if ($this->hasOverlap($employee->id, $startDate, $endDate, $excludeId)) {
            throw new RuntimeException('Khoảng ngày này đã trùng với đơn nghỉ phép khác (đang chờ hoặc đã duyệt).');
        }

        $message = $this->typeLimitMessage($employee, $contract, $type, $days, $startDate, $excludeId);
        if ($message) {
            throw new RuntimeException($message);
        }

        return [
            'ok' => true,
            'message' => null,
            'days' => $days,
            'contract' => $contract,
            'quota' => $quota,
        ];
    }

    public function quotaSummary(Employee $employee, ?int $excludeId = null): array
    {
        $contract = $this->activeContract($employee);
        $legalAnnual = $this->laborLawAnnualDays($employee);
        $profileAnnual = (int) ($employee->leave_balance ?: 0);
        $annualEntitlement = $this->annualEntitlement($employee);
        $usedAnnual = $this->usedDays($employee->id, 'annual', now()->year, null, $excludeId);
        $usedUnpaidMonth = $this->usedDays($employee->id, ['unpaid', 'personal'], now()->year, now()->month, $excludeId);
        $usedMaternity = $this->usedDays($employee->id, 'maternity', null, null, $excludeId);
        $unpaidCap = (int) ($contract?->allowed_unpaid_leave_days_per_month ?? 1);
        $legalMaternity = 180;
        $contractMaternity = (int) ($contract?->allowed_maternity_leave_days ?? 0);
        $maternityCap = max($legalMaternity, $contractMaternity ?: $legalMaternity);

        $usedDays = $usedAnnual;
        $maxDays = $annualEntitlement;

        return [
            'has_contract' => (bool) $contract,
            'used_days' => $usedDays,
            'max_days' => $maxDays,
            'remaining_days' => max(0, $maxDays - $usedDays),
            'used_requests' => 0,
            'max_requests' => 0,
            'annual_used' => $usedAnnual,
            'annual_max' => $annualEntitlement,
            'annual_remaining' => max(0, $annualEntitlement - $usedAnnual),
            'annual_legal' => $legalAnnual,
            'annual_profile' => $profileAnnual,
            'unpaid_used' => $usedUnpaidMonth,
            'unpaid_max' => $unpaidCap,
            'unpaid_remaining' => max(0, $unpaidCap - $usedUnpaidMonth),
            'maternity_used' => $usedMaternity,
            'maternity_max' => $maternityCap,
            'maternity_remaining' => max(0, $maternityCap - $usedMaternity),
            'maternity_legal' => $legalMaternity,
            'types' => $this->typeGuidesFromQuota($employee, [
                'annual_used' => $usedAnnual,
                'annual_max' => $annualEntitlement,
                'annual_remaining' => max(0, $annualEntitlement - $usedAnnual),
                'annual_legal' => $legalAnnual,
                'annual_profile' => $profileAnnual,
                'unpaid_used' => $usedUnpaidMonth,
                'unpaid_max' => $unpaidCap,
                'unpaid_remaining' => max(0, $unpaidCap - $usedUnpaidMonth),
                'maternity_used' => $usedMaternity,
                'maternity_max' => $maternityCap,
                'maternity_remaining' => max(0, $maternityCap - $usedMaternity),
                'maternity_legal' => $legalMaternity,
            ]),
        ];
    }

    /**
     * Số ngày phép năm theo BLLĐ 2019 Điều 113–114: 12 ngày/năm đủ 12 tháng,
     * tính tỷ lệ nếu chưa đủ năm; cộng 1 ngày mỗi 5 năm thâm niên.
     */
    public function laborLawAnnualDays(Employee $employee): int
    {
        $start = $employee->start_date ?? $this->activeContract($employee)?->start_date;
        if (! $start) {
            return 12;
        }

        $start = Carbon::parse($start)->startOfDay();
        $months = max(0, (int) $start->diffInMonths(now()->startOfDay()));
        if ($months < 12) {
            return $months;
        }

        $years = intdiv($months, 12);

        return 12 + intdiv($years, 5);
    }

    public function annualEntitlement(Employee $employee): int
    {
        $legal = $this->laborLawAnnualDays($employee);
        $profile = (int) ($employee->leave_balance ?: 0);

        return max($legal, $profile ?: $legal);
    }

    /**
     * @param  array<string, int|float>  $quota
     * @return array<string, array<string, mixed>>
     */
    public function typeGuidesFromQuota(Employee $employee, array $quota): array
    {
        $guides = [
            'annual' => [
                'label' => LeaveTypes::label('annual'),
                'capped' => true,
                'allowed' => $quota['annual_max'],
                'used' => $quota['annual_used'],
                'remaining' => $quota['annual_remaining'],
                'unit' => 'ngày/năm',
                'basis' => sprintf(
                    'BLLĐ 2019 Điều 113–114: %d ngày (12 ngày/năm, cộng thâm niên). Hồ sơ/HĐ: %d ngày. Áp dụng mức cao hơn: %d ngày.',
                    $quota['annual_legal'],
                    $quota['annual_profile'] ?: $quota['annual_legal'],
                    $quota['annual_max']
                ),
            ],
            'sick' => [
                'label' => LeaveTypes::label('sick'),
                'capped' => false,
                'allowed' => null,
                'used' => null,
                'remaining' => null,
                'unit' => 'theo BHXH',
                'basis' => 'Luật BHXH / BLLĐ: nghỉ ốm hưởng chế độ BHXH theo giấy của cơ sở y tế. Hợp đồng công ty không khống chế số ngày.',
            ],
            'personal' => [
                'label' => LeaveTypes::label('personal'),
                'capped' => true,
                'allowed' => $quota['unpaid_max'],
                'used' => $quota['unpaid_used'],
                'remaining' => $quota['unpaid_remaining'],
                'unit' => 'ngày/tháng',
                'basis' => sprintf(
                    'BLLĐ không ấn định ngày nghỉ việc riêng có hưởng lương. Hợp đồng đang hiệu lực cho tối đa %d ngày/tháng.',
                    $quota['unpaid_max']
                ),
            ],
            'unpaid' => [
                'label' => LeaveTypes::label('unpaid'),
                'capped' => true,
                'allowed' => $quota['unpaid_max'],
                'used' => $quota['unpaid_used'],
                'remaining' => $quota['unpaid_remaining'],
                'unit' => 'ngày/tháng',
                'basis' => sprintf(
                    'Nghỉ không lương theo thỏa thuận / hợp đồng. Hạn mức HĐ: tối đa %d ngày/tháng.',
                    $quota['unpaid_max']
                ),
            ],
        ];

        if ($employee->isFemale()) {
            $guides['maternity'] = [
                'label' => LeaveTypes::label('maternity'),
                'capped' => true,
                'allowed' => $quota['maternity_max'],
                'used' => $quota['maternity_used'],
                'remaining' => $quota['maternity_remaining'],
                'unit' => 'ngày/thai kỳ',
                'basis' => sprintf(
                    'BLLĐ 2019 Điều 139: 6 tháng (180 ngày). Hợp đồng: %d ngày. Áp dụng mức cao hơn: %d ngày.',
                    $quota['maternity_legal'],
                    $quota['maternity_max']
                ),
            ];
        }

        return $guides;
    }

    private function typeLimitMessage(Employee $employee, Contract $contract, string $type, float $days, string $startDate, ?int $excludeId): ?string
    {
        $start = Carbon::parse($startDate);

        return match ($type) {
            'annual' => $this->exceeds(
                $this->usedDays($employee->id, 'annual', $start->year, null, $excludeId) + $days,
                $this->annualEntitlement($employee),
                'Nghỉ phép năm vượt số ngày được phép theo hợp đồng và BLLĐ (tối đa %s ngày/năm).'
            ),
            'unpaid', 'personal' => $this->exceeds(
                $this->usedDays($employee->id, ['unpaid', 'personal'], $start->year, $start->month, $excludeId) + $days,
                (int) ($contract->allowed_unpaid_leave_days_per_month ?? 1),
                'Nghỉ việc riêng/không lương vượt hạn mức hợp đồng (tối đa %s ngày/tháng).'
            ),
            'maternity' => $this->exceeds(
                $this->usedDays($employee->id, 'maternity', null, null, $excludeId) + $days,
                max(180, (int) ($contract->allowed_maternity_leave_days ?? 180)),
                'Nghỉ thai sản vượt hạn mức BLLĐ/hợp đồng (tối đa %s ngày).'
            ),
            default => null,
        };
    }

    private function exceeds(float $used, int $cap, string $template): ?string
    {
        if ($used > $cap) {
            return sprintf($template, $cap);
        }

        return null;
    }

    private function usedDays(int $employeeId, string|array $types, ?int $year, ?int $month, ?int $excludeId): float
    {
        $query = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->whereIn('type', (array) $types)
            ->whereNotIn('status', ['rejected', 'cancelled']);

        if ($year) {
            $query->whereYear('start_date', $year);
        }
        if ($month) {
            $query->whereMonth('start_date', $month);
        }
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return (float) $query->sum('days');
    }

    private function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $excludeId): bool
    {
        $query = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
