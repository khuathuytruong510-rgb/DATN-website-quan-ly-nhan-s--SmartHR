<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalaryHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryHistoryController extends Controller
{
    /**
     * Show the salary history detail.
     * Eager-load relations to avoid N+1 queries.
     */
    public function show($id): View
    {
        $salaryHistory = SalaryHistory::with(['employee.department', 'payroll', 'updatedBy'])->findOrFail($id);

        // Compute derived values
        $old = (float) ($salaryHistory->old_salary ?? 0);
        $new = (float) ($salaryHistory->new_salary ?? 0);
        $difference = $new - $old;
        $percent = $old > 0 ? round(($difference / $old) * 100, 2) : null;

        // Allowances may be stored as array of named items
        $allowances = (array) ($salaryHistory->allowances ?? []);
        $allowanceTotal = 0;
        foreach ($allowances as $k => $v) {
            $allowanceTotal += (float) $v;
        }

        // Other totals
        $rewards = (float) ($salaryHistory->rewards ?? 0);
        $deductions = (float) ($salaryHistory->deductions ?? 0);
        $tax = (float) ($salaryHistory->tax ?? 0);
        $insurance = (float) ($salaryHistory->insurance ?? 0);

        // Net after adjustment
        $net = $new + $allowanceTotal + $rewards - $deductions - $tax - $insurance;

        return view('salary_histories.show', compact(
            'salaryHistory', 'old', 'new', 'difference', 'percent', 'allowances', 'allowanceTotal', 'rewards', 'deductions', 'tax', 'insurance', 'net'
        ));
    }

    /**
     * Find salary history record by payroll and show it.
     * Redirects to detail view or throws 404 if none.
     */
    public function byPayroll(\App\Models\Payroll $payroll)
    {
        $salaryHistory = SalaryHistory::where('payroll_id', $payroll->id)->firstOrFail();
        return $this->show($salaryHistory->id);
    }

    /**
     * List salary history records (paginated) for HR/Admin.
     */
    public function index()
    {
        $histories = SalaryHistory::with('employee')->latest()->paginate(12);
        return view('salary_histories.index', compact('histories'));
    }

    /**
     * Show current authenticated employee's salary history.
     */
    public function meIndex()
    {
        $user = auth()->user();
        $employee = $user ? \App\Models\Employee::where('user_id', $user->id)->first() : null;

        $histories = collect();
        if ($employee) {
            $histories = SalaryHistory::with('payroll')->where('employee_id', $employee->id)->latest()->paginate(12);
        }

        return view('salary_histories.me_index', compact('histories'));
    }
}
