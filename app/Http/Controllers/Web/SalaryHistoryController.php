<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalaryHistoryController extends Controller
{
    protected function authorizeView(SalaryHistory $salaryHistory): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->is_admin || $user->is_director || $user->is_hr || $user->is_accountant) {
            return;
        }

        $employee = $user->linkedEmployee();

        if (! $employee || (int) $salaryHistory->employee_id !== (int) $employee->id) {
            abort(403, 'Bạn chỉ được xem lịch sử lương của chính mình.');
        }
    }

    public function show($id): View
    {
        $salaryHistory = SalaryHistory::with(['employee.department', 'payroll', 'updatedBy'])->findOrFail($id);
        $this->authorizeView($salaryHistory);

        $old = (float) ($salaryHistory->old_salary ?? 0);
        $new = (float) ($salaryHistory->new_salary ?? 0);
        $difference = $new - $old;
        $percent = $old > 0 ? round(($difference / $old) * 100, 2) : null;

        $allowances = (array) ($salaryHistory->allowances ?? []);
        $allowanceTotal = 0;
        foreach ($allowances as $v) {
            $allowanceTotal += (float) $v;
        }

        $rewards = (float) ($salaryHistory->rewards ?? 0);
        $deductions = (float) ($salaryHistory->deductions ?? 0);
        $tax = (float) ($salaryHistory->tax ?? 0);
        $insurance = (float) ($salaryHistory->insurance ?? 0);

        // Với thanh toán lương: new_salary đã là thực nhận
        $isPayment = $salaryHistory->change_type === SalaryHistory::CHANGE_PAYMENT;
        $net = $isPayment
            ? $new
            : ($new + $allowanceTotal + $rewards - $deductions - $tax - $insurance);

            $latePenaltyFee = (float) (optional($salaryHistory->payroll)->late_penalty_fee ?? 0);

        return view('salary_histories.show', compact(
            'salaryHistory',
            'old',
            'new',
            'difference',
            'percent',
            'allowances',
            'allowanceTotal',
            'rewards',
            'deductions',
            'tax',
            'insurance',
            'net',
            'isPayment',
            'latePenaltyFee'
        ));
    }

    public function byPayroll(Payroll $payroll): View|RedirectResponse
    {
        $salaryHistory = SalaryHistory::where('payroll_id', $payroll->id)->first();

        if (! $salaryHistory && $payroll->status === 'paid') {
            $salaryHistory = SalaryHistory::recordFromPaidPayroll(
                $payroll->load(['employee', 'salaryPayment']),
                auth()->user()
            );
        }

        if (! $salaryHistory) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', 'Chưa có lịch sử lương cho phiếu này (chỉ tạo sau khi thanh toán).');
        }

        return $this->show($salaryHistory->id);
    }

    public function index(): View
    {
        $histories = SalaryHistory::with(['employee', 'updatedBy'])
            ->latest('effective_date')
            ->latest('id')
            ->paginate(12);

        return view('salary_histories.index', compact('histories'));
    }

    public function meIndex(): View
    {
        $user = auth()->user();
        $employee = null;

        if ($user) {
            $employee = $user->linkedEmployee();
        }

        $histories = collect();
        if ($employee) {
            $histories = SalaryHistory::with('payroll')
                ->where('employee_id', $employee->id)
                ->where('change_type', SalaryHistory::CHANGE_PAYMENT)
                ->latest('effective_date')
                ->latest('id')
                ->paginate(12);
        }

        return view('salary_histories.me_index', compact('histories'));
    }

    public function meShow(SalaryHistory $salaryHistory): View
    {
        return $this->show($salaryHistory->id);
    }
}
