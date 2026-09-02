<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Database\Seeder;

/**
 * Đồng bộ dữ liệu theo quy tắc lương mới:
 * lương = max(lương theo chức vụ (positions.base_salary), lương CB hợp đồng đang hiệu lực).
 * Cập nhật: base_salary hợp đồng active + tính lại các phiếu lương theo lương mới.
 */
class SyncSalaryToPositionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $svc  = app(PayrollCalculationService::class);
        $actor = User::where('is_hr', true)->orWhere('is_director', true)->orderBy('id')->first();

        $contractsUpdated = 0;
        $payrollsUpdated  = 0;
        $employeesTouched = 0;

        $employees = Employee::with('positionDetail')->where('status', 'active')->orderBy('id')->get();

        foreach ($employees as $employee) {
            $positionBase = (int) ($employee->positionDetail?->base_salary ?? 0);
            if ($positionBase <= 0) {
                $positionBase = match ($employee->position) {
                    'Giám Đốc'       => 13000000,
                    'Trưởng Phòng Nhân Sự' => 10400000,
                    default          => 7800000,
                };
            }

            $contract     = Contract::where('employee_id', $employee->id)
                ->where('status', Contract::STATUS_ACTIVE)
                ->latest('id')
                ->first();
            $contractBase = (int) ($contract?->base_salary ?: $contract?->salary ?: 0);

            $newBase = max($positionBase, $contractBase);

            // 1) Hợp đồng → lương theo quy tắc mới (chỉ tăng lên mức chức vụ nếu thấp hơn)
            if ($contract && (int) $contract->base_salary !== $newBase) {
                $oldBase = (int) $contract->base_salary;

                $contract->base_salary = $newBase;
                $contract->salary      = $newBase;
                $contract->save();

                ContractLog::create([
                    'contract_id' => $contract->id,
                    'user_id'     => $actor?->id,
                    'action'      => 'salary_updated',
                    'message'     => 'Đồng bộ lương theo quy tắc chức vụ (không thấp hơn lương CB)',
                    'details'     => [
                        'old_base_salary' => $oldBase,
                        'new_base_salary' => $newBase,
                    ],
                ]);

                Notification::create([
                    'sender_id' => $actor?->id,
                    'target'    => 'employee',
                    'title'     => 'Mức lương trên hợp đồng thay đổi',
                    'message'   => sprintf(
                        'Hợp đồng %s của bạn được điều chỉnh lương cơ bản từ %s₫ lên %s₫ theo mức chức vụ.',
                        $contract->contract_code,
                        number_format($oldBase, 0, ',', '.'),
                        number_format($newBase, 0, ',', '.')
                    ),
                    'data' => [
                        'type'            => 'contract_salary_updated',
                        'contract_id'     => $contract->id,
                        'employee_id'     => $employee->id,
                        'old_base_salary' => $oldBase,
                        'new_base_salary' => $newBase,
                    ],
                    'is_read' => false,
                ]);

                $contractsUpdated++;
            }

            // 2) Tính lại các phiếu lương theo lương mới
            $touched = $contract && (int) $contract->base_salary === $newBase;
            $payrolls = Payroll::where('employee_id', $employee->id)->get();
            foreach ($payrolls as $payroll) {
                $workingDays  = (float) ($payroll->working_days ?: 0);
                $overtimeDays = (float) ($payroll->overtime_days ?: 0);
                $overtimeHrs  = (float) ($payroll->overtime_hours ?: 0);

                $daily               = $newBase / 26;
                $hourSalary          = $daily / 8;
                $workingSalary       = $workingDays * $daily;
                $overtimeDaySalary   = $overtimeDays * $daily * 1.5;
                $overtimeHourSalary  = $overtimeHrs * $hourSalary * 1.5;
                $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

                $allowance = (float) ($payroll->allowance ?: 0);
                $bonus     = (float) ($payroll->bonus ?: 0);
                $deduction = (float) ($payroll->deduction ?: 0);
                $lateFee   = (float) ($payroll->late_penalty_fee ?: 0);

                $insurance = round($newBase * 0.105, 2);
                $taxable   = $workingSalary + $totalOvertimeSalary + $allowance + $bonus - $insurance;
                $tax       = round($svc->calculateTax($taxable), 2);
                $total     = max(0, $workingSalary + $totalOvertimeSalary + $allowance + $bonus - $insurance - $tax - $deduction - $lateFee);

                if ((float) $payroll->base_salary === (float) $newBase) {
                    continue;
                }

                $payroll->base_salary           = $newBase;
                $payroll->daily_salary          = round($daily, 2);
                $payroll->working_salary        = round($workingSalary, 2);
                $payroll->overtime_day_salary   = round($overtimeDaySalary, 2);
                $payroll->overtime_hour_salary  = round($overtimeHourSalary, 2);
                $payroll->overtime_salary       = round($totalOvertimeSalary, 2);
                $payroll->insurance             = $insurance;
                $payroll->tax                   = $tax;
                $payroll->total_salary          = round($total, 2);
                $payroll->save();

                $payrollsUpdated++;
                $touched = true;
            }

            if ($touched) {
                $employeesTouched++;
            }
        }

        ActivityLog::create([
            'user_id' => $actor?->id,
            'action'  => 'salary_rule_synced',
            'meta'    => sprintf('contracts_updated:%d;payrolls_updated:%d;employees:%d', $contractsUpdated, $payrollsUpdated, $employeesTouched),
        ]);

        $this->command?->info(sprintf(
            'Xong: %d hợp đồng + %d phiếu lương được đồng bộ theo quy tắc chức vụ (%d nhân viên).',
            $contractsUpdated, $payrollsUpdated, $employeesTouched
        ));
    }
}