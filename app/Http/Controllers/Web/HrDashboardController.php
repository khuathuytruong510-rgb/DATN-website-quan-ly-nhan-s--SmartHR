<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Recruitment;
use App\Models\SalaryAdvance;
use App\Models\SupportRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $this->resolveFilter($request);
        $start = $filter['start'];
        $end = $filter['end'];
        $periodLabel = $filter['label'];
        $isAll = $filter['is_all'] ?? false;

        $hrOverview = $this->getHrOverview($start, $end, $isAll);
        $departmentStats = $this->getDepartmentStats();
        $attendanceStats = $this->getAttendanceStats($start, $end, $isAll);
        $payrollStats = $this->getPayrollStats($start, $end, $isAll);
        $contractStats = $this->getContractStats();
        $recruitmentStats = $this->getRecruitmentStats();
        $requestStats = $this->getRequestStats($start, $end, $isAll);
        $accountStats = $this->getAccountStats();
        $rewardStats = $this->getRewardStats();
        $monthlyPayrollTrend = $this->getMonthlyPayrollTrend();
        $monthlyNewEmployees = $this->getMonthlyNewEmployees();

        return view('statistics.hr-dashboard', compact(
            'hrOverview', 'departmentStats', 'attendanceStats', 'payrollStats',
            'contractStats', 'recruitmentStats', 'requestStats', 'accountStats',
            'rewardStats', 'monthlyPayrollTrend', 'monthlyNewEmployees',
            'periodLabel', 'start', 'end', 'isAll'
        ));
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'excel');
        $filter = $this->resolveFilter($request);
        $start = $filter['start'];
        $end = $filter['end'];
        $periodLabel = $filter['label'];
        $isAll = $filter['is_all'] ?? false;

        $hrOverview = $this->getHrOverview($start, $end, $isAll);
        $departmentStats = $this->getDepartmentStats();
        $attendanceStats = $this->getAttendanceStats($start, $end, $isAll);
        $payrollStats = $this->getPayrollStats($start, $end, $isAll);
        $contractStats = $this->getContractStats();
        $requestStats = $this->getRequestStats($start, $end, $isAll);
        $accountStats = $this->getAccountStats();

        if ($format === 'pdf') {
            return $this->exportPdf($hrOverview, $departmentStats, $attendanceStats, $payrollStats, $contractStats, $requestStats, $accountStats, $periodLabel, $start, $end, $isAll);
        }

        return $this->exportExcel($hrOverview, $departmentStats, $attendanceStats, $payrollStats, $contractStats, $requestStats, $accountStats, $periodLabel, $start, $end, $isAll);
    }

    private function resolveFilter(Request $request): array
    {
        $period = $request->input('period', 'all');
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Hôm nay ' . $now->format('d/m/Y'),
                'is_all' => false,
            ],
            '7days' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '7 ngày gần đây',
                'is_all' => false,
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'Tháng ' . $now->format('m/Y'),
                'is_all' => false,
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
                'label' => 'Quý ' . $now->quarter . '/' . $now->year,
                'is_all' => false,
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'Năm ' . $now->year,
                'is_all' => false,
            ],
            'custom' => [
                'start' => Carbon::parse($request->input('date_from', $now->copy()->startOfMonth())),
                'end' => Carbon::parse($request->input('date_to', $now->copy()->endOfMonth())),
                'label' => 'Tùy chọn',
                'is_all' => false,
            ],
            default => [
                'start' => Carbon::parse('2000-01-01'),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Toàn bộ hệ thống',
                'is_all' => true,
            ],
        };
    }

    // ========== 1. TỔNG QUAN NHÂN SỰ ==========
    private function getHrOverview(Carbon $start, Carbon $end, bool $isAll): array
    {
        $totalEmployees = Employee::count();
        $totalDepartments = Department::count();
        $totalPositions = Position::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();

        $probationEmployees = Employee::whereHas('contract', fn($q) => $q->where('contract_type', 'probation'))->count();
        $internEmployees = Employee::whereHas('contract', fn($q) => $q->where('contract_type', 'intern'))->count();

        $newThisMonth = $isAll
            ? Employee::count()
            : Employee::where('start_date', '>=', $start)->where('start_date', '<=', $end)->count();

        return compact(
            'totalEmployees', 'totalDepartments', 'totalPositions',
            'activeEmployees', 'inactiveEmployees',
            'probationEmployees', 'internEmployees', 'newThisMonth'
        );
    }

    // ========== 2. THỐNG KÊ PHÒNG BAN ==========
    private function getDepartmentStats(): array
    {
        $departments = Department::withCount(['employees' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        $totalActive = $departments->sum('employees_count');
        $maxDept = $departments->sortByDesc('employees_count')->first();
        $minDept = $departments->where('employees_count', '>', 0)->sortBy('employees_count')->first();

        $departmentData = $departments->map(function ($dept) use ($totalActive) {
            return [
                'name' => $dept->name,
                'count' => $dept->employees_count,
                'percentage' => $totalActive > 0 ? round(($dept->employees_count / $totalActive) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();

        return [
            'departments' => $departmentData,
            'totalActive' => $totalActive,
            'maxDepartment' => $maxDept?->name ?? 'N/A',
            'maxCount' => $maxDept?->employees_count ?? 0,
            'minDepartment' => $minDept?->name ?? 'N/A',
            'minCount' => $minDept?->employees_count ?? 0,
        ];
    }

    // ========== 3. THỐNG KÊ CHẤM CÔNG ==========
    private function getAttendanceStats(Carbon $start, Carbon $end, bool $isAll): array
    {
        $attendances = $isAll
            ? Attendance::query()
            : Attendance::whereBetween('date', [$start, $end]);

        $totalWorkDays = (clone $attendances)->count();
        $presentDays = (clone $attendances)->whereNotIn('status', ['absent'])->count();
        $absentDays = (clone $attendances)->where('status', 'absent')->count();

        $totalLate = (clone $attendances)->where('late_minutes', '>', 0)->count();
        $totalEarlyLeave = (clone $attendances)->where('early_leave_minutes', '>', 0)->count();
        $totalOvertimeHours = (clone $attendances)->sum('overtime_hours');
        $totalLateMinutes = (clone $attendances)->sum('late_minutes');
        $totalEarlyMinutes = (clone $attendances)->sum('early_leave_minutes');

        $leaveQuery = $isAll ? LeaveRequest::query() : LeaveRequest::whereBetween('start_date', [$start, $end]);
        $paidLeaves = (clone $leaveQuery)->where('status', 'approved')->sum('days');
        $unpaidLeaves = (clone $leaveQuery)->where('status', 'pending')->sum('days');

        return compact(
            'totalWorkDays', 'presentDays', 'absentDays',
            'totalLate', 'totalEarlyLeave', 'totalOvertimeHours',
            'paidLeaves', 'unpaidLeaves',
            'totalLateMinutes', 'totalEarlyMinutes'
        );
    }

    // ========== 4. THỐNG KÊ LƯƠNG ==========
    private function getPayrollStats(Carbon $start, Carbon $end, bool $isAll): array
    {
        $payrolls = $isAll ? Payroll::query() : Payroll::query()
            ->where('month', (int) $start->format('m'))
            ->where('year', (int) $start->format('Y'));

        $totalFund = (clone $payrolls)->sum('total_salary');
        $avgSalary = (clone $payrolls)->avg('total_salary');
        $maxSalary = (clone $payrolls)->max('total_salary');
        $minSalary = (clone $payrolls)->min('total_salary');
        $totalAllowance = (clone $payrolls)->sum('allowance');
        $totalDeduction = (clone $payrolls)->sum('deduction');
        $totalInsurance = (clone $payrolls)->sum('insurance');
        $totalTax = (clone $payrolls)->sum('tax');
        $totalBonus = (clone $payrolls)->sum('bonus');
        $totalNet = (clone $payrolls)->sum('total_salary');
        $totalLatePenalty = (clone $payrolls)->sum('late_penalty_fee');

        $deptQuery = Payroll::select(
            'departments.name as department_name',
            DB::raw('SUM(total_salary) as total_net'),
            DB::raw('SUM(allowance) as total_allowance'),
            DB::raw('SUM(bonus) as total_bonus'),
            DB::raw('SUM(insurance) as total_insurance'),
            DB::raw('SUM(tax) as total_tax'),
            DB::raw('SUM(overtime_salary) as total_overtime'),
            DB::raw('COUNT(DISTINCT payrolls.employee_id) as emp_count')
        )
        ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
        ->join('departments', 'departments.id', '=', 'employees.department_id');

        if (!$isAll) {
            $deptQuery->where('payrolls.month', (int) $start->format('m'))
                ->where('payrolls.year', (int) $start->format('Y'));
        }

        $departmentPayroll = $deptQuery
            ->groupBy('departments.name')
            ->orderByDesc('total_net')
            ->get();

        return compact(
            'totalFund', 'avgSalary', 'maxSalary', 'minSalary',
            'totalAllowance', 'totalDeduction', 'totalInsurance', 'totalTax',
            'totalBonus', 'totalNet', 'totalLatePenalty', 'departmentPayroll'
        );
    }

    // ========== 5. THỐNG KÊ HỢP ĐỒNG ==========
    private function getContractStats(): array
    {
        $total = Contract::count();
        $active = Contract::where('status', 'active')->count();
        $expiringSoon = Contract::where('status', 'active')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->count();
        $expired = Contract::where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'active')->where('end_date', '<', now());
            })->count();

        $byType = Contract::select('contract_type', DB::raw('count(*) as count'))
            ->groupBy('contract_type')
            ->pluck('count', 'contract_type');

        return compact('total', 'active', 'expiringSoon', 'expired', 'byType');
    }

    // ========== 6. THỐNG KÊ TUYỂN DỤNG ==========
    private function getRecruitmentStats(): array
    {
        try {
            $openPositions = Recruitment::where('status', 'open')->count();
            $totalApplications = Recruitment::count();
            $hired = Recruitment::where('status', 'hired')->count();
            $rejected = Recruitment::where('status', 'rejected')->count();
        } catch (\Exception $e) {
            $openPositions = 0;
            $totalApplications = 0;
            $hired = 0;
            $rejected = 0;
        }

        return compact('openPositions', 'totalApplications', 'hired', 'rejected');
    }

    // ========== 7. THỐNG KÊ ĐƠN TỪ ==========
    private function getRequestStats(Carbon $start, Carbon $end, bool $isAll): array
    {
        if ($isAll) {
            $totalLeave = LeaveRequest::count();
            $totalOvertime = OvertimeRequest::count();
            $totalAdvance = SalaryAdvance::count();
            $totalSupport = SupportRequest::count();

            $pendingAll = LeaveRequest::where('status', 'pending')->count()
                + OvertimeRequest::where('status', 'pending')->count()
                + SalaryAdvance::where('status', 'pending')->count()
                + SupportRequest::where('status', 'pending')->count();

            $approvedAll = LeaveRequest::where('status', 'approved')->count()
                + OvertimeRequest::where('status', 'approved')->count()
                + SalaryAdvance::where('status', 'approved')->count()
                + SupportRequest::where('status', 'approved')->count();

            $rejectedAll = LeaveRequest::where('status', 'rejected')->count()
                + OvertimeRequest::where('status', 'rejected')->count()
                + SalaryAdvance::where('status', 'rejected')->count()
                + SupportRequest::where('status', 'rejected')->count();
        } else {
            $totalLeave = LeaveRequest::whereBetween('created_at', [$start, $end])->count();
            $totalOvertime = OvertimeRequest::whereBetween('created_at', [$start, $end])->count();
            $totalAdvance = SalaryAdvance::whereBetween('created_at', [$start, $end])->count();
            $totalSupport = SupportRequest::whereBetween('created_at', [$start, $end])->count();

            $pendingAll = LeaveRequest::where('status', 'pending')->whereBetween('created_at', [$start, $end])->count()
                + OvertimeRequest::where('status', 'pending')->whereBetween('created_at', [$start, $end])->count()
                + SalaryAdvance::where('status', 'pending')->whereBetween('created_at', [$start, $end])->count()
                + SupportRequest::where('status', 'pending')->whereBetween('created_at', [$start, $end])->count();

            $approvedAll = LeaveRequest::where('status', 'approved')->whereBetween('created_at', [$start, $end])->count()
                + OvertimeRequest::where('status', 'approved')->whereBetween('created_at', [$start, $end])->count()
                + SalaryAdvance::where('status', 'approved')->whereBetween('created_at', [$start, $end])->count()
                + SupportRequest::where('status', 'approved')->whereBetween('created_at', [$start, $end])->count();

            $rejectedAll = LeaveRequest::where('status', 'rejected')->whereBetween('created_at', [$start, $end])->count()
                + OvertimeRequest::where('status', 'rejected')->whereBetween('created_at', [$start, $end])->count()
                + SalaryAdvance::where('status', 'rejected')->whereBetween('created_at', [$start, $end])->count()
                + SupportRequest::where('status', 'rejected')->whereBetween('created_at', [$start, $end])->count();
        }

        return compact(
            'totalLeave', 'totalOvertime', 'totalAdvance', 'totalSupport',
            'pendingAll', 'approvedAll', 'rejectedAll'
        );
    }

    // ========== 8. THỐNG KÊ TÀI KHOẢN ==========
    private function getAccountStats(): array
    {
        $total = User::count();
        $admin = User::where('is_admin', true)->count();
        $hr = User::where('is_hr', true)->count();
        $accountant = User::where('is_accountant', true)->count();
        $employee = User::where('is_admin', false)->where('is_hr', false)->where('is_accountant', false)->count();
        $locked = User::where('is_locked', true)->count();
        $active = User::where('is_locked', false)->count();

        return compact('total', 'admin', 'hr', 'accountant', 'employee', 'locked', 'active');
    }

    // ========== 9. THỐNG KÊ KHEN THƯỞNG ==========
    private function getRewardStats(): array
    {
        return [
            'totalRewards' => 0,
            'totalDiscipline' => 0,
            'topRewarded' => 'N/A',
        ];
    }

    // ========== BIỂU ĐỒ XU HƯỚNG ==========
    private function getMonthlyPayrollTrend(): array
    {
        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $m = (int) $date->format('m');
            $y = (int) $date->format('Y');

            $stats = Payroll::where('month', $m)->where('year', $y)
                ->selectRaw('COALESCE(SUM(total_salary), 0) as total_net, COUNT(*) as cnt')
                ->first();

            $trend[] = [
                'label' => $date->format('m/Y'),
                'total' => (float) $stats->total_net,
                'count' => (int) $stats->cnt,
            ];
        }

        return $trend;
    }

    private function getMonthlyNewEmployees(): array
    {
        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $s = $date->copy()->startOfMonth();
            $e = $date->copy()->endOfMonth();

            $count = Employee::where('start_date', '>=', $s)
                ->where('start_date', '<=', $e)
                ->count();

            $trend[] = [
                'label' => $date->format('m/Y'),
                'count' => $count,
            ];
        }

        return $trend;
    }

    // ========== XUẤT EXCEL ==========
    private function exportExcel(array $hrOverview, array $departmentStats, array $attendanceStats, array $payrollStats, array $contractStats, array $requestStats, array $accountStats, string $periodLabel, Carbon $start, Carbon $end, bool $isAll): \Symfony\Component\HttpFoundation\Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= "BÁO CÁO THỐNG KÊ NHÂN SỰ TOÀN HỆ THỐNG - {$periodLabel}\n";
        $csv .= "Ngày xuất: " . now()->format('d/m/Y H:i') . "\n\n";

        $csv .= "1. TỔNG QUAN NHÂN SỰ\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Tổng số nhân viên;{$hrOverview['totalEmployees']}\n";
        $csv .= "Tổng số phòng ban;{$hrOverview['totalDepartments']}\n";
        $csv .= "Tổng số chức vụ;{$hrOverview['totalPositions']}\n";
        $csv .= "Nhân viên đang làm việc;{$hrOverview['activeEmployees']}\n";
        $csv .= "Nhân viên nghỉ việc;{$hrOverview['inactiveEmployees']}\n";
        $csv .= "Nhân viên thử việc;{$hrOverview['probationEmployees']}\n";
        $csv .= "Nhân viên thực tập;{$hrOverview['internEmployees']}\n\n";

        $csv .= "2. THỐNG KÊ PHÒNG BAN\n";
        $csv .= "Phòng ban;Số nhân viên;Tỷ lệ (%)\n";
        foreach ($departmentStats['departments'] as $dept) {
            $csv .= "{$dept['name']};{$dept['count']};{$dept['percentage']}\n";
        }
        $csv .= "Phòng ban đông nhất;{$departmentStats['maxDepartment']};{$departmentStats['maxCount']}\n";
        $csv .= "Phòng ban ít nhất;{$departmentStats['minDepartment']};{$departmentStats['minCount']}\n\n";

        $csv .= "3. THỐNG KÊ CHẤM CÔNG\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Tổng số lượt chấm công;{$attendanceStats['totalWorkDays']}\n";
        $csv .= "Đi làm;{$attendanceStats['presentDays']}\n";
        $csv .= "Vắng mặt;{$attendanceStats['absentDays']}\n";
        $csv .= "Nghỉ phép;{$attendanceStats['paidLeaves']}\n";
        $csv .= "Nghỉ chờ duyệt;{$attendanceStats['unpaidLeaves']}\n";
        $csv .= "Lần đi muộn;{$attendanceStats['totalLate']}\n";
        $csv .= "Lần về sớm;{$attendanceStats['totalEarlyLeave']}\n";
        $csv .= "Tổng giờ làm thêm;{$attendanceStats['totalOvertimeHours']}\n\n";

        $csv .= "4. THỐNG KÊ LƯƠNG\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Tổng quỹ lương;" . number_format($payrollStats['totalFund'], 0, ',', '.') . "đ\n";
        $csv .= "Lương trung bình;" . number_format($payrollStats['avgSalary'], 0, ',', '.') . "đ\n";
        $csv .= "Lương cao nhất;" . number_format($payrollStats['maxSalary'], 0, ',', '.') . "đ\n";
        $csv .= "Lương thấp nhất;" . number_format($payrollStats['minSalary'], 0, ',', '.') . "đ\n";
        $csv .= "Tổng phụ cấp;" . number_format($payrollStats['totalAllowance'], 0, ',', '.') . "đ\n";
        $csv .= "Tổng khấu trừ;" . number_format($payrollStats['totalDeduction'], 0, ',', '.') . "đ\n";
        $csv .= "Tổng thưởng;" . number_format($payrollStats['totalBonus'], 0, ',', '.') . "đ\n";
        $csv .= "Tổng tiền thực lĩnh;" . number_format($payrollStats['totalNet'], 0, ',', '.') . "đ\n\n";

        $csv .= "5. THỐNG KÊ HỢP ĐỒNG\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Tổng số hợp đồng;{$contractStats['total']}\n";
        $csv .= "Hợp đồng sắp hết hạn (30 ngày);{$contractStats['expiringSoon']}\n";
        $csv .= "Hợp đồng đã hết hạn;{$contractStats['expired']}\n";
        $csv .= "Hợp đồng đang hiệu lực;{$contractStats['active']}\n\n";

        $csv .= "6. THỐNG KÊ ĐƠN TỪ\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Đơn nghỉ phép;{$requestStats['totalLeave']}\n";
        $csv .= "Đơn tăng ca;{$requestStats['totalOvertime']}\n";
        $csv .= "Đơn ứng lương;{$requestStats['totalAdvance']}\n";
        $csv .= "Đơn hỗ trợ;{$requestStats['totalSupport']}\n";
        $csv .= "Đơn chờ duyệt;{$requestStats['pendingAll']}\n";
        $csv .= "Đơn đã duyệt;{$requestStats['approvedAll']}\n";
        $csv .= "Đơn bị từ chối;{$requestStats['rejectedAll']}\n\n";

        $csv .= "7. THỐNG KÊ TÀI KHOẢN\n";
        $csv .= "Chỉ số;Giá trị\n";
        $csv .= "Tổng số tài khoản;{$accountStats['total']}\n";
        $csv .= "Admin;{$accountStats['admin']}\n";
        $csv .= "HR;{$accountStats['hr']}\n";
        $csv .= "Kế toán;{$accountStats['accountant']}\n";
        $csv .= "Nhân viên;{$accountStats['employee']}\n";
        $csv .= "Tài khoản bị khóa;{$accountStats['locked']}\n";
        $csv .= "Tài khoản đang hoạt động;{$accountStats['active']}\n";

        $filename = "baocao_nhan-su_{$start->format('Ymd')}_{$end->format('Ymd')}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    // ========== XUẤT PDF ==========
    private function exportPdf(array $hrOverview, array $departmentStats, array $attendanceStats, array $payrollStats, array $contractStats, array $requestStats, array $accountStats, string $periodLabel, Carbon $start, Carbon $end, bool $isAll): \Symfony\Component\HttpFoundation\Response
    {
        $html = $this->buildPdfHtml($hrOverview, $departmentStats, $attendanceStats, $payrollStats, $contractStats, $requestStats, $accountStats, $periodLabel, $start, $end, $isAll);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="baocao_nhan-su.html"',
        ]);
    }

    private function buildPdfHtml(array $hrOverview, array $departmentStats, array $attendanceStats, array $payrollStats, array $contractStats, array $requestStats, array $accountStats, string $periodLabel, Carbon $start, Carbon $end, bool $isAll): string
    {
        $deptRows = '';
        foreach ($departmentStats['departments'] as $dept) {
            $deptRows .= "<tr><td>{$dept['name']}</td><td>{$dept['count']}</td><td>{$dept['percentage']}%</td></tr>";
        }

        return "<!DOCTYPE html>
<html lang='vi'>
<head>
<meta charset='utf-8'>
<title>Báo cáo nhân sự - {$periodLabel}</title>
<style>
body { font-family: Arial, sans-serif; padding: 30px; color: #333; }
h1 { text-align: center; font-size: 22px; border-bottom: 2px solid #000; padding-bottom: 10px; }
h2 { font-size: 16px; margin-top: 24px; background: #f0f0f0; padding: 6px 10px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; font-size: 13px; }
th { background: #f8f8f8; }
.period { text-align: center; color: #666; margin-bottom: 20px; }
@media print { body { padding: 0; } }
</style>
</head>
<body>
<h1>BÁO CÁO THỐNG KÊ NHÂN SỰ TOÀN HỆ THỐNG</h1>
<p class='period'>{$periodLabel} | Xuất ngày " . now()->format('d/m/Y H:i') . "</p>

<h2>1. Tổng quan nhân sự</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Tổng số nhân viên</td><td>{$hrOverview['totalEmployees']}</td></tr>
<tr><td>Tổng số phòng ban</td><td>{$hrOverview['totalDepartments']}</td></tr>
<tr><td>Tổng số chức vụ</td><td>{$hrOverview['totalPositions']}</td></tr>
<tr><td>Nhân viên đang làm việc</td><td>{$hrOverview['activeEmployees']}</td></tr>
<tr><td>Nhân viên nghỉ việc</td><td>{$hrOverview['inactiveEmployees']}</td></tr>
<tr><td>Nhân viên thử việc</td><td>{$hrOverview['probationEmployees']}</td></tr>
<tr><td>Nhân viên thực tập</td><td>{$hrOverview['internEmployees']}</td></tr>
</table>

<h2>2. Thống kê phòng ban</h2>
<table>
<tr><th>Phòng ban</th><th>Số nhân viên</th><th>Tỷ lệ</th></tr>
{$deptRows}
<tr><td><strong>Phòng ban đông nhất</strong></td><td colspan='2'>{$departmentStats['maxDepartment']} ({$departmentStats['maxCount']} NV)</td></tr>
<tr><td><strong>Phòng ban ít nhất</strong></td><td colspan='2'>{$departmentStats['minDepartment']} ({$departmentStats['minCount']} NV)</td></tr>
</table>

<h2>3. Thống kê chấm công</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Tổng lượt chấm công</td><td>{$attendanceStats['totalWorkDays']}</td></tr>
<tr><td>Đi làm</td><td>{$attendanceStats['presentDays']}</td></tr>
<tr><td>Vắng mặt</td><td>{$attendanceStats['absentDays']}</td></tr>
<tr><td>Nghỉ phép</td><td>{$attendanceStats['paidLeaves']}</td></tr>
<tr><td>Nghỉ chờ duyệt</td><td>{$attendanceStats['unpaidLeaves']}</td></tr>
<tr><td>Đi muộn</td><td>{$attendanceStats['totalLate']}</td></tr>
<tr><td>Về sớm</td><td>{$attendanceStats['totalEarlyLeave']}</td></tr>
<tr><td>Giờ làm thêm</td><td>{$attendanceStats['totalOvertimeHours']}</td></tr>
</table>

<h2>4. Thống kê lương</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Tổng quỹ lương</td><td>" . number_format($payrollStats['totalFund'], 0, ',', '.') . " đ</td></tr>
<tr><td>Lương trung bình</td><td>" . number_format($payrollStats['avgSalary'], 0, ',', '.') . " đ</td></tr>
<tr><td>Lương cao nhất</td><td>" . number_format($payrollStats['maxSalary'], 0, ',', '.') . " đ</td></tr>
<tr><td>Lương thấp nhất</td><td>" . number_format($payrollStats['minSalary'], 0, ',', '.') . " đ</td></tr>
<tr><td>Tổng phụ cấp</td><td>" . number_format($payrollStats['totalAllowance'], 0, ',', '.') . " đ</td></tr>
<tr><td>Tổng khấu trừ</td><td>" . number_format($payrollStats['totalDeduction'], 0, ',', '.') . " đ</td></tr>
<tr><td>Tổng thưởng</td><td>" . number_format($payrollStats['totalBonus'], 0, ',', '.') . " đ</td></tr>
<tr><td>Tổng thực lĩnh</td><td>" . number_format($payrollStats['totalNet'], 0, ',', '.') . " đ</td></tr>
</table>

<h2>5. Thống kê hợp đồng</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Tổng số hợp đồng</td><td>{$contractStats['total']}</td></tr>
<tr><td>Sắp hết hạn (30 ngày)</td><td>{$contractStats['expiringSoon']}</td></tr>
<tr><td>Đã hết hạn</td><td>{$contractStats['expired']}</td></tr>
<tr><td>Đang hiệu lực</td><td>{$contractStats['active']}</td></tr>
</table>

<h2>6. Thống kê đơn từ</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Đơn nghỉ phép</td><td>{$requestStats['totalLeave']}</td></tr>
<tr><td>Đơn tăng ca</td><td>{$requestStats['totalOvertime']}</td></tr>
<tr><td>Đơn ứng lương</td><td>{$requestStats['totalAdvance']}</td></tr>
<tr><td>Đơn hỗ trợ</td><td>{$requestStats['totalSupport']}</td></tr>
<tr><td>Chờ duyệt</td><td>{$requestStats['pendingAll']}</td></tr>
<tr><td>Đã duyệt</td><td>{$requestStats['approvedAll']}</td></tr>
<tr><td>Bị từ chối</td><td>{$requestStats['rejectedAll']}</td></tr>
</table>

<h2>7. Thống kê tài khoản</h2>
<table>
<tr><th>Chỉ số</th><th>Giá trị</th></tr>
<tr><td>Tổng số tài khoản</td><td>{$accountStats['total']}</td></tr>
<tr><td>Admin</td><td>{$accountStats['admin']}</td></tr>
<tr><td>HR</td><td>{$accountStats['hr']}</td></tr>
<tr><td>Kế toán</td><td>{$accountStats['accountant']}</td></tr>
<tr><td>Nhân viên</td><td>{$accountStats['employee']}</td></tr>
<tr><td>Bị khóa</td><td>{$accountStats['locked']}</td></tr>
<tr><td>Đang hoạt động</td><td>{$accountStats['active']}</td></tr>
</table>

</body></html>";
    }
}
