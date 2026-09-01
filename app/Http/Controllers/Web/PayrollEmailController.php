<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\PayrollMail;
use App\Models\Payroll;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PayrollEmailController extends Controller
{
    public function __construct()
    {
        $this->middleware(\App\Http\Middleware\EnsureHrOrAdmin::class);
    }

    public function index(): View
    {
        $payrolls = Payroll::with('employee')
            ->whereIn('status', array_merge(
                PayrollPaymentWorkflowService::directorApprovedStatuses(),
                PayrollPaymentWorkflowService::payableStatuses(),
                [PayrollPaymentWorkflowService::PAID]
            ))
            ->orderByDesc('month')
            ->get();

        return view('payroll.email.index', compact('payrolls'));
    }

    public function send(Payroll $payroll): RedirectResponse
    {
        if (! $this->canNotify($payroll)) {
            return redirect()->route('payroll.email.index')
                ->with('error', 'Chỉ gửi email thông báo khi phiếu đã được Giám đốc duyệt (hoặc NV đã xác nhận / đã trả). Email không đổi trạng thái.');
        }

        $employee = $payroll->employee;

        if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('payroll.email.index')
                ->with('error', 'Không thể gửi email: nhân viên chưa có email hợp lệ.');
        }

        try {
            Mail::to($employee->email)->send(new PayrollMail($payroll));
            $payroll->update([
                'sent_at' => now(),
                'sent_by' => Auth::id(),
                'email_status' => 'sent',
            ]);
        } catch (\Throwable $exception) {
            $payroll->update(['email_status' => 'failed']);

            return redirect()->route('payroll.email.index')
                ->with('error', 'Gửi email thất bại: ' . $exception->getMessage());
        }

        return redirect()->route('payroll.email.index')
            ->with('success', 'Đã gửi phiếu lương đến ' . $employee->email);
    }

    public function sendAll(Request $request): RedirectResponse
    {
        $payrolls = Payroll::with('employee')
            ->where(function ($q) {
                $q->whereIn('status', array_merge(
                    PayrollPaymentWorkflowService::directorApprovedStatuses(),
                    PayrollPaymentWorkflowService::payableStatuses(),
                    [PayrollPaymentWorkflowService::PAID]
                ));
            })
            ->orderByDesc('month')
            ->get();
        $sentCount = 0;
        $failedCount = 0;
        $messages = [];

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;

            if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                $failedCount++;
                $messages[] = "Nhân viên #{$payroll->id} không có email hợp lệ.";
                $payroll->update(['email_status' => 'failed']);
                continue;
            }

            try {
                Mail::to($employee->email)->send(new PayrollMail($payroll));
                $payroll->update([
                    'sent_at' => now(),
                    'sent_by' => Auth::id(),
                    'email_status' => 'sent',
                ]);
                $sentCount++;
            } catch (\Throwable $exception) {
                $failedCount++;
                $messages[] = "Gửi email cho {$employee->name} thất bại: {$exception->getMessage()}";
                $payroll->update(['email_status' => 'failed']);
            }
        }

        $message = "Đã gửi {$sentCount} phiếu lương.";

        if ($failedCount > 0) {
            $message .= " {$failedCount} phiếu không gửi được.";
        }

        return redirect()->route('payroll.email.index')
            ->with('success', $message)
            ->with('send_errors', $messages);
    }

    protected function canNotify(Payroll $payroll): bool
    {
        $workflow = app(PayrollPaymentWorkflowService::class);

        return $workflow->isDirectorApproved($payroll->status)
            || $workflow->canPay($payroll)
            || $payroll->status === PayrollPaymentWorkflowService::PAID;
    }
}
