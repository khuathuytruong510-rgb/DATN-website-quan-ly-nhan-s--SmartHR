<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\PayrollMail;
use App\Mail\PayrollConfirmationMail;
use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;


class AccountantController extends Controller
{
    public function dashboard(): View
    {
        $total = Payroll::count();
        $waitingReview = Payroll::whereIn('status', array_merge(
            PayrollPaymentWorkflowService::calculatedStatuses(),
            PayrollPaymentWorkflowService::hrCheckedStatuses()
        ))->count();
        $waitingPay = Payroll::whereIn('status', PayrollPaymentWorkflowService::payableStatuses())->count();
        $paid = Payroll::where('status', PayrollPaymentWorkflowService::PAID)->count();

        return view('accountant.dashboard', compact('total', 'waitingReview', 'waitingPay', 'paid'));
    }

    public function payrollIndex(Request $request): View
    {
        $query = Payroll::with('employee')->orderByDesc('month');

        if ($q = $request->input('q')) {
            $query->where(function($w) use ($q) {
                $w->where('month', 'like', "%{$q}%")
                  ->orWhereHas('employee', function($e) use ($q) {
                      $e->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $statusGroups = [
                'calculated' => PayrollPaymentWorkflowService::calculatedStatuses(),
                'hr_checked' => PayrollPaymentWorkflowService::hrCheckedStatuses(),
                'hr_approved' => PayrollPaymentWorkflowService::hrCheckedStatuses(),
                'director_approved' => PayrollPaymentWorkflowService::directorApprovedStatuses(),
                'employee_confirmed' => PayrollPaymentWorkflowService::payableStatuses(),
                'ready_for_payment' => PayrollPaymentWorkflowService::payableStatuses(),
            ];
            if (isset($statusGroups[$status])) {
                $query->whereIn('status', $statusGroups[$status]);
            } else {
                $query->where('status', $status);
            }
        }

        $payrolls = $query->paginate(15)->withQueryString();

        return view('accountant.payroll.index', [
            'payrolls' => $payrolls,
            'workflow' => app(PayrollPaymentWorkflowService::class),
        ]);
    }

    public function payrollShow(Payroll $payroll): View
    {
        $payroll->load('employee');
        return view('accountant.payroll.show', [
            'payroll' => $payroll,
            'workflow' => app(PayrollPaymentWorkflowService::class),
        ]);
    }

    public function sendPayrollEmail(Payroll $payroll)
    {
        $employee = $payroll->employee;

        if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('accountant.payroll.show', $payroll)
                ->with('error', 'Nhân viên chưa có email hợp lệ.');
        }

        $workflow = app(PayrollPaymentWorkflowService::class);
        if (! $workflow->isDirectorApproved($payroll->status) && ! $workflow->canPay($payroll)) {
            return redirect()->route('accountant.payroll.show', $payroll)
                ->with('error', 'Chỉ gửi email khi phiếu đã được Giám đốc phê duyệt (hoặc NV đã xác nhận, chờ thanh toán).');
        }

        try {
            Mail::to($employee->email)->send(new PayrollMail($payroll));

            $payroll->update([
                'sent_at' => now(),
                'sent_by' => Auth::id(),
                'email_status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            $payroll->update(['email_status' => 'failed']);
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'payroll_email_failed',
                'meta' => 'payroll:'.$payroll->id,
            ]);
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', 'Gửi email thất bại: ' . $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payroll_email_sent',
            'meta' => 'payroll:'.$payroll->id,
        ]);

        return redirect()->route('accountant.payroll.show', $payroll)->with('success', 'Đã gửi email đến ' . $employee->email);
    }

    public function recalculatePayroll(Payroll $payroll, PayrollCalculationService $service)
    {
        if (! in_array($payroll->status, PayrollPaymentWorkflowService::recalculableStatuses(), true)) {
            return redirect()->route('accountant.payroll.show', $payroll)
                ->with('error', 'Chỉ tính lại phiếu đang nháp, đã tính, hoặc đang sự cố. Phiếu HR đã kiểm tra / Giám đốc đã duyệt phải đi đúng vòng workflow.');
        }

        try {
            app(\App\Services\PayrollPeriodLockService::class)
                ->assertUnlockedForCalculation((int) $payroll->month, (int) ($payroll->year ?? now()->year));
        } catch (\Throwable $e) {
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', $e->getMessage());
        }

        $employee = $payroll->employee;
        if (! $employee) {
            return back()->with('error', 'Nhân viên không tồn tại');
        }

        $month = (int) $payroll->month;
        $year = (int) ($payroll->year ?? now()->year);

        try {
            $newPayroll = $service->calculate($employee, $month, $year);
        } catch (\Throwable $e) {
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payroll_recalculated',
            'meta' => 'payroll:'.$newPayroll->id.';status_before:'.$payroll->status,
        ]);

        return redirect()->route('accountant.payroll.show', $newPayroll)->with('success', 'Đã tính lại bảng lương');
    }

    public function lockPayroll(Payroll $payroll)
    {
        abort(403, 'Kế toán không khóa từng phiếu. HR chốt cả kỳ lương.');
    }

    public function unlockPayroll(Payroll $payroll)
    {
        abort(403, 'Kế toán không mở khóa phiếu. HR mở khóa kỳ (có lý do và nhật ký).');
    }

    public function sendAllPayrolls(Request $request)
    {
        $payrolls = Payroll::with('employee')
            ->whereIn('status', PayrollPaymentWorkflowService::directorApprovedStatuses())
            ->orderByDesc('month')
            ->get();
        $sent = 0; $failed = 0;

        foreach ($payrolls as $p) {
            if ($p->locked) continue;
            $employee = $p->employee;
            if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) { $failed++; continue; }

            $updateData = [
                'sent_at' => now(),
                'sent_by' => Auth::id(),
                'email_status' => 'sent',
                'confirmation_deadline' => now()->addDays(7),
            ];

            if ($p->confirmation_status !== 'confirmed') {
                $updateData['confirmation_status'] = 'pending';
            }

            $p->update($updateData);

            try {
                Mail::to($employee->email)->send(new PayrollConfirmationMail($p->fresh()));
                ActivityLog::create(['user_id' => Auth::id(), 'action' => 'send_payroll', 'meta' => 'payroll:' . $p->id]);
                $sent++;
            } catch (\Throwable $e) {
                $p->update(['email_status' => 'failed']);
                $failed++;
            }
        }

        return redirect()->route('accountant.payroll.index')->with('success', "Đã gửi {$sent} bảng lương. {$failed} thất bại.");
    }

