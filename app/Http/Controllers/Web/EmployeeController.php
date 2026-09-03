<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\Benefit;
use App\Models\Contract;
use App\Models\DeletionRequest;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\Payroll;
use App\Services\ContractService;
use App\Services\DeletionRequestService;
use App\Services\LeaveEligibilityService;
use App\Services\LeaveRequestService;
use App\Services\PayrollPaymentWorkflowService;
use App\Support\LeaveTypes;
use App\Support\RequestApprover;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $employee = $user->linkedEmployee();

        if (! $employee) {
            return redirect()->route('me.unlinked');
        }

        $employee->loadMissing('department');

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->latest()
            ->first();

        $todayStatus = 'Chưa chấm';
        if ($todayAttendance?->check_out) {
            $todayStatus = 'Đã ra';
        } elseif ($todayAttendance?->check_in) {
            $todayStatus = 'Đã vào';
        }

        $workflow = app(PayrollPaymentWorkflowService::class);
        $issuedStatuses = array_values(array_unique(array_merge(
            PayrollPaymentWorkflowService::directorApprovedStatuses(),
            PayrollPaymentWorkflowService::payableStatuses(),
            [PayrollPaymentWorkflowService::PAID]
        )));
        $latestPayroll = Payroll::where('employee_id', $employee->id)
            ->whereIn('status', $issuedStatuses)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first()
            ?? Payroll::where('employee_id', $employee->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first();

        $currentContract = Contract::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->latest('start_date')
            ->first()
            ?? Contract::where('employee_id', $employee->id)->latest()->first();

        $leaveLimit = app(LeaveEligibilityService::class)->quotaSummary($employee);

        $unreadNotifications = $this->employeeNotificationsQuery($user, $employee)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return view('employee.dashboard', [
            'employee' => $employee,
            'todayAttendance' => $todayAttendance,
            'todayStatus' => $todayStatus,
            'latestPayroll' => $latestPayroll,
            'currentContract' => $currentContract,
            'leaveLimit' => $leaveLimit,
            'unreadNotifications' => $unreadNotifications,
            'payrollStatusLabel' => $latestPayroll ? $workflow->statusLabel($latestPayroll->status) : null,
            'payrollIsIssued' => $latestPayroll ? in_array($latestPayroll->status, $issuedStatuses, true) : false,
        ]);
    }

    public function unlinked()
    {
        return view('employee.unlinked');
    }

    public function attendanceIndex(Request $request)
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        if (! $employee) {
            return redirect()->route('me.unlinked');
        }

        if ($request->expectsJson()) {
            $attendances = Attendance::where('employee_id', $employee->id)->latest()->get();

            return response()->json([
                'attendances' => $attendances,
            ], 200);
        }

        $attendances = Attendance::where('employee_id', $employee->id)->latest()->paginate(10);

        return view('employee.attendance.index', [
            'attendances' => $attendances,
            'employee' => $employee,
            'approverLabel' => RequestApprover::queueLabel($employee),
        ]);
    }

    public function attendanceCreate(Request $request)
    {
        return redirect()->route('me.attendance');
    }

    public function attendanceStore(Request $request)
    {
        abort(403, 'Không được tự tạo bản ghi chấm công. Dùng Check-in/Check-out hoặc gửi yêu cầu điều chỉnh.');
    }

    public function attendanceUpdate(Request $request, Attendance $attendance)
    {
        abort(403, 'Không được tự sửa giờ chấm công. Liên hệ HR nếu cần điều chỉnh (có kiểm tra và nhật ký).');
    }

    public function contracts()
    {
        $employee = auth()->user()?->linkedEmployee();

        $contracts = collect();
        if ($employee) {
            $contracts = Contract::where('employee_id', $employee->id)
                ->with('employee.department')
                ->latest()
                ->get();
        }

        return view('employee.contracts.index', ['contracts' => $contracts, 'employee' => $employee]);
    }

    public function signContract(Contract $contract, ContractService $contractService)
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee || $contract->employee_id !== $employee->id) {
            abort(403, 'Bạn chỉ được ký hợp đồng của chính mình.');
        }

        try {
            $contractService->signContract(auth()->user(), $contract, 'employee');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $contract->fresh()->isFullySigned()
            ? 'Bạn đã ký hợp đồng. Hệ thống đã ghi nhận đủ chữ ký hai bên và khóa tài liệu.'
            : 'Bạn đã ký hợp đồng phía người lao động (mô phỏng).');
    }

    public function payrolls()
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            abort(403, 'Tài khoản chưa gắn hồ sơ nhân viên.');
        }

        $payrolls = Payroll::where('employee_id', $employee->id)->with('employee')->latest()->get();

        return view('employee.payroll.index', [
            'payrolls' => $payrolls,
            'workflow' => app(\App\Services\PayrollPaymentWorkflowService::class),
        ]);
    }

    public function payrollShow(Payroll $payroll)
    {
        $this->assertOwnEmployeeId($payroll->employee_id);

        return view('employee.payroll.index', [
            'payrolls' => collect([$payroll->load('employee')]),
            'workflow' => app(\App\Services\PayrollPaymentWorkflowService::class),
        ]);
    }

    public function payrollHistory(Payroll $payroll)
    {
        $this->assertOwnEmployeeId($payroll->employee_id);

        $history = \App\Models\SalaryHistory::where('payroll_id', $payroll->id)->first();
        if (! $history) {
            abort(404, 'Chưa có lịch sử thanh toán cho phiếu này.');
        }

        return redirect()->route('me.salary_histories.show', $history);
    }

    public function contractShow(Contract $contract)
    {
        $this->assertOwnEmployeeId($contract->employee_id);

        return view('employee.contracts.index', [
            'contracts' => collect([$contract->load('employee.department')]),
            'employee' => $contract->employee,
        ]);
    }

    public function attendanceShow(Attendance $attendance)
    {
        $this->assertOwnEmployeeId($attendance->employee_id);

        return redirect()->route('me.attendance');
    }

    private function assertOwnEmployeeId($employeeId): void
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee || (int) $employee->id !== (int) $employeeId) {
            abort(403, 'Bạn chỉ được xem dữ liệu của chính mình.');
        }
    }

    /**
     * Lịch sử thanh toán của NV — dùng chung trang lịch sử lương đã thanh toán.
     */
    public function paymentHistory()
    {
        return redirect()->route('me.salary_histories');
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
        $employee = Employee::where('email', $user->email)->with('department')->firstOrFail();

        return view('employee.schedule.index', ['employee' => $employee]);
    }

    public function leaveIndex()
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            return redirect()->route('me.unlinked');
        }

        $leaves = $employee->leaveRequests()->with('approver')->latest()->get();

        return view('employee.leave.index', [
            'employee' => $employee,
            'leaves' => $leaves,
            'leaveLimit' => app(LeaveEligibilityService::class)->quotaSummary($employee),
        ]);
    }

    public function leaveCreate()
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            return redirect()->route('me.unlinked');
        }

        $eligibility = app(LeaveEligibilityService::class);

        return view('employee.leave.form', [
            'employee' => $employee,
            'contract' => $eligibility->activeContract($employee),
            'leaveLimit' => $eligibility->quotaSummary($employee),
            'leaveTypes' => LeaveTypes::available($employee),
            'defaultType' => LeaveTypes::default($employee),
        ]);
    }

    public function leaveStore(Request $request)
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        if (! $employee) {
            return redirect()->route('me.unlinked');
        }

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'type' => ['required', LeaveTypes::validationRule($employee)],
            'reason' => ['nullable', 'string'],
            'is_urgent' => ['nullable', 'boolean'],
            'urgent_reason' => ['required_if:is_urgent,1', 'nullable', 'string', 'max:500'],
        ]);

        $data['is_urgent'] = $request->boolean('is_urgent');
        $data['half_day'] = $request->boolean('half_day');

        try {
            app(LeaveRequestService::class)->submit($employee, $user, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('me.leave_requests')->with('success', RequestApprover::submittedMessage($employee));
    }

    public function cancelLeave(LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        if ((int) $leaveRequest->employee_id !== (int) $employee->id) {
            abort(403, 'Bạn chỉ được hủy đơn của chính mình.');
        }

        $reason = trim((string) request('cancel_reason', ''));

        try {
            DB::transaction(function () use ($leaveRequest, $user, $employee, $reason) {
                $leave = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();
                if ((int) $leave->employee_id !== (int) $employee->id) {
                    abort(403, 'Bạn chỉ được hủy đơn của chính mình.');
                }

                $status = $leave->status ?: 'pending';
                $beforeLeaveDay = optional($leave->start_date)->toDateString() > now()->toDateString();

                if ($status === 'pending') {
                    $leave->update([
                        'status' => 'cancelled',
                        'cancelled_by' => $user->id,
                        'cancelled_at' => now(),
                        'cancel_reason' => $reason !== '' ? $reason : 'Nhân viên hủy đơn đang chờ duyệt',
                    ]);
                } elseif ($status === 'approved' && $beforeLeaveDay) {
                    $data = request()->validate([
                        'cancel_reason' => ['required', 'string', 'max:500'],
                    ]);
                    $leave->update([
                        'status' => 'cancelled',
                        'cancelled_by' => $user->id,
                        'cancelled_at' => now(),
                        'cancel_reason' => $data['cancel_reason'],
                    ]);
                    app(LeaveRequestService::class)->revertApprovedAttendance($leave);
                } else {
                    throw new \RuntimeException('Chỉ hủy được đơn đang chờ duyệt, hoặc đơn đã duyệt trước ngày nghỉ.');
                }

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'leave_cancelled',
                    'meta' => 'leave:'.$leave->id.';reason:'.$leave->cancel_reason,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('me.leave_requests')->with('success', 'Đã hủy đơn nghỉ phép. Lịch sử đơn vẫn được giữ.');
    }

    public function profile()
    {
        $user = auth()->user();
        $employee = $user->employee()
            ->with(['department', 'positionDetail'])
            ->first()
            ?? Employee::query()
                ->with(['department', 'positionDetail'])
                ->where('email', $user->email)
                ->firstOrFail();

        $baseSalary = $this->resolveProfileBaseSalary($employee);

        return view('employee.profile', compact('employee', 'baseSalary'));
    }

    private function resolveProfileBaseSalary(Employee $employee): ?float
    {
        $contract = Contract::query()
            ->where('employee_id', $employee->id)
            ->whereNotIn('status', [
                Contract::STATUS_CANCELLED,
                Contract::STATUS_REJECTED,
                Contract::STATUS_TERMINATED,
            ])
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [Contract::STATUS_ACTIVE])
            ->latest('start_date')
            ->latest('id')
            ->first();

        $fromContract = (float) ($contract?->base_salary ?: $contract?->salary ?: 0);
        if ($fromContract > 0) {
            return $fromContract;
        }

        $fromPosition = (float) optional($employee->positionDetail)->base_salary;
        if ($fromPosition > 0) {
            return $fromPosition;
        }

        $fromPayroll = (float) optional(
            Payroll::query()
                ->where('employee_id', $employee->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first()
        )->base_salary;

        return $fromPayroll > 0 ? $fromPayroll : null;
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
        ]);

        $employee->update($data);

        return redirect()->route('me.profile')->with('success', 'Đã gửi cập nhật thông tin cá nhân.');
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

        return redirect()->route('me.profile')->with('success', 'Mật khẩu đã được cập nhật.');
    }

    public function notifications(): View
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->first();

        $notifications = $this->employeeNotificationsQuery($user, $employee)
            ->with(['reads' => fn ($q) => $q->where('user_id', $user->id)])
            ->latest()
            ->paginate(10);

        return view('employee.notifications', compact('notifications'));
    }

    public function markNotificationRead(Notification $notification): RedirectResponse
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        $allowed = $this->employeeNotificationsQuery($user, $employee)
            ->whereKey($notification->id)
            ->exists();

        if (! $allowed) {
            abort(403);
        }

        NotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return back()->with('success', 'Đã đánh dấu đã đọc.');
    }

    public function submitTransferFeedback(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            abort(403);
        }

        $data = $request->validate([
            'agree' => ['required', 'in:1,0'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            app(DeletionRequestService::class)->submitTransferFeedback(
                $deletionRequest,
                $employee,
                $request->user(),
                $data['agree'] === '1',
                $data['message'] ?? ''
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi phản hồi đến bộ phận Nhân sự.');
    }

    public function requestAttendanceAdjustment(Request $request, Attendance $attendance): RedirectResponse
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee || (int) $attendance->employee_id !== (int) $employee->id) {
            abort(403, 'Bạn chỉ được yêu cầu điều chỉnh chấm công của chính mình.');
        }

        $data = $request->validate([
            'requested_check_in' => ['nullable', 'date_format:H:i'],
            'requested_check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (blank($data['requested_check_in'] ?? null) && blank($data['requested_check_out'] ?? null)) {
            return back()->with('error', 'Vui lòng nhập giờ vào hoặc giờ ra đề nghị.');
        }

        try {
            DB::transaction(function () use ($attendance, $employee, $data) {
                $locked = Attendance::query()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();
                if ((int) $locked->employee_id !== (int) $employee->id) {
                    abort(403);
                }

                $pending = AttendanceAdjustmentRequest::query()
                    ->where('attendance_id', $locked->id)
                    ->where('status', AttendanceAdjustmentRequest::PENDING)
                    ->lockForUpdate()
                    ->exists();

                if ($pending) {
                    throw new \RuntimeException('Đã có yêu cầu điều chỉnh đang chờ HR xử lý cho ngày này.');
                }

                AttendanceAdjustmentRequest::create([
                    'employee_id' => $employee->id,
                    'attendance_id' => $locked->id,
                    'work_date' => $locked->date,
                    'current_check_in' => $locked->getRawOriginal('check_in'),
                    'current_check_out' => $locked->getRawOriginal('check_out'),
                    'requested_check_in' => $data['requested_check_in'] ?? null,
                    'requested_check_out' => $data['requested_check_out'] ?? null,
                    'reason' => $data['reason'],
                    'status' => AttendanceAdjustmentRequest::PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'applied_at' => null,
                    'review_note' => null,
                ]);

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'attendance_adjustment_requested',
                    'meta' => optional($locked->date)->format('d/m/Y'),
                ]);

                RequestApprover::notifyQueue(
                    $employee,
                    auth()->user(),
                    'Yêu cầu điều chỉnh chấm công',
                    sprintf(
                        '%s đề nghị điều chỉnh chấm công ngày %s: %s',
                        $employee->name,
                        optional($locked->date)->format('d/m/Y'),
                        $data['reason']
                    ),
                    ['attendance_id' => $locked->id, 'type' => 'attendance_adjustment']
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException) {
            return back()->with('error', 'Đã có yêu cầu điều chỉnh đang chờ '.RequestApprover::queueLabel($employee).' xử lý cho ngày này.');
        }

        return back()->with('success', 'Đã gửi yêu cầu điều chỉnh chấm công. '.RequestApprover::queueLabel($employee).' sẽ xử lý, bạn không tự sửa giờ.');
    }

    protected function employeeNotificationsQuery($user, ?Employee $employee)
    {
        return Notification::query()
            ->where(function ($query) {
                $query->where('target', 'all')->orWhere('target', 'employee');
            })
            ->when($employee, function ($query) use ($employee) {
                $query->where(function ($q) use ($employee) {
                    $q->whereNull('data')
                        ->orWhereNull('data->employee_id')
                        ->orWhere('data->employee_id', $employee->id);
                });
            });
    }
}
