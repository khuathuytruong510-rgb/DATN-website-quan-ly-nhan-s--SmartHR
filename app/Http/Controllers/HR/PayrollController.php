<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(protected PayrollPaymentWorkflowService $workflow)
    {
    }

    public function index(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $payrolls = Payroll::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('id')
            ->get();

        return view('hr.payroll.index', [
            'payrolls' => $payrolls,
            'month' => $month,
            'year' => $year,
            'workflow' => $this->workflow,
        ]);
    }

    /**
     * Danh sách phiếu lương bị nhân viên báo sự cố.
     */
    public function issues()
    {
        $issues = Payroll::with('employee')
            ->where('confirmation_status', 'issue_reported')
            ->whereNotNull('issue_report')
            ->orderByDesc('issue_reported_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('hr.payroll.issues', [
            'issues' => $issues,
            'workflow' => $this->workflow,
        ]);
    }

    public function generate(Request $request, PayrollCalculationService $service)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        foreach (Employee::all() as $employee) {
            $service->calculate($employee, (int) $month, (int) $year);
        }

        return redirect()
            ->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', 'Đã tính bảng lương thành công!');
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'salaryPayment', 'paidByUser']);

        return view('hr.payroll.show', [
            'payroll' => $payroll,
            'workflow' => $this->workflow,
        ]);
    }

    /**
     * Form khắc phục sự cố lương (sửa số liệu).
     */
    public function fixIssueForm(Payroll $payroll)
    {
        $user = request()->user();
        if (! $user || (! $user->is_admin && ! $user->is_hr && ! $user->is_accountant)) {
            abort(403);
        }

        if (! $this->workflow->canRemediateIssue($payroll)) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', 'Phiếu này không đang ở trạng thái báo sự cố.');
        }

        $payroll->load('employee');

        return view('hr.payroll.fix_issue', [
            'payroll' => $payroll,
            'workflow' => $this->workflow,
        ]);
    }

    /**
     * Lưu khắc phục → gửi mail + chờ NV xác nhận lại.
     */
    public function fixIssueSave(Request $request, Payroll $payroll)
    {
        $user = $request->user();
        if (! $user || (! $user->is_admin && ! $user->is_hr && ! $user->is_accountant)) {
            abort(403);
        }

        $data = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
            'working_salary' => ['required', 'numeric', 'min:0'],
            'overtime_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'insurance' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'fix_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->workflow->remediateIssue($payroll, $data, $user);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $payroll->refresh();
        $mailNote = match ($payroll->email_status) {
            'sent' => ' Đã gửi email xác nhận lại đến nhân viên.',
            'failed' => ' (Email gửi thất bại — kiểm tra cấu hình Gmail SMTP.)',
            default => '',
        };

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', 'Đã khắc phục sự cố. Phiếu quay về trạng thái chờ nhân viên xác nhận.'.$mailNote);
    }

    public function approve(Payroll $payroll)
    {
        $user = request()->user();
        if (! $user || (! $user->is_admin && ! $user->is_hr)) {
            abort(403, 'Chỉ Admin/HR được duyệt bảng lương.');
        }

        try {
            $this->workflow->approve($payroll, $user);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $payroll->refresh();
        $mailNote = match ($payroll->email_status) {
            'sent' => ' Đã gửi email xác nhận đến nhân viên.',
            'failed' => ' (Email gửi thất bại — kiểm tra cấu hình Gmail SMTP.)',
            default => '',
        };

        return back()->with('success', 'Đã duyệt. Đang chờ nhân viên xác nhận.'.$mailNote);
    }

    /**
     * Giữ route cũ — chuyển sang approve chuẩn
     */
    public function approveWithPayment(Payroll $payroll)
    {
        return $this->approve($payroll);
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Không thể xóa bảng lương đã thanh toán.');
        }

        $payroll->delete();

        return back()->with('success', 'Đã xóa bảng lương.');
    }
}
