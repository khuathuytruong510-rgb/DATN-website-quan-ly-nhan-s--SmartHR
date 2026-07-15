<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Mail\PayrollMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\PayrollService;
use App\Models\ActivityLog;


class AccountantController extends Controller
{
    public function dashboard(): View
    {
        $total = Payroll::count();
        $pending = Payroll::where('status', 'pending')->count();
        $approved = Payroll::where('status', 'approved')->count();

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

    public function recalculatePayroll(Payroll $payroll)
    {
        if ($payroll->locked) {
            return redirect()->route('accountant.payroll.show', $payroll)->with('error', 'Bảng lương đang bị khoá.');
        }

        $employee = $payroll->employee;
        if (! $employee) {
            return back()->with('error', 'Nhân viên không tồn tại');
        }

        $service = new PayrollService();
        $monthParts = explode('-', $payroll->month);
        $year = (int)($monthParts[0] ?? $payroll->year ?? now()->year);
        $month = (int)($monthParts[1] ?? $payroll->month);

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
            try {
                Mail::to($employee->email)->send(new PayrollMail($p));
                $p->update(['sent_at' => now(), 'sent_by' => Auth::id(), 'email_status' => 'sent']);
                ActivityLog::create(['user_id' => Auth::id(), 'action' => 'send_payroll', 'meta' => 'payroll:' . $p->id]);
                $sent++;
            } catch (\Throwable $e) {
                $p->update(['email_status' => 'failed']);
                $failed++;
            }
        }

        return redirect()->route('accountant.payroll.index')->with('success', "Đã gửi {$sent} bảng lương. {$failed} thất bại.");
    }

    // Placeholder routes for actions (will implement per-module)
    public function payrollGenerate(): View
    {
        return view('accountant.payroll.generate');
    }

    public function payrollSend(): View
    {
        return view('accountant.payroll.send');
    }

    public function payrollFeedback(): View
    {
        return view('accountant.payroll.feedback');
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
