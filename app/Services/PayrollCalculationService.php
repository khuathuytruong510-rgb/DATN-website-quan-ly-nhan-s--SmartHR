<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function calculateTax(float $taxableIncome): float
    {
        if ($taxableIncome <= 5000000) {
            return 0;
        } elseif ($taxableIncome <= 10000000) {
            return ($taxableIncome - 5000000) * 0.05;
        } elseif ($taxableIncome <= 18000000) {
            return 250000 + (($taxableIncome - 10000000) * 0.10);
        } elseif ($taxableIncome <= 32000000) {
            return 1050000 + (($taxableIncome - 18000000) * 0.15);
        } elseif ($taxableIncome <= 52000000) {
            return 3150000 + (($taxableIncome - 32000000) * 0.20);
        } elseif ($taxableIncome <= 80000000) {
            return 7150000 + (($taxableIncome - 52000000) * 0.25);
        } else {
            return 14150000 + (($taxableIncome - 80000000) * 0.30);
        }
    }

    public function normalizeOvertimeHours($value): float
    {
        if (is_string($value) && str_contains($value, ':')) {
            $parts = explode(':', $value);
            return (float)($parts[0] ?? 0) + (float)($parts[1] ?? 0) / 60;
        }
        if ($value > 1000) {
            return round($value / 3600, 2);
        }
        if ($value > 100) {
            return round($value / 60, 2);
        }
        return (float) $value;
    }

    public function calculateNetSalary(float $gross, float $insurance, float $tax): float
    {
        return max(0, $gross - $insurance - $tax);
    }

    /**
     * Tính / tính lại một phiếu. Backend tự tính số liệu và gán calculated.
     * Không nhận status / total_salary từ client.
     *
     * Chỉ được tính phiếu mới, nháp, đã tính, hoặc đang sự cố.
     */
    public function calculate(Employee $employee, int $month, int $year)
    {
        return DB::transaction(function () use ($employee, $month, $year) {
            $existing = Payroll::query()
                ->where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($existing && ! in_array($existing->status, PayrollPaymentWorkflowService::recalculableStatuses(), true)) {
                throw new PayrollNotRecalculableException(
                    'Không được tính lại phiếu đã vào vòng HR kiểm tra / Giám đốc duyệt / NV xác nhận / đã thanh toán. Phải đi vòng sự cố chính thức.'
                );
            }

            app(PayrollPeriodLockService::class)->assertUnlockedForCalculation($month, $year);

            return $this->persistCalculated($employee, $month, $year, $existing);
        });
    }

    /**
     * Tính cả kỳ cho nhân viên đang làm. Bỏ qua phiếu không được tính lại.
     */
    public function calculatePeriod(int $month, int $year, ?User $actor = null): array
    {
        app(PayrollPeriodLockService::class)->assertUnlockedForCalculation($month, $year);

        $calculated = 0;
        $skipped = 0;

        $employees = Employee::query()->where('status', 'active')->orderBy('id')->get();
        foreach ($employees as $employee) {
            try {
                $this->calculate($employee, $month, $year);
                $calculated++;
            } catch (PayrollNotRecalculableException) {
                $skipped++;
            }
        }

        if ($actor) {
            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_calculated',
                'meta' => sprintf('period:%02d/%d;calculated:%d;skipped:%d', $month, $year, $calculated, $skipped),
            ]);
        }

        return compact('calculated', 'skipped', 'month', 'year');
    }

    protected function persistCalculated(Employee $employee, int $month, int $year, ?Payroll $existing): Payroll
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Lương cơ bản theo chức vụ
        |--------------------------------------------------------------------------
        */
        $baseSalary = match ($employee->position) {
            'Giám Đốc' => 13000000,
            'Trưởng Phòng Nhân Sự' => 10400000,
            default => 7800000,
        };

        /*
        |--------------------------------------------------------------------------
        | 2. Quy định công chuẩn
        |--------------------------------------------------------------------------
        */
        $requiredWorkingDays = 26;
        $dailySalary = $baseSalary / $requiredWorkingDays;
        $hourSalary = $dailySalary / 8;

        /*
        |--------------------------------------------------------------------------
        | 3. Lấy dữ liệu chấm công
        |--------------------------------------------------------------------------
        */
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. Đếm số ngày làm thực tế
        |--------------------------------------------------------------------------
        */
        $actualWorkingDays = 0;
        foreach ($attendances as $attendance) {
            if (
                $attendance->check_in &&
                $attendance->check_out &&
                $attendance->work_hours >= 8
            ) {
                $actualWorkingDays++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Lấy dữ liệu Nghỉ phép đã được duyệt (status = 'approved')
        |--------------------------------------------------------------------------
        */
        $approvedLeaveRequests = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($month, $year) {
                $query->whereMonth('start_date', $month)->whereYear('start_date', $year)
                    ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
            })
            ->get();

        $paidLeaveDays = 0;   // Nghỉ hưởng lương (Phép năm - annual)
        $unpaidLeaveDays = 0; // Nghỉ không hưởng lương (Sick, Unpaid, Personal...)

        foreach ($approvedLeaveRequests as $leave) {
            if ($leave->type === 'annual') {
                $paidLeaveDays += $leave->days;
            } else {
                $unpaidLeaveDays += $leave->days;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Tổng số ngày công tính lương (Thực tế + Nghỉ phép năm, tối đa 26)
        |--------------------------------------------------------------------------
        */
        $payableDays = $actualWorkingDays + $paidLeaveDays;
        $workingDays = min($payableDays, $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 7. Ngày tăng ca
        |--------------------------------------------------------------------------
        */
        $overtimeDays = max(0, $actualWorkingDays - $requiredWorkingDays);

        /*
        |--------------------------------------------------------------------------
        | 8. Tổng giờ tăng ca
        |--------------------------------------------------------------------------
        */
        $overtimeHours = $attendances->sum('overtime_hours');

        /*
        |--------------------------------------------------------------------------
        | 9. Lương ngày công
        |--------------------------------------------------------------------------
        */
        $workingSalary = $workingDays * $dailySalary;

        /*
        |--------------------------------------------------------------------------
        | 10. Tiền tăng ca (150%)
        |--------------------------------------------------------------------------
        */
        $overtimeDaySalary = $overtimeDays * $dailySalary * 1.5;
        $overtimeHourSalary = $overtimeHours * $hourSalary * 1.5;
        $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

        /*
        |--------------------------------------------------------------------------
        | 11. Phụ cấp & Thưởng
        | (Thưởng 500k nếu tổng ngày công tính lương >= 26)
        |--------------------------------------------------------------------------
        */
        $allowance = 500000;
        $bonus = ($payableDays >= 26) ? 500000 : 0;

        /*
        |--------------------------------------------------------------------------
        | 12. Khấu trừ & Tiền phạt đi muộn
        |--------------------------------------------------------------------------
        */
        $deduction = 0;
        $totalLatePenaltyFee = $attendances->sum('late_penalty_fee');

        /*
        |--------------------------------------------------------------------------
        | 13. Bảo hiểm (10.5% lương cơ bản)
        |--------------------------------------------------------------------------
        */
        $insurance = $baseSalary * 0.105;

        /*
        |--------------------------------------------------------------------------
        | 14. Thuế TNCN
        |--------------------------------------------------------------------------
        */
        $taxableIncome = $workingSalary + $totalOvertimeSalary + $allowance + $bonus - $insurance;
        $tax = $this->calculateTax($taxableIncome);

        /*
        |--------------------------------------------------------------------------
        | 15. Lương thực nhận
        |--------------------------------------------------------------------------
        */
        $totalSalary = $workingSalary
            + $totalOvertimeSalary
            + $allowance
            + $bonus
            - $insurance
            - $tax
            - $deduction
            - $totalLatePenaltyFee;

        /*
        |--------------------------------------------------------------------------
        | 16. Lưu / Cập nhật bảng lương
        |--------------------------------------------------------------------------
        */
        $payload = [
            'base_salary'           => $baseSalary,
            'daily_salary'          => round($dailySalary, 2),
            'required_working_days' => $requiredWorkingDays,
            'working_days'          => $actualWorkingDays,
            'paid_leave_days'       => $paidLeaveDays,
            'unpaid_leave_days'     => $unpaidLeaveDays,
            'working_salary'        => round($workingSalary, 2),
            'overtime_days'         => $overtimeDays,
            'overtime_hours'        => round($overtimeHours, 2),
            'overtime_day_salary'   => round($overtimeDaySalary, 2),
            'overtime_hour_salary'  => round($overtimeHourSalary, 2),
            'overtime_salary'       => round($totalOvertimeSalary, 2),
            'allowance'             => $allowance,
            'bonus'                 => $bonus,
            'deduction'             => $deduction,
            'late_penalty_fee'      => round($totalLatePenaltyFee, 2),
            'insurance'             => round($insurance, 2),
            'tax'                   => round($tax, 2),
            'total_salary'          => round($totalSalary, 2),
            'status'                => PayrollPaymentWorkflowService::CALCULATED,
        ];

        if ($existing) {
            $existing->fill($payload)->save();

            return $existing->fresh();
        }

        return Payroll::create(array_merge($payload, [
            'employee_id' => $employee->id,
            'month'       => $month,
            'year'        => $year,
        ]));
    }
}