<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService;
use App\Services\PayrollPeriodLockService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollPaymentWorkflowService $workflow,
        protected PayrollPeriodLockService $periodLock,
    ) {
    }

    public function index(Request $request)
    {
        $month = (int) ($request->input('month') ?: now()->month);
        $year = (int) ($request->input('year') ?: now()->year);
        $user = $request->user();
        $paymentFocus = $user && $user->is_accountant && ! $user->is_hr && ! $user->is_director;

        $query = Payroll::with('employee')
            ->where('month', $month)
            ->where('year', $year);

        if ($paymentFocus) {
            $query->whereIn('status', PayrollPaymentWorkflowService::payableStatuses());
        }

        $payrolls = $query->orderByDesc('id')->get();

        return view('hr.payroll.index', [
            'payrolls' => $payrolls,
            'month' => $month,
            'year' => $year,
            'workflow' => $this->workflow,
            'periodLock' => $this->periodLock->find((int) $month, (int) $year),
            'paymentFocus' => $paymentFocus,
        ]);
    }

    /**
     * Danh sách phiếu lương bị nhân viên báo sự cố.
     */
    public function issues()
    {
        $issues = Payroll::with('employee')
            ->where(function ($q) {
                $q->where('status', PayrollPaymentWorkflowService::PAYROLL_ISSUE)
                    ->orWhere('confirmation_status', 'issue_reported');
            })
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
        if (! request()->user()?->is_accountant) {
            abort(403, 'Chỉ kế toán được tính lương. HR kiểm tra dữ liệu sau khi kế toán đã tính.');
        }

        $month = (int) ($request->input('month') ?: now()->month);
        $year = (int) ($request->input('year') ?: now()->year);

        try {
            $result = $service->calculatePeriod($month, $year, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $target = $request->user()?->is_accountant
            ? redirect()->route('accountant.payroll.index')
            : redirect()->route('payroll.index', ['month' => $month, 'year' => $year]);

        $msg = "Đã tính {$result['calculated']} phiếu lương.";
        if ($result['skipped'] > 0) {
            $msg .= " Bỏ qua {$result['skipped']} phiếu đã vào vòng duyệt (không tính lại).";
        }

        return $target->with('success', $msg);
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
        if (! $user || ! $user->is_hr) {
            abort(403, 'Chỉ HR được nhập số khắc phục. Kế toán tính lại từ dữ liệu nguồn sau khi HR xử lý sự cố.');
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
     * Lưu khắc phục → tính lại (calculated), HR kiểm tra lại, Giám đốc duyệt lại.
     */
    public function fixIssueSave(Request $request, Payroll $payroll)
    {
        $user = $request->user();
        if (! $user || ! $user->is_hr) {
            abort(403, 'Chỉ HR được nhập số khắc phục. Kế toán tính lại từ dữ liệu nguồn sau khi HR xử lý sự cố.');
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

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', 'Đã khắc phục sự cố. Phiếu đã tính lại — chờ HR kiểm tra dữ liệu, rồi Giám đốc phê duyệt lại.');
    }

    public function review(Payroll $payroll)
    {
        $user = request()->user();
        if (! $user || ! $user->is_hr) {
            abort(403, 'Chỉ HR được kiểm tra dữ liệu bảng lương.');
        }

        try {
            $this->workflow->reviewByHr($payroll, $user);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'HR đã kiểm tra dữ liệu bảng lương. Đang chờ Giám đốc phê duyệt cuối.');
    }

    public function reviewAll(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->is_hr) {
            abort(403, 'Chỉ HR được kiểm tra dữ liệu bảng lương.');
        }

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $pending = Payroll::query()
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->whereIn('status', PayrollPaymentWorkflowService::calculatedStatuses())
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            return back()->with('error', 'Không có bảng lương nào đang chờ HR kiểm tra trong tháng này.');
        }

        $ok = 0;
        $failed = 0;
        foreach ($pending as $payroll) {
            try {
                $this->workflow->reviewByHr($payroll, $user);
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $msg = "HR đã kiểm tra {$ok} bảng lương. Đang chờ Giám đốc phê duyệt cuối.";
        if ($failed > 0) {
            $msg .= " {$failed} phiếu lỗi/bỏ qua.";
        }

        return redirect()
            ->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', $msg);
    }

    public function approve(Payroll $payroll)
    {
        $user = request()->user();
        if (! $this->workflow->actorCanFinalApprove($user, $payroll)) {
            abort(403, 'Chỉ Giám đốc được phê duyệt cuối bảng lương HR đã kiểm tra.');
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

        return back()->with('success', 'Đã phê duyệt cuối. Đang chờ nhân viên xác nhận.'.$mailNote);
    }

    /**
     * Giám đốc phê duyệt cuối toàn bộ phiếu HR đã kiểm tra.
     */
    public function approveAll(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->is_director) {
            abort(403, 'Chỉ Giám đốc được phê duyệt cuối bảng lương.');
        }

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $pending = Payroll::query()
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->whereIn('status', PayrollPaymentWorkflowService::hrCheckedStatuses())
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            return back()->with('error', 'Không có bảng lương nào đang chờ phê duyệt cuối trong tháng này.');
        }

        $ok = 0;
        $failed = 0;
        foreach ($pending as $payroll) {
            try {
                $this->workflow->approve($payroll, $user);
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $msg = "Đã phê duyệt cuối {$ok} bảng lương. Đang chờ nhân viên xác nhận.";
        if ($failed > 0) {
            $msg .= " {$failed} phiếu lỗi/bỏ qua.";
        }

        return redirect()
            ->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', $msg);
    }

    /**
     * Bản in bảng lương tháng — trình Giám đốc phê duyệt cuối.
     */
    public function printSheet(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);

        $payrolls = Payroll::with(['employee.department'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('id')
            ->get();

        $totals = [
            'base_salary' => $payrolls->sum('base_salary'),
            'working_salary' => $payrolls->sum('working_salary'),
            'overtime_salary' => $payrolls->sum('overtime_salary'),
            'allowance' => $payrolls->sum('allowance'),
            'bonus' => $payrolls->sum('bonus'),
            'insurance' => $payrolls->sum('insurance'),
            'tax' => $payrolls->sum('tax'),
            'total_salary' => $payrolls->sum('total_salary'),
        ];

        return view('hr.payroll.print', [
            'payrolls' => $payrolls,
            'month' => $month,
            'year' => $year,
            'totals' => $totals,
            'printedAt' => now(),
            'printedBy' => $request->user(),
            'workflow' => $this->workflow,
        ]);
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
        if (! request()->user()?->is_hr) {
            abort(403, 'Chỉ HR được xóa phiếu lương chưa vào vòng duyệt.');
        }

        if (! in_array($payroll->status, PayrollPaymentWorkflowService::recalculableStatuses(), true)) {
            return back()->with('error', 'Không xóa phiếu đã được HR kiểm tra, Giám đốc duyệt, NV xác nhận hoặc đã thanh toán.');
        }

        $payroll->delete();

        return back()->with('success', 'Đã xóa bảng lương.');
    }

    public function lockPeriod(Request $request)
    {
        $user = $request->user();
        if (! $user?->is_hr) {
            abort(403, 'Chỉ HR được chốt dữ liệu kỳ lương.');
        }

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        try {
            $this->periodLock->lock((int) $data['month'], (int) $data['year'], $user);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', sprintf('Đã chốt dữ liệu kỳ %02d/%d. Kế toán có thể tính lương. Không sửa chấm công/nghỉ phép của kỳ khi đang khóa.', $data['month'], $data['year']));
    }

    public function unlockPeriod(Request $request)
    {
        $user = $request->user();
        if (! $user?->is_hr) {
            abort(403, 'Chỉ HR được mở khóa kỳ lương.');
        }

        $request->merge([
            'unlock_reason' => trim((string) $request->input('unlock_reason')),
        ]);

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'unlock_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $this->periodLock->unlock((int) $data['month'], (int) $data['year'], $user, $data['unlock_reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', 'Đã mở khóa kỳ lương. Sau khi chỉnh dữ liệu, HR phải chốt lại trước khi Kế toán tính.');
    }
}
