<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;

class PayrollService
{
    private const REQUIRED_WORKING_DAYS = 26;
    private const WORKING_HOURS_PER_DAY = 8;
    private const INSURANCE_RATE = 0.105;
    private const OVERTIME_MULTIPLIER = 1.5;

    private AttendanceCalculationService $attendanceCalculationService;

    public function __construct(?AttendanceCalculationService $attendanceCalculationService = null)
    {
        $this->attendanceCalculationService = $attendanceCalculationService ?? new AttendanceCalculationService();
    }

    public function calculate(Employee $employee, int $month, int $year): Payroll
    {
        $baseSalary = $this->getBaseSalary($employee->position);
        $dailySalary = round($baseSalary / self::REQUIRED_WORKING_DAYS, 0);
        $hourlySalary = round($dailySalary / self::WORKING_HOURS_PER_DAY, 0);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $attendanceMetrics = $attendances->map(function (Attendance $attendance) {
            return $this->attendanceCalculationService->calculateAttendanceMetrics($attendance);
        });

        $fullWorkDays = $attendanceMetrics->filter(function (array $metrics) {
            return $metrics['work_hours'] >= self::WORKING_HOURS_PER_DAY;
        })->count();

        $overtimeHours = round($attendanceMetrics->reduce(function ($carry, array $metrics) {
            return $carry + max(0, $metrics['work_hours'] - self::WORKING_HOURS_PER_DAY);
        }, 0.0), 2);

        $workingDays = min($fullWorkDays, self::REQUIRED_WORKING_DAYS);
        $overtimeDays = max(0, $fullWorkDays - self::REQUIRED_WORKING_DAYS);
        $workingSalary = round($dailySalary * $workingDays, 0);

        $overtimeDaySalary = round($overtimeDays * $dailySalary * self::OVERTIME_MULTIPLIER, 0);
        $overtimeHourSalary = round($overtimeHours * $hourlySalary * self::OVERTIME_MULTIPLIER, 0);
        $totalOvertimeSalary = $overtimeDaySalary + $overtimeHourSalary;

        $allowance = 500000;
        $bonus = $workingDays >= self::REQUIRED_WORKING_DAYS ? 500000 : 0;

        $totalIncome = round($workingSalary + $totalOvertimeSalary + $allowance + $bonus, 0);
        $insurance = round($baseSalary * self::INSURANCE_RATE, 0);
        $taxableIncome = max(0, round($totalIncome - $insurance, 0));
        $tax = round($this->calculateTax($taxableIncome), 0);
        $netSalary = $this->calculateNetSalary($totalIncome, $insurance, $tax);

        $monthKey = sprintf('%04d-%02d', $year, $month);

        $invalidMonthKeys = [
            (string) $month,
            ltrim((string) $month, '0'),
        ];

        Payroll::where('employee_id', $employee->id)
            ->where(function ($query) use ($monthKey, $invalidMonthKeys) {
                $query->where('month', $monthKey);

                foreach ($invalidMonthKeys as $invalidMonthKey) {
                    $query->orWhere('month', $invalidMonthKey);
                }
            })
            ->delete();

        return Payroll::create([
            'employee_id' => $employee->id,
            'month' => $monthKey,
            'year' => $year,
            'base_salary' => round($baseSalary, 0),
            'daily_salary' => round($dailySalary, 0),
            'required_working_days' => self::REQUIRED_WORKING_DAYS,
            'working_days' => $workingDays,
            'working_salary' => round($workingSalary, 0),
            'overtime_days' => $overtimeDays,
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_day_salary' => round($overtimeDaySalary, 0),
            'overtime_hour_salary' => round($overtimeHourSalary, 0),
            'overtime_salary' => round($totalOvertimeSalary, 0),
            'allowance' => round($allowance, 0),
            'bonus' => round($bonus, 0),
            'deduction' => 0,
            'insurance' => round($insurance, 0),
            'tax' => round($tax, 0),
            'total_salary' => round($netSalary, 0),
        ]);
    }

    public function calculateTax(float $taxableIncome): float
    {
        if ($taxableIncome <= 11000000) {
            return 0.0;
        }

        if ($taxableIncome <= 20000000) {
            return round(($taxableIncome - 11000000) * 0.05, 2);
        }

        if ($taxableIncome <= 32000000) {
            return round(450000 + (($taxableIncome - 20000000) * 0.10), 2);
        }

        if ($taxableIncome <= 52000000) {
            return round(1450000 + (($taxableIncome - 32000000) * 0.15), 2);
        }

        if ($taxableIncome <= 80000000) {
            return round(2950000 + (($taxableIncome - 52000000) * 0.20), 2);
        }

        return round(5950000 + (($taxableIncome - 80000000) * 0.25), 2);
    }

    public function normalizeOvertimeHours(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            $numericValue = (float) $value;
            if ($numericValue <= 0) {
                return 0.0;
            }

            if ($numericValue > 1000) {
                return round($numericValue / 3600, 2);
            }

            if ($numericValue > 100) {
                return round($numericValue / 60, 2);
            }

            return round($numericValue, 2);
        }

        if ($value instanceof Carbon) {
            $hours = $value->hour + ($value->minute / 60) + ($value->second / 3600);
            return round($hours, 2);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $trimmed)) {
                [$hours, $minutes, $seconds] = array_map('intval', explode(':', $trimmed));
                return round(($hours * 3600 + $minutes * 60 + $seconds) / 3600, 2);
            }

            if (preg_match('/^\d{1,2}:\d{2}$/', $trimmed)) {
                [$hours, $minutes] = array_map('intval', explode(':', $trimmed));
                return round(($hours * 60 + $minutes) / 60, 2);
            }

            return 0.0;
        }

        return 0.0;
    }

    public function calculateNetSalary(float $totalIncome, float $insurance, float $tax): float
    {
        $netSalary = $totalIncome - $insurance - $tax;

        return round(max(0, $netSalary), 2);
    }

    private function getBaseSalary(string $position): int
    {
        return match ($position) {
            'Giám Đốc' => 13000000,
            'Trưởng Phòng Nhân Sự' => 10400000,
            default => 7800000,
        };
    }
}
