<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Services\SalaryCalculationService;

class SalaryController extends Controller
{
    public function index()
    {
        $salaries = Salary::with('employee')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('hr.salary.index', compact('salaries'));
    }

    public function generate(
        SalaryCalculationService $salaryService
    ) {
        $month = now()->month;
        $year = now()->year;

        foreach (Employee::all() as $employee) {

            $salaryService->calculate(
                $employee,
                $month,
                $year
            );
        }

        return redirect()
            ->route('salary.index')
            ->with(
                'success',
                'Đã tính lương thành công'
            );
    }
}