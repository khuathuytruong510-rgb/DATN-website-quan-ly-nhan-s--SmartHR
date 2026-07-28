<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Mail\PayrollMail;
use App\Mail\PayrollConfirmationMail;
use App\Traits\HasLeaveLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\PayrollCalculationService;
use App\Models\ActivityLog;


class AccountantController extends Controller
{
    use HasLeaveLimit;

    public function dashboard(): View
    {
        $total = Payroll::count();
        $pending = Payroll::where('status', 'pending')->count();
        $approved = Payroll::whereIn('status', [
            'waiting_confirmation',
            'ready_for_payment',
            'paid',
            'approved',
        ])->count();

        return view('accountant.dashboard', compact('total', 'pending', 'approved'));
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
            $query->where('status', $status);
        }

        $payrolls = $query->paginate(15)->withQueryString();

        return view('accountant.payroll.index', compact('payrolls'));
    }

    public function payrollShow(Payroll $payroll): View
    {
        $payroll->load('employee');
        return view('accountant.payroll.show', compact('payroll'));
    }

    public function sendPayrollEmail(Payroll $payroll)
    {
        $employee = $payroll->employee;

        if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('accountant.payroll.show', $payroll)
                ->with('error', 'Nhân viên chưa có email hợp lệ.');
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
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', 'Gửi email thất bại: ' . $e->getMessage());
        }

        return redirect()->route('accountant.payroll.show', $payroll)->with('success', 'Đã gửi email đến ' . $employee->email);
    }

    public function recalculatePayroll(Payroll $payroll, PayrollCalculationService $service)
    {
        if ($payroll->locked) {
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', 'Bảng lương đang bị khoá.');
        }

        $employee = $payroll->employee;
        if (! $employee) {
            return back()->with('error', 'Nhân viên không tồn tại');
        }

        $month = (int) $payroll->month;
        $year = (int) ($payroll->year ?? now()->year);

        $newPayroll = $service->calculate($employee, $month, $year);

        ActivityLog::create(['user_id' => Auth::id(), 'action' => 'recalculate_payroll', 'meta' => 'payroll:' . $newPayroll->id]);

        return redirect()->route('accountant.payroll.show', $newPayroll)->with('success', 'Đã tính lại bảng lương');
    }

    public function lockPayroll(Payroll $payroll)
    {
        $payroll->update(['locked' => true]);
        ActivityLog::create(['user_id' => Auth::id(), 'action' => 'lock_payroll', 'meta' => 'payroll:' . $payroll->id]);
        return redirect()->route('accountant.payroll.show', $payroll)->with('success', 'Đã khoá bảng lương');
    }

    public function unlockPayroll(Payroll $payroll)
    {
        $payroll->update(['locked' => false]);
        ActivityLog::create(['user_id' => Auth::id(), 'action' => 'unlock_payroll', 'meta' => 'payroll:' . $payroll->id]);
        return redirect()->route('accountant.payroll.show', $payroll)->with('success', 'Đã mở khoá bảng lương');
    }

    public function sendAllPayrolls(Request $request)
    {
        $payrolls = Payroll::with('employee')->orderByDesc('month')->get();
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

    public function payrollGenerate(): View
    {
        return view('accountant.payroll.generate');
    }

    public function generatePayroll(Request $request, PayrollCalculationService $service)
    {
        $monthInput = $request->input('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
            return redirect()->route('accountant.payroll.generate')->with('error', 'Định dạng tháng không hợp lệ.');
        }

        [$year, $month] = explode('-', $monthInput);
        $year = (int) $year;
        $month = (int) $month;

        $employees = Employee::where('status', 'active')->get();
        $count = 0;

        foreach ($employees as $employee) {
            $service->calculate($employee, $month, $year);
            $count++;
        }

        return redirect()->route('accountant.payroll.generate')->with('success', "Đã tính lương cho {$count} nhân viên cho {$monthInput}.");
    }

    public function payrollSend(): View
    {
        return view('accountant.payroll.send');
    }

    public function payrollFeedback(): View
    {
        $issues = Payroll::with('employee')
            ->where('confirmation_status', 'issue_reported')
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

    public function createLeaveRequest(): View
    {
        return view('accountant.leave_requests.create', [
            'leaveRequest' => new LeaveRequest([
                'status' => 'pending',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(1)->toDateString(),
            ]),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storeLeaveRequest(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'type' => ['required', 'in:sick,personal,annual,unpaid,maternity'],
            'reason' => ['nullable', 'string'],
            'is_urgent' => ['nullable', 'boolean'],
            'urgent_reason' => ['required_if:is_urgent,1', 'nullable', 'string', 'max:500'],
        ]);

        $data['is_urgent'] = $request->boolean('is_urgent');
        $data['half_day'] = $request->boolean('half_day');

        $limitCheck = $this->checkLeaveLimit(
            $data['employee_id'],
            $data['start_date'],
            $data['end_date'],
            $data['half_day']
        );

        if ($limitCheck['exceeded'] && empty($data['is_urgent'])) {
            $msg = "Nhân viên đã sử dụng {$limitCheck['used_days']}/{$limitCheck['max_days']} ngày nghỉ phép trong tháng này. ";
            if ($limitCheck['requests_exceeded']) {
                $msg .= "Nhân viên đã hết {$limitCheck['max_requests']} lượt xin nghỉ trong tháng. ";
            }
            $msg .= "Vui lòng yêu cầu nhân viên liên hệ bộ phận hỗ trợ nếu cần nghỉ thêm với lý do thuyết phục.";
            return back()->withInput()->with('error', $msg);
        }

        $data['days'] = $this->calculateLeaveDays($data['start_date'], $data['end_date'], $data['half_day']);
        $data['status'] = 'pending';

        LeaveRequest::create($data);

        return redirect()->route('accountant.leave_requests')->with('success', 'Đã tạo đơn nghỉ phép thành công.');
    }

    public function approveLeaveRequest(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('accountant.leave_requests')->with('success', 'Đã duyệt đơn nghỉ phép.');
    }

    public function rejectLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()->route('accountant.leave_requests')->with('success', 'Đã từ chối đơn nghỉ phép.');
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
        return view('accountant.activity_logs.index');
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

