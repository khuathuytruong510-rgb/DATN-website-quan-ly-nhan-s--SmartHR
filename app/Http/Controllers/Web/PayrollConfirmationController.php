<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
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
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            abort(403, 'Tài khoản chưa gắn hồ sơ nhân viên.');
        }

        return $employee;
    }

    protected function authorizePayroll(Payroll $payroll): void
    {
        if ((int) $payroll->employee_id !== (int) $this->currentEmployee()->id) {
            abort(403, 'Bạn chỉ được thao tác phiếu lương của chính mình.');
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

        $data = $request->validate([
            'issue_types' => ['nullable', 'array'],
            'issue_types.*' => ['in:working_days,allowance,deduction,overtime,other'],
            'issue_report' => ['required', 'string', 'max:1000'],
        ]);

        $typeLabels = [
            'working_days' => 'Sai ngày công',
            'allowance' => 'Sai phụ cấp',
            'deduction' => 'Sai khấu trừ',
            'overtime' => 'Sai OT',
            'other' => 'Khác',
        ];
        $types = collect($data['issue_types'] ?? [])
            ->map(fn ($type) => $typeLabels[$type] ?? $type)
            ->implode(', ');
        $issue = trim(($types ? "Loại lỗi: {$types}\n" : '').$data['issue_report']);

        try {
            $this->workflow->reportIssue($payroll, $issue, $request->user());
        } catch (\Throwable $e) {
            return redirect()->route('me.payrolls')->with('error', $e->getMessage());
        }

        return redirect()->route('me.payrolls')->with('success', 'Đã gửi báo cáo sự cố đến HR / Kế toán.');
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
