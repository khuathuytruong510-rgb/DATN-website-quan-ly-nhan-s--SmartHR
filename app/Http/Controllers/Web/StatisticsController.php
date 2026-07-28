<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(protected StatisticsService $stats)
    {
    }

    public function index(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $overview    = $this->stats->getOverview($month, $year);
        $departments = $this->stats->getByDepartment($month, $year);
        $comparison  = $this->stats->getComparison($month, $year);
        $topEarners  = $this->stats->getTopEarners($month, $year, 10);
        $distribution= $this->stats->getSalaryDistribution($month, $year);

        return view('statistics.index', compact(
            'overview', 'departments', 'comparison', 'topEarners', 'distribution', 'month', 'year'
        ));
    }

    public function trend(Request $request)
    {
        $months = $request->integer('months', 12);
        $trend = $this->stats->getTrend($months);

        return view('statistics.trend', compact('trend', 'months'));
    }

    public function departmentReport(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $departments = $this->stats->getByDepartment($month, $year);

        return view('statistics.departments', compact('departments', 'month', 'year'));
    }

    public function exportDepartment(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $csv = $this->stats->exportDepartmentReport($month, $year);
        $filename = "baocao_phongban_{$month}_{$year}.csv";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
        ]);
    }

    /**
     * Xuất bảng thống kê lương tháng ra Excel (.xls mở được bằng Excel).
     */
    public function exportExcel(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));

        $html = $this->stats->exportSalaryStatisticsExcel($month, $year);
        $filename = sprintf('thong_ke_luong_%02d_%d.xls', $month, $year);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    public function apiTrend(Request $request)
    {
        $months = $request->integer('months', 12);
        $trend = $this->stats->getTrend($months);

        return response()->json($trend);
    }

    public function apiDistribution(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $distribution = $this->stats->getSalaryDistribution($month, $year);

        return response()->json($distribution);
    }
}