    public function payrollGenerate(PayrollPeriodLockService $periodLock): View
    {
        $periods = collect();
        $cursor = Carbon::now()->startOfMonth();
        for ($i = 0; $i < 6; $i++) {
            $month = (int) $cursor->month;
            $year = (int) $cursor->year;
            $lock = $periodLock->find($month, $year);
            $statusCounts = Payroll::query()
                ->where('month', $month)
                ->where('year', $year)
                ->pluck('status')
                ->countBy();

            $periods->push([
                'month' => $month,
                'year' => $year,
                'label' => $cursor->format('m/Y'),
                'value' => $cursor->format('Y-m'),
                'locked' => (bool) $lock?->is_locked,
                'total' => $statusCounts->sum(),
                'calculated' => (int) $statusCounts->only(PayrollPaymentWorkflowService::calculatedStatuses())->sum(),
                'issue' => (int) ($statusCounts[PayrollPaymentWorkflowService::PAYROLL_ISSUE] ?? 0),
            ]);
            $cursor = $cursor->copy()->subMonth();
        }

        return view('accountant.payroll.generate', compact('periods'));
    }

    public function generatePayroll(Request $request, PayrollCalculationService $service)
    {
        $monthInput = $request->input('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', (string) $monthInput)) {
            return redirect()->route('accountant.payroll.generate')->with('error', 'Định dạng tháng không hợp lệ.');
        }

        [$year, $month] = explode('-', $monthInput);
        $year = (int) $year;
        $month = (int) $month;

        try {
            $result = $service->calculatePeriod($month, $year, $request->user());
        } catch (\Throwable $e) {
            return redirect()->route('accountant.payroll.generate')->with('error', $e->getMessage());
        }

        $msg = "Đã tính lương cho {$result['calculated']} nhân viên kỳ {$monthInput}.";
        if ($result['skipped'] > 0) {
            $msg .= " Bỏ qua {$result['skipped']} phiếu đã vào vòng duyệt (không tính lại).";
        }

        return redirect()->route('accountant.payroll.generate')->with('success', $msg);
    }

    public function payrollSend(): View
    {
        return view('accountant.payroll.send');
    }

    public function payrollFeedback(): View
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

        return view('accountant.payroll.feedback', [
            'issues' => $issues,
        ]);
    }

    public function leaveRequests(Request $request): View
    {
        $query = LeaveRequest::with('employee', 'approver')->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        return view('accountant.leave_requests', [
            'leaveRequests' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function createLeaveRequest()
    {
        abort(403, 'Kế toán chỉ xem nghỉ phép để tính lương. Tạo/duyệt đơn thuộc HR.');
    }

    public function storeLeaveRequest(Request $request)
    {
        abort(403, 'Kế toán chỉ xem nghỉ phép để tính lương. Tạo/duyệt đơn thuộc HR.');
    }

    public function approveLeaveRequest(LeaveRequest $leaveRequest)
    {
        abort(403, 'Kế toán không được duyệt nghỉ phép. Việc duyệt thuộc HR.');
    }

    public function rejectLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        abort(403, 'Kế toán không được từ chối nghỉ phép. Việc duyệt thuộc HR.');
    }

    public function allowances(): View
    {
        return view('accountant.allowances.index');
    }

    public function deductions(): View
    {
        return view('accountant.deductions.index');
    }

    public function bonuses(): View
    {
        return view('accountant.bonuses.index');
    }

    public function reports(): View
    {
        return view('accountant.reports.index');
    }

    public function export(): View
    {
        return view('accountant.export.index');
    }

    public function activityLogs(): View
    {
        $logs = ActivityLog::query()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('action', 'like', '%payroll%')
                    ->orWhere('action', 'like', '%salary%');
            })
            ->latest()
            ->paginate(20);

        return view('accountant.activity_logs.index', compact('logs'));
    }

    public function profile(): View
    {
        return view('accountant.profile');
    }

    public function showChangePassword(): View
    {
        return view('accountant.change_password');
    }
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();
        if (! \Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng']);
        }

        $user->password = \Hash::make($data['password']);
        $user->save();

        return redirect()->route('accountant.profile')->with('success', 'Đổi mật khẩu thành công');
    }
}

