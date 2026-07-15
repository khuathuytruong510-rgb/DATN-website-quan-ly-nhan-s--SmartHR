<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Benefit;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->with('department')->firstOrFail();

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->latest()
            ->first();

        $latestPayroll = Payroll::where('employee_id', $employee->id)
            ->latest('month')
            ->first();

        return view('employee.dashboard', [
            'employee' => $employee,
            'todayAttendance' => $todayAttendance,
            'latestPayroll' => $latestPayroll,
        ]);
    }

    public function attendanceIndex(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        if ($request->expectsJson()) {
            $attendances = Attendance::where('employee_id', $employee->id)->latest()->get();

            return response()->json([
                'attendances' => $attendances,
            ], 200);
        }

        $attendances = Attendance::where('employee_id', $employee->id)->latest()->paginate(10);

        return view('employee.attendance.index', [
            'attendances' => $attendances,
        ]);
    }

    public function attendanceCreate(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        return view('employee.attendance.form', [
            'attendance' => new Attendance(['employee_id' => $employee->id, 'status' => 'present', 'date' => now()->toDateString()]),
        ]);
    }

    public function attendanceStore(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['required', 'in:present,late,leave,absent'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['employee_id'] = $employee->id;

        $attendance = Attendance::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'attendance' => $attendance,
            ], 201);
        }

        return redirect()->route('me.attendance')->with('success', 'Ghi chấm công thành công.');
    }

    public function attendanceUpdate(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        if ($attendance->employee_id !== $employee->id) {
            abort(403);
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['required', 'in:present,late,leave,absent'],
            'notes' => ['nullable', 'string'],
        ]);

        $attendance->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'attendance' => $attendance,
            ], 200);
        }

        return redirect()->route('me.attendance')->with('success', 'Cập nhật chấm công thành công.');
    }

    public function contracts()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->first();

        $contracts = collect();
        if ($employee) {
            $contracts = Contract::where('employee_id', $employee->id)
                ->with('employee.department')
                ->latest()
                ->get();
        }

        return view('employee.contracts.index', ['contracts' => $contracts, 'employee' => $employee]);
    }

    public function payrolls()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $payrolls = Payroll::where('employee_id', $employee->id)->latest()->get();

        return view('employee.payroll.index', ['payrolls' => $payrolls]);
    }

    public function evaluations(): View
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $evaluations = EmployeeEvaluation::where('employee_id', $employee->id)
            ->latest()
            ->get();

        return view('employee.evaluations', compact('evaluations'));
    }

    public function benefits(): View
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $benefits = Benefit::where('employee_id', $employee->id)
            ->latest()
            ->get();

        return view('employee.benefits', compact('benefits'));
    }

    public function department()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->with('department')->firstOrFail();

        return view('employee.department', ['department' => $employee->department, 'employee' => $employee]);
    }

    public function schedule()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        return view('employee.schedule.index', ['employee' => $employee]);
    }

    public function leaveIndex()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $leaves = $employee->leaveRequests()->latest()->get();

        return view('employee.leave.index', ['leaves' => $leaves]);
    }

    public function leaveCreate()
    {
        return view('employee.leave.form');
    }

    public function leaveStore(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'in:annual,sick,personal,unpaid'],
            'reason' => ['nullable', 'string'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['days'] = (int) (Carbon::parse($data['end_date'])->diffInDays(Carbon::parse($data['start_date'])) + 1);
        $data['status'] = 'pending';

        LeaveRequest::create($data);

        return redirect()->route('me.leave_requests')->with('success', 'Đã tạo đơn nghỉ phép.');
    }

    public function profile()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->with('department')->firstOrFail();

        return view('employee.profile', compact('employee'));
    }

    public function trainings(): View
    {
        return view('employee.trainings');
    }

    public function rewards(): View
    {
        return view('employee.rewards');
    }

    public function editProfile()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->with('department')->firstOrFail();

        return view('employee.profile-edit', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'cccd' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'education' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string'],
        ]);

        $employee->update($data);

        return redirect()->route('me.profile')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    /**
     * Show change password form for employee
     */
    public function showChangePassword()
    {
        return view('employee.change-password');
    }

    /**
     * Update employee user's password
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
            return redirect()->back()->with('error', 'Mật khẩu hiện tại không đúng.');
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        $user->save();

        // Log activity
        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'change_password',
                'meta' => 'User changed password',
            ]);
        }

        return redirect()->route('me.profile')->with('success', 'Đổi mật khẩu thành công.');
    }

    public function notifications(): View
    {
        $user = auth()->user();

        $notifications = Notification::where(function ($query) use ($user) {
            $query->where('target', 'all');

            if ($user->is_hr) {
                $query->orWhere('target', 'employee');
                $query->orWhere('target', 'hr');
            } else {
                $query->orWhere('target', 'employee');
            }
        })->latest()->paginate(10);

        return view('employee.notifications', compact('notifications'));
    }
}
