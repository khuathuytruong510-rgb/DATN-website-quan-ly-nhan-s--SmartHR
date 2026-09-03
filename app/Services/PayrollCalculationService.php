<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    /**
     * Nhân viên được tính lương kỳ: đang làm / chờ nghỉ — không gồm Giám đốc.
     */
    public function payrollEligibleEmployees()
    {
        return Employee::query()
            ->withoutBoardAndDirector()
            ->whereIn('status', [Employee::STATUS_ACTIVE, Employee::STATUS_PENDING_TERMINATION])
            ->orderBy('id');
    }

    public function calculate(Employee $employee, int $month, int $year)
    {
        if (\App\Support\RequestApprover::isDirectorProfile($employee)) {
            throw new PayrollNotRecalculableException('Không tính lương cho Giám đốc.');
        }

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
     * Tính cả kỳ cho nhân viên đang làm (không gồm Giám đốc). Bỏ qua phiếu không được tính lại.
     */
    public function calculatePeriod(int $month, int $year, ?User $actor = null): array
    {
        app(PayrollPeriodLockService::class)->assertUnlockedForCalculation($month, $year);

        $calculated = 0;
        $skipped = 0;

        $employees = $this->payrollEligibleEmployees()->get();
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

    /**
     * Số liệu kỳ để kế toán đối chiếu trước khi ghi phiếu (không ghi DB).
     *
     * @return Collection<int, object>
     */
    public function previewPeriod(int $month, int $year): Collection
    {
        $employees = $this->payrollEligibleEmployees()
            ->with(['positionDetail', 'contracts'])
            ->reorder()
            ->orderBy('name')
            ->get();

        $existingByEmployee = Payroll::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function (Employee $employee) use ($month, $year, $existingByEmployee) {
            $existing = $existingByEmployee->get($employee->id);
            $canRecalculate = ! $existing
                || in_array($existing->status, PayrollPaymentWorkflowService::recalculableStatuses(), true);

            $amounts = ($existing && ! $canRecalculate)
                ? $this->amountsFromPayroll($existing)
                : $this->buildAmounts($employee, $month, $year);

            $contract = $this->activeContract($employee);

            return (object) array_merge($amounts, $this->workFacts($employee, $month, $year), [
                'employee' => $employee,
                'payroll' => $existing,
                'can_recalculate' => $canRecalculate,
                'status' => $existing?->status,
                'month' => $month,
                'year' => $year,
                'contract_type' => $contract?->contract_type,
                'contract_code' => $contract?->contract_code,
                'contract_status' => $contract?->status,
                'working_schedule' => $contract?->working_schedule,
            ]);
        });
    }

    protected function activeContract(Employee $employee): ?Contract
    {
        $employee->loadMissing('contracts');

        return $employee->contracts
            ->sortByDesc(fn (Contract $item) => optional($item->start_date)?->toDateString())
            ->first(fn (Contract $item) => $item->status === 'active')
            ?? $employee->contracts->sortByDesc(fn (Contract $item) => optional($item->start_date)?->toDateString())->first();
    }

    /**
     * Ngày công / giờ / tăng ca / đi muộn — không gồm số tiền.
     *
     * @return array<string, float|int>
     */
    protected function workFacts(Employee $employee, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        return [
            'work_hours' => round((float) $attendances->sum('work_hours'), 2),
            'late_days' => $attendances->filter(
                fn ($row) => (int) ($row->late_minutes ?? 0) > 0 || $row->status === 'late'
            )->count(),
            'late_minutes' => (int) $attendances->sum('late_minutes'),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'early_leave_minutes' => (int) $attendances->sum('early_leave_minutes'),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    protected function amountsFromPayroll(Payroll $payroll): array
    {
        return [
            'base_salary' => (float) $payroll->base_salary,
            'daily_salary' => (float) ($payroll->daily_salary ?: ((float) $payroll->base_salary / $this->standardWorkingDays())),
            'required_working_days' => (int) ($payroll->required_working_days ?: $this->standardWorkingDays()),
            'calendar_working_days' => $this->workingDaysInMonth(...$this->payrollMonthYear($payroll)),
            'working_days' => (float) $payroll->working_days,
            'paid_leave_days' => (float) ($payroll->paid_leave_days ?? 0),
            'unpaid_leave_days' => (float) ($payroll->unpaid_leave_days ?? 0),
            'paid_holiday_days' => (float) ($payroll->paid_holiday_days ?? 0),
            'paid_holiday_salary' => (float) ($payroll->paid_holiday_days ?? 0) * (float) ($payroll->daily_salary ?: ((float) $payroll->base_salary / $this->standardWorkingDays())),
            'holiday_work_salary' => (float) ($payroll->holiday_work_salary ?? 0),
            'weekly_rest_work_salary' => (float) ($payroll->weekly_rest_work_salary ?? 0),
            'working_salary' => (float) $payroll->working_salary,
            'overtime_days' => (float) ($payroll->overtime_days ?? 0),
            'overtime_hours' => (float) ($payroll->overtime_hours ?? 0),
            'overtime_day_salary' => (float) ($payroll->overtime_day_salary ?? 0),
            'overtime_hour_salary' => (float) ($payroll->overtime_hour_salary ?? 0),
            'overtime_salary' => (float) ($payroll->overtime_salary ?? 0),
            'allowance' => (float) $payroll->allowance,
            'bonus' => (float) ($payroll->bonus ?? 0),
            'deduction' => (float) ($payroll->deduction ?? 0),
            'late_penalty_fee' => (float) ($payroll->late_penalty_fee ?? 0),
            'insurance' => (float) $payroll->insurance,
            'tax' => (float) $payroll->tax,
            'total_salary' => (float) $payroll->total_salary,
        ];
    }

    /**
     * Tính lại số liệu phiếu, giữ nguyên trạng thái workflow.
     */
    public function rebuildStoredAmounts(Payroll $payroll): Payroll
    {
        $payroll->loadMissing(['employee.positionDetail', 'employee.contracts']);
        $amounts = $this->buildAmounts($payroll->employee, (int) $payroll->month, (int) $payroll->year);
        $payroll->fill($amounts)->save();

        return $payroll->fresh();
    }

    protected function persistCalculated(Employee $employee, int $month, int $year, ?Payroll $existing): Payroll
    {
        $payload = array_merge($this->buildAmounts($employee, $month, $year), [
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        if ($existing) {
            $existing->fill($payload)->save();

            return $existing->fresh();
        }

        return Payroll::create(array_merge($payload, [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
        ]));
    }

    /**
     * @return array<string, float|int>
     */
    public function buildAmounts(Employee $employee, int $month, int $year): array
    {
        $employee->loadMissing(['positionDetail', 'contracts']);

        $contract = $employee->contracts
            ->sortByDesc(fn (Contract $item) => optional($item->start_date)?->toDateString())
            ->first(fn (Contract $item) => $item->status === 'active')
            ?? $employee->contracts->sortByDesc(fn (Contract $item) => optional($item->start_date)?->toDateString())->first();

        $baseSalary = (float) (
            $contract?->base_salary
            ?: $contract?->salary
            ?: $employee->positionDetail?->base_salary
            ?: 7800000
        );
        $allowance = (float) ($contract?->allowance ?: $employee->positionDetail?->allowance ?: 500000);

        $standardWorkingDays = $this->standardWorkingDays();
        $calendarWorkingDays = $this->workingDaysInMonth($month, $year);
        $dailySalary = $baseSalary / $standardWorkingDays;
        $hourSalary = $dailySalary / $this->hoursPerDay();
        $holidayMap = $this->holidayCalendar()->mapForMonth($month, $year);
        $holidays = array_keys($holidayMap);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $approvedLeaveRequests = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($month, $year) {
                $query->whereMonth('start_date', $month)->whereYear('start_date', $year)
                    ->orWhereMonth('end_date', $month)->whereYear('end_date', $year);
            })
            ->get();

        $overtimeByDate = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('id')
            ->get()
            ->keyBy(fn (OvertimeRequest $row) => optional($row->date)?->toDateString());

        $paidLeaveDays = 0.0;
        $unpaidLeaveDays = 0.0;
        $countedLeaveDates = [];
        foreach ($approvedLeaveRequests as $leave) {
            $daysInMonth = $this->leaveWorkingDaysInMonth($leave, $month, $year, $holidays);
            foreach ($this->leaveDateKeysInMonth($leave, $month, $year, $holidays) as $dateKey) {
                $countedLeaveDates[$dateKey] = true;
            }
            if ($leave->isPaidLeave()) {
                $paidLeaveDays += $daysInMonth;
            } else {
                $unpaidLeaveDays += $daysInMonth;
            }
        }

        $actualWorkingDays = 0;
        $attendanceLeaveDates = [];
        $attendanceAbsentDates = [];
        $paidHolidayDays = 0.0;
        $holidayWorkSalary = 0.0;
        $weeklyRestWorkSalary = 0.0;
        $regularOvertimeHours = 0.0;
        $overtimeDays = 0;
        $accountedHolidayDates = [];

        foreach ($attendances as $attendance) {
            $dateKey = optional($attendance->date)?->toDateString();
            $day = $dateKey ? Carbon::parse($dateKey) : null;
            $isHoliday = $dateKey && isset($holidayMap[$dateKey]);
            $isWeeklyOff = $day && $this->isWeeklyOff($day);
            $worked = (bool) (($attendance->getRawOriginal('check_in') ?: $attendance->check_in)
                && ($attendance->getRawOriginal('check_out') ?: $attendance->check_out));
            $otHours = $this->payrollOvertimeHours($attendance, $overtimeByDate);

            if ($isWeeklyOff) {
                if ($worked) {
                    $weeklyRestWorkSalary += $dailySalary * $this->weeklyRestRate();
                    if ($otHours > 0) {
                        $weeklyRestWorkSalary += $otHours * $hourSalary * $this->weeklyRestRate();
                        $overtimeDays++;
                    }
                }
                continue;
            }

            if ($isHoliday) {
                $paidHolidayDays += 1;
                $accountedHolidayDates[$dateKey] = true;
                if ($worked) {
                    $holidayWorkSalary += $dailySalary * $this->holidayWorkRate();
                    if ($otHours > 0) {
                        $holidayWorkSalary += $otHours * $hourSalary * $this->holidayWorkRate();
                        $overtimeDays++;
                    }
                }
                continue;
            }

            if ($dateKey && isset($countedLeaveDates[$dateKey])) {
                continue;
            }
            if ($attendance->status === 'leave') {
                if ($dateKey) {
                    $attendanceLeaveDates[$dateKey] = true;
                }
                continue;
            }
            if ($attendance->status === 'absent') {
                if ($dateKey) {
                    $attendanceAbsentDates[$dateKey] = true;
                }
                continue;
            }
            if ($worked) {
                $actualWorkingDays++;
                if ($otHours > 0) {
                    $regularOvertimeHours += $otHours;
                    $overtimeDays++;
                }
            }
        }

        foreach ($this->holidayCalendar()->weekdayHolidayKeys($month, $year) as $dateKey) {
            if (! isset($accountedHolidayDates[$dateKey])) {
                $paidHolidayDays += 1;
                $accountedHolidayDates[$dateKey] = true;
            }
        }

        foreach (array_keys($attendanceLeaveDates) as $dateKey) {
            if (! isset($countedLeaveDates[$dateKey]) && ! isset($accountedHolidayDates[$dateKey])) {
                $paidLeaveDays += 1;
                $countedLeaveDates[$dateKey] = true;
            }
        }
        foreach (array_keys($attendanceAbsentDates) as $dateKey) {
            if (! isset($countedLeaveDates[$dateKey]) && ! isset($accountedHolidayDates[$dateKey])) {
                $unpaidLeaveDays += 1;
                $countedLeaveDates[$dateKey] = true;
            }
        }

        $accounted = $actualWorkingDays + $paidLeaveDays + $unpaidLeaveDays + $paidHolidayDays;
        if ($accounted < $calendarWorkingDays) {
            $unpaidLeaveDays += $calendarWorkingDays - $accounted;
        }

        $payableDays = $actualWorkingDays + $paidLeaveDays + $paidHolidayDays;
        $overtimeHours = $regularOvertimeHours;

        $actualWorkingSalary = $actualWorkingDays * $dailySalary;
        $paidLeaveSalary = $paidLeaveDays * $dailySalary;
        $paidHolidaySalary = $paidHolidayDays * $dailySalary;
        $workingSalary = $actualWorkingSalary + $paidLeaveSalary + $paidHolidaySalary;
        $overtimeDaySalary = $holidayWorkSalary + $weeklyRestWorkSalary;
        $overtimeHourSalary = $overtimeHours * $hourSalary * $this->overtimeHourRate();
        $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

        $bonus = $this->attendanceBonus($payableDays);
        $deduction = 0;
        $totalLatePenaltyFee = (float) $attendances->sum('late_penalty_fee');
        $insuranceBase = $baseSalary;
        $insurance = $insuranceBase * $this->insuranceEmployeeRate();
        $gross = $workingSalary + $totalOvertimeSalary + $allowance + $bonus;
        $familyDeduction = $this->familyDeduction($employee);
        $taxableIncome = max(0, $gross - $insurance - $familyDeduction);
        $tax = $this->calculateTax($taxableIncome);
        $totalSalary = $this->calculateNetSalary($gross, $insurance + $deduction + $totalLatePenaltyFee, $tax);

        return [
            'base_salary' => round($baseSalary, 2),
            'daily_salary' => round($dailySalary, 2),
            'required_working_days' => $standardWorkingDays,
            'calendar_working_days' => $calendarWorkingDays,
            'working_days' => $actualWorkingDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'paid_holiday_days' => $paidHolidayDays,
            'paid_holiday_salary' => round($paidHolidaySalary, 2),
            'holiday_work_salary' => round($holidayWorkSalary, 2),
            'weekly_rest_work_salary' => round($weeklyRestWorkSalary, 2),
            'working_salary' => round($workingSalary, 2),
            'overtime_days' => $overtimeDays,
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_day_salary' => round($overtimeDaySalary, 2),
            'overtime_hour_salary' => round($overtimeHourSalary, 2),
            'overtime_salary' => round($totalOvertimeSalary, 2),
            'allowance' => round($allowance, 2),
            'bonus' => round($bonus, 2),
            'deduction' => round($deduction, 2),
            'late_penalty_fee' => round($totalLatePenaltyFee, 2),
            'insurance' => round($insurance, 2),
            'tax' => round($tax, 2),
            'total_salary' => round($totalSalary, 2),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, OvertimeRequest>  $overtimeByDate
     */
    protected function payrollOvertimeHours(Attendance $attendance, Collection $overtimeByDate): float
    {
        $dateKey = optional($attendance->date)?->toDateString();
        if (! $dateKey) {
            return 0.0;
        }

        $request = $overtimeByDate->get($dateKey);
        if ($request instanceof OvertimeRequest) {
            return $request->payrollHours();
        }

        return (float) ($attendance->overtime_hours ?? 0);
    }

    public function standardWorkingDays(): int
    {
        return max(1, (int) config('payroll.standard_working_days', 26));
    }

    public function hoursPerDay(): float
    {
        return max(1, (float) config('payroll.hours_per_day', 8));
    }

    public function overtimeHourRate(): float
    {
        return max(0, (float) config('payroll.overtime_hour_rate', 1.5));
    }

    public function weeklyRestRate(): float
    {
        return max(0, (float) config('payroll.weekly_rest_rate', 2.0));
    }

    public function holidayWorkRate(): float
    {
        return max(0, (float) config('payroll.holiday_work_rate', 3.0));
    }

    protected function holidayCalendar(): VietnamHolidayCalendar
    {
        return app(VietnamHolidayCalendar::class);
    }

    public function insuranceEmployeeRate(): float
    {
        return max(0, (float) config('payroll.insurance_employee_rate', 0.105));
    }

    public function familyDeduction(?Employee $employee = null): float
    {
        $personal = max(0, (float) config('payroll.personal_deduction', 0));
        $perDependent = max(0, (float) config('payroll.dependent_deduction', 4400000));
        $dependents = (int) ($employee?->dependents ?? $employee?->number_of_dependents ?? 0);

        return $personal + ($dependents * $perDependent);
    }

    public function attendanceBonus(float $payableDays): float
    {
        $fullDays = (int) config('payroll.bonus.full_attendance_days', 22);
        $goodDays = (int) config('payroll.bonus.good_attendance_days', 18);

        if ($payableDays >= $fullDays) {
            return (float) config('payroll.bonus.full', 500000);
        }
        if ($payableDays >= $goodDays) {
            return (float) config('payroll.bonus.good', 300000);
        }

        return (float) config('payroll.bonus.base', 200000);
    }

    /**
     * Tách 3 tầng số liệu để hiển thị / bảo vệ: lương HĐ, lương theo công, thực nhận.
     *
     * @return array<string, float|int|string>
     */
    public function explain(Payroll $payroll): array
    {
        $standardDays = $this->standardWorkingDays();
        $dailySalary = (float) ($payroll->daily_salary ?: ((float) $payroll->base_salary / max(1, $standardDays)));
        [$month, $year] = $this->payrollMonthYear($payroll);
        $period = $this->periodMeta($month, $year);
        $workDays = (float) ($payroll->working_days ?? 0);
        $paidLeaveDays = (float) ($payroll->paid_leave_days ?? 0);
        $unpaidLeaveDays = (float) ($payroll->unpaid_leave_days ?? 0);
        $leavePay = $paidLeaveDays * $dailySalary;
        $paidHolidayDays = (float) ($payroll->paid_holiday_days ?? 0);
        $holidayPay = $paidHolidayDays * $dailySalary;
        $holidayWorkPay = (float) ($payroll->holiday_work_salary ?? 0);
        $weeklyRestPay = (float) ($payroll->weekly_rest_work_salary ?? 0);
        $storedWorking = (float) ($payroll->working_salary ?? 0);
        $workPay = $storedWorking > 0
            ? max(0, $storedWorking - $leavePay - $holidayPay)
            : $workDays * $dailySalary;
        $overtime = (float) ($payroll->overtime_salary ?? 0);
        $allowance = (float) ($payroll->allowance ?? 0);
        $bonus = (float) ($payroll->bonus ?? 0);
        $gross = $workPay + $leavePay + $holidayPay + $overtime + $allowance + $bonus;
        $insurance = (float) ($payroll->insurance ?? 0);
        $tax = (float) ($payroll->tax ?? 0);
        $deduction = (float) ($payroll->deduction ?? 0);
        $late = (float) ($payroll->late_penalty_fee ?? 0);
        $totalDeductions = $insurance + $tax + $deduction + $late;
        $insuranceRate = $this->insuranceEmployeeRate();
        $familyDeduction = $this->familyDeduction($payroll->employee);
        $taxableIncome = max(0, $gross - $insurance - $familyDeduction);

        return [
            'standard_days' => $standardDays,
            'period_start' => $period['start_label'],
            'period_end' => $period['end_label'],
            'period_range' => $period['range_label'],
            'days_in_period' => $period['days_in_period'],
            'weekend_days' => $period['weekend_days'],
            'holiday_days' => $period['holiday_days'],
            'holidays' => $period['holidays'],
            'calendar_days' => $period['calendar_working_days'],
            'full_attendance_formula' => $period['formula_label'],
            'work_days' => $workDays,
            'paid_holiday_days' => $paidHolidayDays,
            'holiday_pay' => $holidayPay,
            'holiday_work_pay' => $holidayWorkPay,
            'holiday_work_rate' => $this->holidayWorkRate(),
            'weekly_rest_pay' => $weeklyRestPay,
            'weekly_rest_rate' => $this->weeklyRestRate(),
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'payable_days' => $workDays + $paidLeaveDays + $paidHolidayDays,
            'daily_salary' => $dailySalary,
            'hour_salary' => $dailySalary / $this->hoursPerDay(),
            'overtime_hour_rate' => $this->overtimeHourRate(),
            'base_salary' => (float) ($payroll->base_salary ?? 0),
            'work_pay' => $workPay,
            'leave_pay' => $leavePay,
            'overtime_day_pay' => (float) ($payroll->overtime_day_salary ?? 0),
            'overtime_hour_pay' => (float) ($payroll->overtime_hour_salary ?? 0),
            'overtime' => $overtime,
            'allowance' => $allowance,
            'bonus' => $bonus,
            'gross' => $gross,
            'insurance_base' => (float) ($payroll->base_salary ?? 0),
            'insurance_rate' => $insuranceRate,
            'insurance' => $insurance,
            'family_deduction' => $familyDeduction,
            'taxable_income' => $taxableIncome,
            'tax' => $tax,
            'deduction' => $deduction,
            'late_penalty' => $late,
            'total_deductions' => $totalDeductions,
            'net' => (float) ($payroll->total_salary ?? max(0, $gross - $totalDeductions)),
        ];
    }

    /**
     * Kỳ lương = tháng dương lịch: ngày 1 → ngày cuối tháng.
     *
     * @return array{
     *     start: Carbon,
     *     end: Carbon,
     *     start_label: string,
     *     end_label: string,
     *     range_label: string,
     *     days_in_period: int,
     *     weekend_days: int,
     *     calendar_working_days: int,
     *     formula_label: string
     * }
     */
    public function periodMeta(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $daysInPeriod = (int) $start->daysInMonth;
        $weekendDays = $this->weekendDaysInMonth($month, $year);
        $holidayMap = $this->holidayCalendar()->mapForMonth($month, $year);
        $holidayDays = count($this->holidayCalendar()->weekdayHolidayKeys($month, $year));
        $calendarDays = max(1, $daysInPeriod - $weekendDays - $holidayDays);
        $holidayLabels = collect($holidayMap)
            ->filter(fn (array $row, string $date) => ! Carbon::parse($date)->isSunday())
            ->map(fn (array $row, string $date) => Carbon::parse($date)->format('d/m').' '.$row['name'])
            ->values()
            ->implode(', ');
        $formula = sprintf(
            '%d ngày trong kỳ − %d Chủ nhật − %d ngày lễ = %d ngày phải đi làm',
            $daysInPeriod,
            $weekendDays,
            $holidayDays,
            $calendarDays
        );
        if ($holidayDays > 0) {
            $formula .= sprintf(
                '. %d ngày lễ hưởng lương (%s) vẫn trả 100%% lương ngày — không tính vào cột nghỉ không lương.',
                $holidayDays,
                $holidayLabels
            );
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_label' => $start->format('d/m/Y'),
            'end_label' => $end->format('d/m/Y'),
            'range_label' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
            'days_in_period' => $daysInPeriod,
            'weekend_days' => $weekendDays,
            'holiday_days' => $holidayDays,
            'holidays' => $holidayMap,
            'calendar_working_days' => $calendarDays,
            'formula_label' => $formula,
        ];
    }

    /**
     * Ngày công nếu đi làm đủ = số ngày trong kỳ − số Chủ nhật.
     */
    public function workingDaysInMonth(int $month, int $year): int
    {
        return $this->periodMeta($month, $year)['calendar_working_days'];
    }

    public function weekendDaysInMonth(int $month, int $year): int
    {
        $cursor = Carbon::create($year, $month, 1);
        $end = $cursor->copy()->endOfMonth();
        $offDays = 0;
        for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
            if ($this->isWeeklyOff($day)) {
                $offDays++;
            }
        }

        return $offDays;
    }

    public function isWeeklyOff(Carbon $day): bool
    {
        $offWeekdays = config('payroll.off_weekdays', [Carbon::SUNDAY]);

        return in_array((int) $day->dayOfWeek, $offWeekdays, true);
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function payrollMonthYear(Payroll $payroll): array
    {
        $monthRaw = $payroll->getAttributes()['month'] ?? $payroll->month;
        if (is_string($monthRaw) && preg_match('/^(\d{4})-(\d{2})$/', $monthRaw, $parts)) {
            return [(int) $parts[2], (int) $parts[1]];
        }

        $year = $payroll->getAttributes()['year'] ?? $payroll->year;

        return [(int) $monthRaw, (int) $year];
    }

    /**
     * @return list<string>
     */
    protected function holidayDates(int $month, int $year): array
    {
        if (! Schema::hasTable('holidays')) {
            return [];
        }

        return DB::table('holidays')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    /**
     * @param  list<string>  $holidays
     */
    protected function isWorkingDay(Carbon $day, array $holidays): bool
    {
        return ! $this->isWeeklyOff($day) && ! in_array($day->toDateString(), $holidays, true);
    }

    /**
     * @param  list<string>  $holidays
     * @return list<string>
     */
    protected function leaveDateKeysInMonth(LeaveRequest $leave, int $month, int $year, array $holidays): array
    {
        $start = Carbon::parse($leave->start_date)->max(Carbon::create($year, $month, 1)->startOfDay());
        $end = Carbon::parse($leave->end_date)->min(Carbon::create($year, $month, 1)->endOfMonth());
        if ($start->gt($end)) {
            return [];
        }

        $keys = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($this->isWorkingDay($day, $holidays)) {
                $keys[] = $day->toDateString();
            }
        }

        return $keys;
    }

    /**
     * @param  list<string>  $holidays
     */
    protected function leaveWorkingDaysInMonth(LeaveRequest $leave, int $month, int $year, array $holidays): float
    {
        $keys = $this->leaveDateKeysInMonth($leave, $month, $year, $holidays);
        if ($leave->half_day) {
            return $keys === [] ? 0.0 : 0.5;
        }

        return (float) count($keys);
    }
}