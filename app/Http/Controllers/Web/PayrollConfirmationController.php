<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Payroll;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollConfirmationController extends Controller
{
    public function __construct(protected PayrollPaymentWorkflowService $workflow)
    {
    }

    protected function currentEmployee(): Employee
    {
        return Employee::where('email', auth()->user()->email)->firstOrFail();
    }

    protected function authorizePayroll(Payroll $payroll): void
    {
        if ($payroll->employee_id !== $this->currentEmployee()->id) {
            abort(403);
        }
    }

    public function confirm(Payroll $payroll): RedirectResponse
    {
        $this->authorizePayroll($payroll);

        try {
            $this->workflow->confirm($payroll, auth()->user());
        } catch (\Throwable $e) {
            return redirect()->route('me.payrolls')->with('error', $e->getMessage());
        }

        return redirect()->route('me.payrolls')->with('success', 'Đã xác nhận phiếu lương. Phiếu đủ điều kiện thanh toán.');
    }

    /**
     * Xác nhận qua link email (token)
     */
    public function confirmByToken(string $token): View|RedirectResponse
    {
        $payroll = Payroll::where('confirmation_token', $token)->first();

        if (! $payroll) {
            return view('payroll.email.confirm_result', [
                'ok' => false,
                'message' => 'Link xác nhận không hợp lệ hoặc đã được sử dụng.',
            ]);
        }

        try {
            $this->workflow->confirm($payroll, null, false);
        } catch (\Throwable $e) {
            return view('payroll.email.confirm_result', [
                'ok' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return view('payroll.email.confirm_result', [
            'ok' => true,
            'message' => 'Xác nhận bảng lương thành công. Cảm ơn bạn.',
            'payroll' => $payroll->fresh(),
        ]);
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

        $payroll->loadMissing('employee');

        $payroll->update([
            'issue_report' => $data['issue_report'],
            'issue_reported_at' => now(),
            'confirmation_status' => 'issue_reported',
        ]);

        $employeeName = optional($payroll->employee)->name ?? 'Nhân viên';
        $period = sprintf('%02d/%d', $payroll->month, $payroll->year);

        Notification::create([
            'sender_id' => auth()->id(),
            'target' => 'hr',
            'title' => "Báo sự cố lương — {$employeeName}",
            'message' => "Nhân viên {$employeeName} báo sự cố phiếu lương tháng {$period} (mã #{$payroll->id}):\n{$data['issue_report']}",
            'data' => [
                'payroll_id' => $payroll->id,
                'type' => 'payroll_issue',
            ],
        ]);

        return redirect()->route('me.payrolls')->with('success', 'Đã gửi báo cáo sự cố đến Admin / HR / Kế toán.');
    }

    public function requestBankChange(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        $data = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100', Rule::in(config('banks', []))],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
            'qr_image' => ['nullable', 'image', 'max:4096'],
        ]);

        if (
            blank($data['account_number'] ?? null)
            && blank($data['bank_name'] ?? null)
            && ! $request->hasFile('qr_image')
        ) {
            return back()->with('error', 'Vui lòng nhập ít nhất số tài khoản hoặc tải ảnh QR.');
        }

        try {
            $this->workflow->submitBankChangeRequest($employee, $data, $request->file('qr_image'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi yêu cầu thay đổi thông tin nhận lương. Chờ HR duyệt.');
    }
}
