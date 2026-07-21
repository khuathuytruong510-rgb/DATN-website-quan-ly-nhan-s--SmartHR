<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Thống kê tổng quan theo tháng/năm
     */
    public function getOverview(int $month, int $year): array
    {
        $payrolls = Payroll::where('month', $month)->where('year', $year);
        $payments = SalaryPayment::where('month', $month)->where('year', $year);

        $totalPayrolls = (clone $payrolls)->count();
        $totalEmployees = (clone $payrolls)->distinct('employee_id')->count('employee_id');
        $totalBaseSalary = (clone $payrolls)->sum('base_salary');
        $totalWorkingSalary = (clone $payrolls)->sum('working_salary');
        $totalOvertime = (clone $payrolls)->sum('overtime_salary');
        $totalAllowance = (clone $payrolls)->sum('allowance');
        $totalBonus = (clone $payrolls)->sum('bonus');
        $totalInsurance = (clone $payrolls)->sum('insurance');
        $totalTax = (clone $payrolls)->sum('tax');
        $totalDeduction = (clone $payrolls)->sum('deduction');
        $totalNetSalary = (clone $payrolls)->sum('total_salary');

        $paidCount = (clone $payments)->where('status', 'paid')->count();
        $pendingCount = (clone $payments)->where('status', 'pending')->count();
        $paidAmount = (clone $payments)->where('status', 'paid')->sum('net');
        $pendingAmount = (clone $payments)->where('status', 'pending')->sum('net');

        $bankCount = (clone $payments)->where('payment_method', 'bank_transfer')->count();
        $cashCount = (clone $payments)->where('payment_method', 'cash')->count();

        $reconciled = (clone $payments)->where('reconciliation_status', 'reconciled')->count();
        $discrepancy = (clone $payments)->where('reconciliation_status', 'discrepancy')->count();

        return compact(
            'totalPayrolls', 'totalEmployees',
            'totalBaseSalary', 'totalWorkingSalary', 'totalOvertime',
            'totalAllowance', 'totalBonus',
            'totalInsurance', 'totalTax', 'totalDeduction', 'totalNetSalary',
            'paidCount', 'pendingCount', 'paidAmount', 'pendingAmount',
            'bankCount', 'cashCount',
            'reconciled', 'discrepancy',
            'month', 'year'
        );
    }

    /**
     * Thống kê theo phòng ban
     */
    public function getByDepartment(int $month, int $year): \Illuminate\Support\Collection
    {
        return Payroll::select(
            'departments.id as department_id',
            'departments.name as department_name',
            DB::raw('SUM(total_salary) as total_net'),
            DB::raw('SUM(working_salary) as total_working'),
            DB::raw('SUM(overtime_salary) as total_overtime'),
            DB::raw('SUM(allowance) as total_allowance'),
            DB::raw('SUM(bonus) as total_bonus'),
            DB::raw('SUM(insurance) as total_insurance'),
            DB::raw('SUM(tax) as total_tax'),
            DB::raw('COUNT(DISTINCT payrolls.employee_id) as employee_count')
        )
        ->where('payrolls.month', $month)
        ->where('payrolls.year', $year)
        ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
        ->join('departments', 'departments.id', '=', 'employees.department_id')
        ->groupBy('departments.id', 'departments.name')
        ->orderByDesc('total_net')
        ->get();
    }

    /**
     * Xu hướng lương 12 tháng gần nhất
     */
    public function getTrend(int $months = 12): array
    {
        $trend = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $m = (int) $date->format('m');
            $y = (int) $date->format('Y');

            $stats = Payroll::where('month', $m)->where('year', $y)
                ->selectRaw('
                    COUNT(*) as total_payrolls,
                    COALESCE(SUM(total_salary), 0) as total_net,
                    COALESCE(SUM(working_salary), 0) as total_working,
                    COALESCE(SUM(overtime_salary), 0) as total_overtime,
                    COALESCE(SUM(insurance), 0) as total_insurance,
                    COALESCE(SUM(tax), 0) as total_tax
                ')
                ->first();

            $trend[] = [
                'label' => $date->format('m/Y'),
                'month' => $m,
                'year'  => $y,
                'total_payrolls' => (int) $stats->total_payrolls,
                'total_net'      => (float) $stats->total_net,
                'total_working'  => (float) $stats->total_working,
                'total_overtime' => (float) $stats->total_overtime,
                'total_insurance'=> (float) $stats->total_insurance,
                'total_tax'      => (float) $stats->total_tax,
            ];
        }

        return $trend;
    }

    /**
     * Top nhân viên lương cao nhất
     */
    public function getTopEarners(int $month, int $year, int $limit = 10): \Illuminate\Support\Collection
    {
        return Payroll::select('payrolls.*')
            ->with('employee.department')
            ->where('payrolls.month', $month)
            ->where('payrolls.year', $year)
            ->orderByDesc('total_salary')
            ->limit($limit)
            ->get();
    }

    /**
     * So sánh tháng này vs tháng trước
     */
    public function getComparison(int $month, int $year): array
    {
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;

        $current = Payroll::where('month', $month)->where('year', $year)
            ->selectRaw('
                COUNT(*) as count,
                COALESCE(AVG(total_salary), 0) as avg_salary,
                COALESCE(SUM(total_salary), 0) as total,
                COALESCE(SUM(overtime_salary), 0) as overtime,
                COALESCE(MAX(total_salary), 0) as max_salary,
                COALESCE(MIN(total_salary), 0) as min_salary
            ')->first();

        $previous = Payroll::where('month', $prevMonth)->where('year', $prevYear)
            ->selectRaw('
                COUNT(*) as count,
                COALESCE(AVG(total_salary), 0) as avg_salary,
                COALESCE(SUM(total_salary), 0) as total,
                COALESCE(SUM(overtime_salary), 0) as overtime,
                COALESCE(MAX(total_salary), 0) as max_salary,
                COALESCE(MIN(total_salary), 0) as min_salary
            ')->first();

        $currTotal = (float) $current->total;
        $prevTotal = (float) $previous->total;

        return [
            'current'  => $current,
            'previous' => $previous,
            'prev_month' => $prevMonth,
            'prev_year'  => $prevYear,
            'total_change'     => $prevTotal > 0 ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 1) : 0,
            'avg_change'       => (float) $previous->avg_salary > 0
                ? round((((float) $current->avg_salary - (float) $previous->avg_salary) / (float) $previous->avg_salary) * 100, 1)
                : 0,
            'employee_change'  => (int) $previous->count > 0
                ? round((((int) $current->count - (int) $previous->count) / (int) $previous->count) * 100, 1)
                : 0,
        ];
    }

    /**
     * Phân bố lương (salary distribution)
     */
    public function getSalaryDistribution(int $month, int $year): array
    {
        $brackets = [
            ['label' => '< 5 triệu', 'min' => 0, 'max' => 5000000],
            ['label' => '5-10 triệu', 'min' => 5000000, 'max' => 10000000],
            ['label' => '10-15 triệu', 'min' => 10000000, 'max' => 15000000],
            ['label' => '15-20 triệu', 'min' => 15000000, 'max' => 20000000],
            ['label' => '20-30 triệu', 'min' => 20000000, 'max' => 30000000],
            ['label' => '> 30 triệu', 'min' => 30000000, 'max' => PHP_INT_MAX],
        ];

        $result = [];
        foreach ($brackets as $bracket) {
            $count = Payroll::where('month', $month)
                ->where('year', $year)
                ->where('total_salary', '>=', $bracket['min'])
                ->where('total_salary', '<', $bracket['max'])
                ->count();

            $result[] = [
                'label' => $bracket['label'],
                'count' => $count,
            ];
        }

        return $result;
    }

    /**
     * Xuất báo cáo CSV
     */
    public function exportDepartmentReport(int $month, int $year): string
    {
        $departments = $this->getByDepartment($month, $year);

        $headers = [
            'Phòng ban', 'Số nhân viên', 'Tổng lương thực nhận',
            'Lương công', 'Tăng ca', 'Phụ cấp', 'Thưởng',
            'Bảo hiểm', 'Thuế', 'Lương TB'
        ];

        $csv = implode(';', $headers) . "\n";

        foreach ($departments as $dept) {
            $avg = $dept->employee_count > 0 ? round($dept->total_net / $dept->employee_count) : 0;
            $row = [
                $dept->department_name,
                $dept->employee_count,
                $dept->total_net,
                $dept->total_working,
                $dept->total_overtime,
                $dept->total_allowance,
                $dept->total_bonus,
                $dept->total_insurance,
                $dept->total_tax,
                $avg,
            ];
            $csv .= implode(';', $row) . "\n";
        }

        return $csv;
    }
}
