<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollConfirmationController extends Controller
{
    protected function authorizePayroll(Payroll $payroll): void
    {
        $employee = Employee::where('email', auth()->user()->email)->firstOrFail();

        if ($payroll->employee_id !== $employee->id) {
            abort(403);
        }
    }

    public function confirm(Payroll $payroll): RedirectResponse
    {
        $this->authorizePayroll($payroll);

        if ($payroll->status === 'paid') {
            return redirect()->route('me.payrolls')->with('error', 'Phiếu lương đã được thanh toán và không thể xác nhận lại.');
        }

        if ($payroll->confirmation_deadline && now()->greaterThan($payroll->confirmation_deadline)) {
            return redirect()->route('me.payrolls')->with('error', 'Hạn xác nhận đã hết. Vui lòng liên hệ phòng nhân sự.');
        }

        $payroll->update([
            'confirmation_status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return redirect()->route('me.payrolls')->with('success', 'Đã xác nhận phiếu lương.');
    }

    public function reportIssue(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorizePayroll($payroll);

        if ($payroll->status === 'paid') {
            return redirect()->route('me.payrolls')->with('error', 'Phiếu lương đã thanh toán và không thể báo cáo sự cố.');
        }

        $data = $request->validate([
            'issue_report' => ['required', 'string', 'max:1000'],
        ]);

        $payroll->update([
            'issue_report' => $data['issue_report'],
            'issue_reported_at' => now(),
            'confirmation_status' => 'issue_reported',
        ]);

        return redirect()->route('me.payrolls')->with('success', 'Đã gửi báo cáo sự cố đến phòng nhân sự.');
    }
}
