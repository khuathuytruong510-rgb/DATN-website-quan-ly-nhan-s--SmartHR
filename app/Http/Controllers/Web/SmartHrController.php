<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractFormRequest;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeEvaluation;
use App\Models\Benefit;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Notification;
use App\Models\Position;
use App\Models\User;
use App\Models\OvertimeRequest;
use App\Models\Recruitment;
use App\Models\SalaryAdvance;
use App\Models\SupportRequest;
use App\Traits\HasLeaveLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Mail\PayrollConfirmationMail;
use App\Services\ContractService;
use App\Services\EvaluationService;
use App\Services\PayrollCalculationService;
use App\Support\ContractFixedTerms;
use Illuminate\Support\Facades\Mail;

class SmartHrController extends Controller
{
    use HasLeaveLimit;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $userRecord = User::where('email', $request->input('email'))->first();
        if ($userRecord && $userRecord->is_locked) {
            return back()
                ->withErrors(['email' => 'Tài khoản này đã bị khoá. Vui lòng liên hệ quản trị.'])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Check if user is an employee or a privileged staff
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if ($user->is_admin || $user->is_hr || $user->is_accountant) {
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Đăng nhập thành công! Chào mừng ' . $user->name);
        }

        if ($employee) {
            return redirect()->intended(route('me.attendance'))
                ->with('success', 'Đăng nhập thành công! Chào mừng ' . $user->name);
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Đăng nhập thành công! Chào mừng ' . $user->name);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Đăng ký tài khoản thành công.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->is_admin || $user->is_hr || $user->is_accountant) {
            // 1. Tổng quan nhân sự
            $hrOverview = [
                'totalEmployees'     => Employee::count(),
                'totalDepartments'   => Department::count(),
                'totalPositions'     => Position::count(),
                'activeEmployees'    => Employee::where('status', 'active')->count(),
                'inactiveEmployees'  => Employee::where('status', 'inactive')->count(),
                'probationEmployees' => Employee::whereHas('contracts', fn($q) => $q->where('contract_type', 'probation'))->count(),
                'internEmployees'    => Employee::whereHas('contracts', fn($q) => $q->where('contract_type', 'internship'))->count(),
            ];

            // 2. Thống kê phòng ban
            $deptCollection = Department::withCount(['employees' => fn($q) => $q->where('status', 'active')])->get();
            $totalActive    = $deptCollection->sum('employees_count');
            $maxDept        = $deptCollection->sortByDesc('employees_count')->first();
            $minDept        = $deptCollection->where('employees_count', '>', 0)->sortBy('employees_count')->first();
            $departmentStats = [
                'departments'    => $deptCollection->map(fn($d) => [
                    'name'       => $d->name,
                    'count'      => $d->employees_count,
                    'percentage' => $totalActive > 0 ? round(($d->employees_count / $totalActive) * 100, 1) : 0,
                ])->sortByDesc('count')->values(),
                'totalActive'    => $totalActive,
                'maxDepartment'  => $maxDept?->name ?? 'N/A',
                'maxCount'       => $maxDept?->employees_count ?? 0,
                'minDepartment'  => $minDept?->name ?? 'N/A',
                'minCount'       => $minDept?->employees_count ?? 0,
            ];

            // 3. Thống kê chấm công
            $att = Attendance::query();
            $attendanceStats = [
                'totalWorkDays'    => (clone $att)->count(),
                'presentDays'      => (clone $att)->whereNotIn('status', ['absent'])->count(),
                'absentDays'       => (clone $att)->where('status', 'absent')->count(),
                'totalLate'        => (clone $att)->where('late_minutes', '>', 0)->count(),
                'totalEarlyLeave'  => (clone $att)->where('early_leave_minutes', '>', 0)->count(),
                'totalOvertimeHours' => (clone $att)->sum('overtime_hours'),
                'paidLeaves'       => LeaveRequest::where('status', 'approved')->sum('days'),
                'unpaidLeaves'     => LeaveRequest::where('status', 'pending')->sum('days'),
            ];

            // 4. Thống kê lương
            $pay      = Payroll::query();
            $totalNet = (clone $pay)->sum('total_salary');
            $payrollStats = [
                'totalFund'       => $totalNet,
                'avgSalary'       => (clone $pay)->avg('total_salary'),
                'maxSalary'       => (clone $pay)->max('total_salary'),
                'minSalary'       => (clone $pay)->min('total_salary'),
                'totalAllowance'  => (clone $pay)->sum('allowance'),
                'totalDeduction'  => (clone $pay)->sum('deduction'),
                'totalInsurance'  => (clone $pay)->sum('insurance'),
                'totalTax'        => (clone $pay)->sum('tax'),
                'totalBonus'      => (clone $pay)->sum('bonus'),
                'totalNet'        => $totalNet,
                'departmentPayroll' => Payroll::select(
                    'departments.name as department_name',
                    DB::raw('SUM(total_salary) as total_net'),
                    DB::raw('SUM(allowance) as total_allowance'),
                    DB::raw('SUM(bonus) as total_bonus'),
                    DB::raw('SUM(insurance) as total_insurance'),
                    DB::raw('SUM(tax) as total_tax'),
                    DB::raw('SUM(overtime_salary) as total_overtime'),
                    DB::raw('COUNT(DISTINCT payrolls.employee_id) as emp_count')
                )
                ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->groupBy('departments.name')
                ->orderByDesc('total_net')
                ->get(),
            ];

            // 5. Thống kê hợp đồng
            $contractStats = [
                'total'        => Contract::count(),
                'active'       => Contract::where('status', 'active')->count(),
                'expiringSoon' => Contract::where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->where('end_date', '<=', now()->addDays(30))->count(),
                'expired'      => Contract::where('status', 'expired')
                    ->orWhere(fn($q) => $q->where('status', 'active')->where('end_date', '<', now()))->count(),
                'byType'       => Contract::select('contract_type', DB::raw('count(*) as count'))
                    ->groupBy('contract_type')->pluck('count', 'contract_type'),
            ];

            // 6. Thống kê đơn từ
            $requestStats = [
                'totalLeave'    => LeaveRequest::count(),
                'totalOvertime' => OvertimeRequest::count(),
                'totalAdvance'  => SalaryAdvance::count(),
                'totalSupport'  => SupportRequest::count(),
                'pendingAll'    => LeaveRequest::where('status', 'pending')->count()
                    + OvertimeRequest::where('status', 'pending')->count()
                    + SalaryAdvance::where('status', 'pending')->count()
                    + SupportRequest::where('status', 'pending')->count(),
                'approvedAll'   => LeaveRequest::where('status', 'approved')->count()
                    + OvertimeRequest::where('status', 'approved')->count()
                    + SalaryAdvance::where('status', 'approved')->count()
                    + SupportRequest::where('status', 'approved')->count(),
                'rejectedAll'   => LeaveRequest::where('status', 'rejected')->count()
                    + OvertimeRequest::where('status', 'rejected')->count()
                    + SalaryAdvance::where('status', 'rejected')->count()
                    + SupportRequest::where('status', 'rejected')->count(),
            ];

            // 7. Thống kê tài khoản
            $accountStats = [
                'total'     => User::count(),
                'admin'     => User::where('is_admin', true)->count(),
                'hr'        => User::where('is_hr', true)->count(),
                'accountant'=> User::where('is_accountant', true)->count(),
                'employee'  => User::where('is_admin', false)->where('is_hr', false)->where('is_accountant', false)->count(),
                'locked'    => User::where('is_locked', true)->count(),
                'active'    => User::where('is_locked', false)->count(),
            ];

            // 8. Tuyển dụng
            $recruitmentStats = ['openPositions' => 0, 'totalApplications' => 0, 'hired' => 0, 'rejected' => 0];
            try {
                $recruitmentStats['openPositions']    = Recruitment::where('status', 'open')->count();
                $recruitmentStats['totalApplications']= Recruitment::count();
                $recruitmentStats['hired']            = Recruitment::where('status', 'hired')->count();
                $recruitmentStats['rejected']         = Recruitment::where('status', 'rejected')->count();
            } catch (\Exception $e) {}

            // 9. Xu hướng 12 tháng
            $monthlyPayrollTrend = collect();
            $monthlyNewEmployees = collect();
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $m    = (int) $date->format('m');
                $y    = (int) $date->format('Y');
                $p    = Payroll::where('month', $m)->where('year', $y)
                    ->selectRaw('COALESCE(SUM(total_salary), 0) as t')->first();
                $monthlyPayrollTrend->push(['label' => $date->format('m/Y'), 'total' => (float) $p->t]);

                $s   = $date->copy()->startOfMonth();
                $e   = $date->copy()->endOfMonth();
                $cnt = Employee::where('start_date', '>=', $s)->where('start_date', '<=', $e)->count();
                $monthlyNewEmployees->push(['label' => $date->format('m/Y'), 'count' => $cnt]);
            }

            // 10. Hợp đồng sắp hết hạn
            $expiringContracts = Contract::with(['employee.department'])
                ->whereNotIn('status', ['expired', 'cancelled'])
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->orderBy('end_date')
                ->get();

            return view('admin.dashboard', compact(
                'hrOverview', 'departmentStats', 'attendanceStats', 'payrollStats',
                'contractStats', 'requestStats', 'accountStats', 'recruitmentStats',
                'monthlyPayrollTrend', 'monthlyNewEmployees', 'expiringContracts'
            ));
        }

        return redirect()->route('me.dashboard');
    }

    public function positions(): View
    {
        $positions = [
            [
                'name' => 'HR Manager',
                'department' => 'Nhân sự',
                'description' => 'Quản lý phòng nhân sự',
                'status' => 'Hoạt động',
            ],
            [
                'name' => 'HR Executive',
                'department' => 'Nhân sự',
                'description' => 'Phụ trách tuyển dụng, hồ sơ',
                'status' => 'Hoạt động',
            ],
            [
                'name' => 'Finance Officer',
                'department' => 'Kế toán',
                'description' => 'Quản lý tài chính',
                'status' => 'Hoạt động',
            ],
            [
                'name' => 'Senior Developer',
                'department' => 'IT',
                'description' => 'Phát triển hệ thống',
                'status' => 'Hoạt động',
            ],
        ];

        return view('positions.index', compact('positions'));
    }

    public function notifications(): View
    {
        return view('notifications.index');
    }

    public function accounts(): View
    {
        return view('accounts.index', [
            'users' => User::latest()->paginate(10),
        ]);
    }

    public function createAccount(): View
    {
        return view('accounts.form', [
            'user' => new User(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $emailRules = ['required', 'email', 'max:255', 'unique:users,email'];
        if ($request->input('role') !== 'admin') {
            $emailRules[] = 'unique:employees,email';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:employee,hr,admin,accountant'],
            'department_id' => ['required_if:role,employee,hr,accountant', 'nullable', 'exists:departments,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $data['role'] === 'admin',
            'is_hr' => $data['role'] === 'hr',
            'is_accountant' => $data['role'] === 'accountant',
        ]);

        if ($data['role'] !== 'admin') {
            $department = Department::findOrFail($data['department_id']);
            Employee::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'employee_code' => Employee::generateUniqueEmployeeCode($department),
                'position' => $data['role'] === 'hr' ? 'HR' : ($data['role'] === 'accountant' ? 'Kế toán' : 'Nhân viên'),
                'department_id' => $data['department_id'],
                'status' => 'active',
            ]);
        }

        return redirect()->route('accounts.index')->with('success', 'Tạo tài khoản thành công.');
    }

    public function editAccount(User $user): View
    {
        return view('accounts.form', [
            'user' => $user,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function updateAccount(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:employee,hr,admin,accountant'],
            'department_id' => ['required_if:role,employee,hr,accountant', 'nullable', 'exists:departments,id'],
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['role'] === 'admin',
            'is_hr' => $data['role'] === 'hr',
            'is_accountant' => $data['role'] === 'accountant',
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        if ($data['role'] === 'admin') {
            $user->employee()->delete();
        } else {
            $user->employee()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'position' => $data['role'] === 'hr' ? 'HR' : ($data['role'] === 'accountant' ? 'Kế toán' : 'Nhân viên'),
                    'department_id' => $data['department_id'],
                    'status' => 'active',
                ]
            );
        }

        return redirect()->route('accounts.index')->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroyAccount(User $user): RedirectResponse
    {
        $auth = Auth::user();
        if ($auth->id === $user->id) {
            return redirect()->route('accounts.index')->with('error', 'Bạn không thể xoá chính mình.');
        }

        $user->delete();

        return redirect()->route('accounts.index')->with('success', 'Xoá tài khoản thành công.');
    }

    public function toggleLockAccount(User $user): RedirectResponse
    {
        $auth = Auth::user();
        if ($auth->id === $user->id) {
            return redirect()->route('accounts.index')->with('error', 'Bạn không thể khoá/mở khoá chính mình.');
        }

        $user->is_locked = ! $user->is_locked;
        $user->save();

        $msg = $user->is_locked ? 'Đã khoá tài khoản.' : 'Đã mở khoá tài khoản.';

        return redirect()->route('accounts.index')->with('success', $msg);
    }

    public function impersonate(User $user): RedirectResponse
    {
        $admin = Auth::user();
        if (! $admin || ! $admin->is_admin) {
            abort(403);
        }

        if ($admin->id === $user->id) {
            return redirect()->route('accounts.index')->with('error', 'Bạn không thể đăng nhập thay chính mình.');
        }

        if ($user->is_locked) {
            return redirect()->route('accounts.index')->with('error', 'Không thể đăng nhập thay tài khoản đã bị khoá.');
        }

        session(['impersonator_id' => $admin->id]);
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Bạn đang xem hệ thống dưới quyền: ' . $user->name);
    }

    public function stopImpersonation(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');
        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($impersonatorId);
        if ($admin) {
            Auth::login($admin);
        }

        session()->forget('impersonator_id');

        return redirect()->route('accounts.index')->with('success', 'Đã quay lại tài khoản quản trị.');
    }

    public function permissions(): View
    {
        return view('permissions.index', [
            'users' => User::latest()->paginate(10),
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $user->update([
            'is_admin' => $request->boolean('is_admin'),
            'is_hr' => $request->boolean('is_hr'),
            'is_accountant' => $request->boolean('is_accountant'),
        ]);

        return redirect()->route('permissions.index')->with('success', 'Cập nhật phân quyền người dùng thành công.');
    }

    public function systemLogs(): View
    {
        return view('system_logs.index');
    }

    public function settings(): View
    {
        return view('settings.index');
    }

    public function departments(): View
    {
        return view('departments.index', [
            'departments' => Department::latest()->paginate(10),
        ]);
    }

    public function createDepartment(): View
    {
        return view('departments.form', ['department' => new Department()]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        Department::create($this->validateDepartment($request) + ['employee_count' => 0]);

        return redirect()->route('departments.index')->with('success', 'Tạo phòng ban thành công.');
    }

    public function editDepartment(Department $department): View
    {
        return view('departments.form', compact('department'));
    }

    public function showDepartment(Department $department): View
    {
        return view('departments.show', compact('department'));
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validateDepartment($request));
        $this->syncDepartmentCount($department->id);

        return redirect()->route('departments.index')->with('success', 'Cập nhật phòng ban thành công.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Xóa phòng ban thành công.');
    }

    public function employees(Request $request)
    {
        if ($request->expectsJson()) {
            $employees = Employee::with('department')->latest()->get();

            return response()->json([
                'employees' => $employees,
            ]);
        }

        return view('employees.index', [
            'employees' => Employee::with('department')->latest()->paginate(10),
        ]);
    }

    public function createEmployee(): View
    {
        $employee = new Employee(['status' => 'active']);
        return view('employees.form', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'nextEmployeeCode' => '', // Will be auto-generated on store based on department
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $data = $this->validateEmployee($request);
        
        // Auto-generate employee code based on department if not already set
        if (empty($data['employee_code'])) {
            $department = Department::findOrFail($data['department_id']);
            $data['employee_code'] = Employee::generateUniqueEmployeeCode($department);
        }
        
        $employee = Employee::create($data);
        $this->syncDepartmentCount($employee->department_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'employee' => $employee->load('department'),
            ], 201);
        }

        return redirect()->route('employees.index')->with('success', 'Tạo nhân viên thành công.');
    }

    public function editEmployee(Employee $employee): View
    {
        return view('employees.form', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ]);
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $oldDepartmentId = $employee->department_id;
        $employee->update($this->validateEmployee($request, $employee->id));

        $this->syncDepartmentCount($oldDepartmentId);
        $this->syncDepartmentCount($employee->department_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'employee' => $employee->load('department'),
            ], 200);
        }

        return redirect()->route('employees.index')->with('success', 'Cập nhật nhân viên thành công.');
    }

    public function destroyEmployee(Employee $employee)
    {
        $departmentId = $employee->department_id;
        $employee->delete();
        $this->syncDepartmentCount($departmentId);

        if (request()->expectsJson()) {
            return response()->json(['success' => true], 200);
        }

        return redirect()->route('employees.index')->with('success', 'Xóa nhân viên thành công.');
    }

    public function contracts(): View
    {
        $user = Auth::user();
        $query = Contract::with('employee.department');

        if ($user && ! $user->is_admin && ! $user->is_hr) {
            $employee = Employee::where('email', $user->email)->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereNull('id');
            }
        }

        return view('contracts.index', [
            'contracts' => $query->latest()->paginate(10),
        ]);
    }

    public function attendance(): View
    {
        return view('hr.attendance.index', [
            'attendances' => Attendance::with('employee')->latest()->paginate(10),
        ]);
    }

    public function createAttendance(): View
    {
        return view('hr.attendance.form', [
            'attendance' => new Attendance([
                'status' => 'present',
                'date' => now()->toDateString(),
            ]),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $attendance = Attendance::create($this->validateAttendance($request));

        return redirect()->route('attendance.index')->with('success', 'Thêm bản ghi chấm công thành công.');
    }

    public function editAttendance(Attendance $attendance): View
    {
        return view('hr.attendance.form', [
            'attendance' => $attendance,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function updateAttendance(Request $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update($this->validateAttendance($request));

        return redirect()->route('attendance.index')->with('success', 'Cập nhật bản ghi chấm công thành công.');
    }

    public function destroyAttendance(Attendance $attendance): RedirectResponse
    {
        $attendance->delete();

        return redirect()->route('attendance.index')->with('success', 'Xóa bản ghi chấm công thành công.');
    }

    public function payroll(): View
    {
        $selectedMonth = request('month', now()->format('Y-m'));

        return view('hr.payroll.index', [
            'payrolls' => Payroll::with('employee')
                ->when($selectedMonth, fn($query, $month) => $query->where('month', $month))
                ->latest()
                ->get(),
            'selectedMonth' => $selectedMonth,
        ]);
    }

    public function createPayroll(): View
    {
        return view('hr.payroll.form', [
            'payroll' => new Payroll(['status' => 'pending']),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storePayroll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'date_format:Y-m'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,paid'],
        ]);

        $data['allowance'] = $data['allowance'] ?? 0;
        $data['deduction'] = $data['deduction'] ?? 0;
        $data['status'] = $data['status'] ?? 'pending';
        $data['total_salary'] = $data['base_salary'];

        Payroll::create($data);

        return redirect()->route('payroll.index')->with('success', 'Tạo bản ghi lương thành công.');
    }

    public function generatePayroll(Request $request): RedirectResponse
    {
        $monthInput = $request->input('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
            return redirect()->route('payroll.index')->with('error', 'Định dạng tháng không hợp lệ.');
        }

        [$year, $month] = explode('-', $monthInput);
        $year = (int) $year;
        $month = (int) $month;

        $service = new PayrollCalculationService();
        $employees = Employee::where('status', 'active')->get();
        $count = 0;

        foreach ($employees as $employee) {
            $service->calculate($employee, $month, $year);
            $count++;
        }

        return redirect()->route('payroll.index', ['month' => $monthInput])
            ->with('success', "Đã tính lương cho {$count} nhân viên cho {$monthInput}.");
    }

    public function evaluations(Request $request): View
    {
        $search         = trim((string) $request->query('search', ''));
        $filterMonth    = trim((string) $request->query('month', ''));
        $filterStatus   = trim((string) $request->query('status', ''));
        $filterClass    = trim((string) $request->query('classification', ''));

        $query = EmployeeEvaluation::with(['employee.department', 'evaluator']);

        if ($search !== '') {
            $query->whereHas('employee', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
        }
        if ($filterMonth !== '') {
            $query->where('month', $filterMonth);
        }
        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }
        if ($filterClass !== '') {
            $query->where('classification', $filterClass);
        }

        $evaluations = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $stats = [
            'total'     => EmployeeEvaluation::count(),
            'pending'   => EmployeeEvaluation::where('status', 'pending')->count(),
            'approved'  => EmployeeEvaluation::where('status', 'approved')->count(),
            'excellent' => EmployeeEvaluation::where('classification', 'Xuất sắc')->count(),
            'good'      => EmployeeEvaluation::where('classification', 'Tốt')->count(),
            'average'   => EmployeeEvaluation::where('classification', 'Trung bình')->count(),
            'weak'      => EmployeeEvaluation::where('classification', 'Yếu')->count(),
            'avg_score' => round(EmployeeEvaluation::avg('score_total') ?? 0, 1),
        ];

        return view('hr.evaluations.index', compact(
            'evaluations', 'stats',
            'search', 'filterMonth', 'filterStatus', 'filterClass'
        ));
    }

    public function createEvaluation(Request $request): View
    {
        $svc        = new EvaluationService();
        $month      = $request->input('month', now()->format('Y-m'));
        $employeeId = $request->input('employee_id');
        $monthlyStats = null;
        $suggested    = null;
        if ($employeeId) {
            $monthlyStats = $svc->getMonthlyStats((int)$employeeId, $month);
            $suggested    = $svc->suggestScores((int)$employeeId, $month);
        }
        return view('hr.evaluations.form', [
            'evaluation'   => new EmployeeEvaluation(),
            'employees'    => Employee::orderBy('name')->get(),
            'month'        => $month,
            'monthlyStats' => $monthlyStats,
            'suggested'    => $suggested,
        ]);
    }

    public function storeEvaluation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id'     => ['required', 'exists:employees,id'],
            'month'           => ['required', 'date_format:Y-m', Rule::unique('employee_evaluations')->where(fn($q) => $q->where('employee_id', $request->input('employee_id')))],
            'rating'          => ['required', 'integer', 'between:1,5'],
            'punctuality'     => ['required', 'integer', 'between:0,10'],
            'task_completion' => ['required', 'integer', 'between:0,30'],
            'quality'         => ['required', 'integer', 'between:0,20'],
            'technical_skill' => ['required', 'integer', 'between:0,10'],
            'responsibility'  => ['required', 'integer', 'between:0,10'],
            'teamwork'        => ['required', 'integer', 'between:0,10'],
            'attitude'        => ['required', 'integer', 'between:0,10'],
            'summary'         => ['nullable', 'string'],
            'comments'        => ['nullable', 'string'],
        ]);

        $data['evaluator_id']   = Auth::id();
        $data['score_total']    = $this->calculateEvaluationTotal($data);
        $data['classification'] = $this->classifyEvaluationScore($data['score_total']);
        $data['status']         = 'pending';

        EmployeeEvaluation::create($data);

        return redirect()->route('evaluations.index')->with('success', 'Tạo đánh giá nhân viên thành công.');
    }

    public function editEvaluation(EmployeeEvaluation $evaluation): View
    {
        $svc          = new EvaluationService();
        $monthlyStats = $svc->getMonthlyStats($evaluation->employee_id, $evaluation->month);
        $suggested    = $svc->suggestScores($evaluation->employee_id, $evaluation->month);
        return view('hr.evaluations.form', [
            'evaluation'   => $evaluation,
            'employees'    => Employee::orderBy('name')->get(),
            'month'        => $evaluation->month,
            'monthlyStats' => $monthlyStats,
            'suggested'    => $suggested,
        ]);
    }

    public function updateEvaluation(Request $request, EmployeeEvaluation $evaluation): RedirectResponse
    {
        $data = $request->validate([
            'employee_id'     => ['required', 'exists:employees,id'],
            'month'           => ['required', 'date_format:Y-m', Rule::unique('employee_evaluations')->where(fn($q) => $q->where('employee_id', $request->input('employee_id')))->ignore($evaluation->id)],
            'rating'          => ['required', 'integer', 'between:1,5'],
            'punctuality'     => ['required', 'integer', 'between:0,10'],
            'task_completion' => ['required', 'integer', 'between:0,30'],
            'quality'         => ['required', 'integer', 'between:0,20'],
            'technical_skill' => ['required', 'integer', 'between:0,10'],
            'responsibility'  => ['required', 'integer', 'between:0,10'],
            'teamwork'        => ['required', 'integer', 'between:0,10'],
            'attitude'        => ['required', 'integer', 'between:0,10'],
            'summary'         => ['nullable', 'string'],
            'comments'        => ['nullable', 'string'],
        ]);

        $data['score_total']    = $this->calculateEvaluationTotal($data);
        $data['classification'] = $this->classifyEvaluationScore($data['score_total']);

        $evaluation->update($data);

        return redirect()->route('evaluations.index')->with('success', 'Cập nhật đánh giá nhân viên thành công.');
    }

    public function destroyEvaluation(EmployeeEvaluation $evaluation): RedirectResponse
    {
        $evaluation->delete();

        return redirect()->route('evaluations.index')->with('success', 'Xóa đánh giá nhân viên thành công.');
    }

    public function showEvaluation(EmployeeEvaluation $evaluation): View
    {
        $svc = new EvaluationService();
        $evaluation->load(['employee.department', 'evaluator', 'approvedBy']);
        $monthlyStats = $svc->getMonthlyStats($evaluation->employee_id, $evaluation->month);
        return view('hr.evaluations.show', compact('evaluation', 'monthlyStats'));
    }

    public function approveEvaluation(EmployeeEvaluation $evaluation): RedirectResponse
    {
        $evaluation->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('evaluations.index')->with('success', 'Đã duyệt đánh giá thành công.');
    }

    /** API AJAX: trả về dữ liệu thực tế + điểm đề xuất */
    public function evaluationSuggest(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month'       => ['required', 'date_format:Y-m'],
        ]);
        $svc = new EvaluationService();
        return response()->json([
            'stats'     => $svc->getMonthlyStats((int)$request->employee_id, $request->month),
            'suggested' => $svc->suggestScores((int)$request->employee_id, $request->month),
        ]);
    }

    public function benefits(Request $request): View
    {
        $query = Benefit::with(['employee', 'creator']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employee', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return view('hr.benefits.index', [
            'benefits' => $query->latest()->paginate(10)->withQueryString(),
            'filterTypes' => ['allowance' => 'Phụ cấp', 'insurance' => 'Bảo hiểm', 'bonus' => 'Thưởng', 'other' => 'Khác'],
        ]);
    }

    public function exportBenefits(Request $request): StreamedResponse
    {
        $query = Benefit::with(['employee']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employee', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $benefits = $query->latest()->get();

        $filename = 'phuc_loi_' . now()->format('Ymd_His') . '.csv';

        $columns = ['STT', 'Mã phúc lợi', 'Nhân viên', 'Tiêu đề', 'Loại', 'Áp dụng cho', 'Điều kiện', 'Đơn vị tính', 'Số tiền', 'Trạng thái phê duyệt', 'Trạng thái ứng dụng', 'Ngày hiệu lực', 'Ngày hết hạn'];

        $callback = function () use ($benefits, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($benefits as $index => $benefit) {
                fputcsv($file, [
                    $index + 1,
                    $benefit->code,
                    optional($benefit->employee)->name,
                    $benefit->title,
                    $benefit->type,
                    $benefit->applies_to,
                    $benefit->condition,
                    $benefit->unit,
                    $benefit->amount,
                    $benefit->approval_status,
                    $benefit->application_status,
                    optional($benefit->effective_date)->format('Y-m-d'),
                    optional($benefit->expiry_date)->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function createBenefit(): View
    {
        return view('hr.benefits.form', [
            'benefit' => new Benefit(),
            'employees' => Employee::orderBy('name')->get(),
            'types' => ['allowance' => 'Phụ cấp', 'insurance' => 'Bảo hiểm', 'bonus' => 'Thưởng', 'other' => 'Khác'],
            'applicationStatuses' => ['active' => 'Đang áp dụng', 'inactive' => 'Không áp dụng'],
            'approvalStatuses' => ['pending' => 'Chờ phê duyệt', 'approved' => 'Đã phê duyệt', 'rejected' => 'Từ chối'],
            'statuses' => ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'],
        ]);
    }

    public function storeBenefit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'code' => ['required', 'string', 'max:50', 'unique:benefits,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:allowance,insurance,bonus,other'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'applies_to' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'application_status' => ['required', 'in:active,inactive'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = Auth::id();

        Benefit::create($data);

        return redirect()->route('benefits.index')->with('success', 'Tạo phúc lợi thành công.');
    }

    public function showBenefit(Benefit $benefit): View
    {
        return view('hr.benefits.show', [
            'benefit' => $benefit->load(['employee', 'creator', 'approvedBy']),
        ]);
    }

    public function editBenefit(Benefit $benefit): View
    {
        return view('hr.benefits.form', [
            'benefit' => $benefit,
            'employees' => Employee::orderBy('name')->get(),
            'types' => ['allowance' => 'Phụ cấp', 'insurance' => 'Bảo hiểm', 'bonus' => 'Thưởng', 'other' => 'Khác'],
            'applicationStatuses' => ['active' => 'Đang áp dụng', 'inactive' => 'Không áp dụng'],
            'approvalStatuses' => ['pending' => 'Chờ phê duyệt', 'approved' => 'Đã phê duyệt', 'rejected' => 'Từ chối'],
            'statuses' => ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'],
        ]);
    }

    public function updateBenefit(Request $request, Benefit $benefit): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'code' => ['required', 'string', 'max:50', 'unique:benefits,code,' . $benefit->id],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:allowance,insurance,bonus,other'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'applies_to' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'application_status' => ['required', 'in:active,inactive'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $benefit->update($data);

        return redirect()->route('benefits.index')->with('success', 'Cập nhật phúc lợi thành công.');
    }

    public function approveBenefit(Benefit $benefit): RedirectResponse
    {
        $benefit->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('benefits.index')->with('success', 'Đã duyệt phúc lợi thành công.');
    }

    public function destroyBenefit(Benefit $benefit): RedirectResponse
    {
        $benefit->delete();

        return redirect()->route('benefits.index')->with('success', 'Xóa phúc lợi thành công.');
    }

    public function benefitAssignments(Request $request): View
    {
        $query = EmployeeBenefit::with(['employee', 'benefit']);

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })->orWhereHas('benefit', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        return view('hr.benefits.assignments.index', [
            'assignments' => $query->latest()->paginate(10)->withQueryString(),
            'statuses' => ['active' => 'Đang áp dụng', 'received' => 'Đã nhận', 'unused' => 'Chưa sử dụng'],
        ]);
    }

    public function createBenefitAssignment(): View
    {
        return view('hr.benefits.assignments.form', [
            'assignment' => new EmployeeBenefit(),
            'employees' => Employee::orderBy('name')->get(),
            'benefits' => Benefit::orderBy('title')->get(),
            'statuses' => ['active' => 'Đang áp dụng', 'received' => 'Đã nhận', 'unused' => 'Chưa sử dụng'],
        ]);
    }

    public function storeBenefitAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'benefit_id' => ['required', 'exists:benefits,id'],
            'applied_at' => ['required', 'date'],
            'status' => ['required', 'in:active,received,unused'],
            'notes' => ['nullable', 'string'],
        ]);

        EmployeeBenefit::create($data);

        return redirect()->route('benefits.assignments.index')->with('success', 'Gán phúc lợi cho nhân viên thành công.');
    }

    public function editBenefitAssignment(EmployeeBenefit $assignment): View
    {
        return view('hr.benefits.assignments.form', [
            'assignment' => $assignment,
            'employees' => Employee::orderBy('name')->get(),
            'benefits' => Benefit::orderBy('title')->get(),
            'statuses' => ['active' => 'Đang áp dụng', 'received' => 'Đã nhận', 'unused' => 'Chưa sử dụng'],
        ]);
    }

    public function updateBenefitAssignment(Request $request, EmployeeBenefit $assignment): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'benefit_id' => ['required', 'exists:benefits,id'],
            'applied_at' => ['required', 'date'],
            'status' => ['required', 'in:active,received,unused'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment->update($data);

        return redirect()->route('benefits.assignments.index')->with('success', 'Cập nhật gán phúc lợi thành công.');
    }

    public function destroyBenefitAssignment(EmployeeBenefit $assignment): RedirectResponse
    {
        $assignment->delete();

        return redirect()->route('benefits.assignments.index')->with('success', 'Xóa gán phúc lợi thành công.');
    }

    public function showPayroll(Payroll $payroll): View
    {
        return view('hr.payroll.show', compact('payroll'));
    }

    public function sendPayrollConfirmationEmail(Payroll $payroll): RedirectResponse
    {
        $employee = $payroll->employee;

        if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Không thể gửi email xác nhận lương: nhân viên chưa có email hợp lệ.');
        }

        $updateData = [
            'sent_at' => now(),
            'sent_by' => Auth::id(),
            'email_status' => 'sent',
            'confirmation_deadline' => now()->addDays(7),
        ];

        if ($payroll->confirmation_status !== 'confirmed') {
            $updateData['confirmation_status'] = 'pending';
        }

        $payroll->update($updateData);

        try {
            Mail::to($employee->email)
                ->send(new PayrollConfirmationMail($payroll->fresh()));
        } catch (\Throwable $exception) {
            $payroll->update(['email_status' => 'failed']);

            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Gửi email thất bại: ' . $exception->getMessage());
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Đã gửi email xác nhận lương đến ' . $employee->email);
    }

    public function approvePayroll(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status === 'paid') {
            return redirect()->route('payroll.show', $payroll)
                ->with('info', 'Phiếu lương đã được thanh toán.');
        }

        if ($payroll->confirmation_status !== 'confirmed') {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Chỉ có thể chuyển trạng thái sẵn sàng thanh toán sau khi nhân viên xác nhận.');
        }

        $payroll->update(['status' => 'approved']);

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Đã đánh dấu phiếu lương là sẵn sàng thanh toán.');
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status === 'paid') {
            return redirect()->route('payroll.show', $payroll)
                ->with('info', 'Phiếu lương đã được thanh toán.');
        }

        if ($payroll->status !== \App\Services\PayrollPaymentWorkflowService::READY_FOR_PAYMENT) {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Chỉ thanh toán khi bảng lương đủ điều kiện thanh toán (đã xác nhận).');
        }

        return redirect()->route('payroll.payment.show', $payroll)
            ->with('info', 'Vui lòng hoàn tất thanh toán tại trang quy trình.');
    }

    public function editPayroll(Payroll $payroll): View
    {
        return view('hr.payroll.form', [
            'payroll' => $payroll,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function updatePayroll(Request $request, Payroll $payroll): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['sometimes', 'required', 'exists:employees,id'],
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
            'base_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,paid'],
        ]);

        // Calculate total salary if any salary field is updated
        if (isset($data['base_salary']) || isset($data['allowance']) || isset($data['deduction'])) {
            $baseSalary = $data['base_salary'] ?? $payroll->base_salary;
            $allowance = $data['allowance'] ?? $payroll->allowance;
            $deduction = $data['deduction'] ?? $payroll->deduction;
            $data['total_salary'] = $baseSalary + $allowance - $deduction;
        }

        $payroll->update($data);

        return redirect()->route('payroll.index')->with('success', 'Cập nhật bản ghi lương thành công.');
    }

    public function destroyPayroll(Payroll $payroll): RedirectResponse
    {
        $payroll->delete();

        return redirect()->route('payroll.index')->with('success', 'Xóa bản ghi lương thành công.');
    }

    public function leaveRequests(): View
    {
        return view('hr.leave.index', [
            'leaveRequests' => LeaveRequest::with('employee', 'approver')->latest()->paginate(10),
        ]);
    }

    public function createLeaveRequest(): View
    {
        return view('hr.leave.form', [
            'leaveRequest' => new LeaveRequest([
                'status' => 'pending',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(1)->toDateString(),
            ]),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storeLeaveRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'type' => ['required', 'in:sick,personal,annual,unpaid'],
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

        return redirect()->route('leave_requests.index')->with('success', 'Gửi đơn xin nghỉ phép thành công.');
    }

    public function approveLeaveRequest(LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$this->isHROrAdmin()) {
            abort(403, 'Unauthorized: Only HR and Admin can approve leave requests.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('leave_requests.index')->with('success', 'Đã duyệt đơn nghỉ phép.');
    }

    public function rejectLeaveRequest(Request $request, \App\Models\LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$this->isHROrAdmin()) {
            abort(403, 'Unauthorized: Only HR and Admin can reject leave requests.');
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()->route('leave_requests.index')->with('success', 'Đã từ chối đơn nghỉ phép.');
    }

    public function destroyLeaveRequest(\App\Models\LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$this->isAdmin()) {
            abort(403, 'Unauthorized: Only Admin can delete leave requests.');
        }

        $leaveRequest->delete();

        return redirect()->route('leave_requests.index')->with('success', 'Xóa đơn nghỉ phép thành công.');
    }

    private function calculateEvaluationTotal(array $data): int
    {
        return (
            ($data['punctuality'] ?? 0)
            + ($data['task_completion'] ?? 0)
            + ($data['quality'] ?? 0)
            + ($data['technical_skill'] ?? 0)
            + ($data['responsibility'] ?? 0)
            + ($data['teamwork'] ?? 0)
            + ($data['attitude'] ?? 0)
        );
    }

    private function classifyEvaluationScore(int $scoreTotal): string
    {
        if ($scoreTotal >= 85) {
            return 'Xuất sắc';
        }

        if ($scoreTotal >= 70) {
            return 'Tốt';
        }

        if ($scoreTotal >= 50) {
            return 'Trung bình';
        }

        return 'Yếu';
    }

    public function showEmployee(Request $request, Employee $employee)
    {
        if ($request->expectsJson()) {
            $position = $employee->position ?: optional($employee->positionDetail)->name;
            $positionDetail = $employee->positionDetail;
            $positionMinSalary = optional($positionDetail)->salary_range_min;
            $positionMaxSalary = optional($positionDetail)->salary_range_max;
            $positionAllowance = optional($positionDetail)->allowance;
            $positionAllowanceDefault = $positionAllowance ?: ($positionMinSalary ? (int) round($positionMinSalary * 0.1) : 0);

            return response()->json([
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'position' => $position,
                'position_id' => $employee->position_id,
                'position_salary_min' => $positionMinSalary,
                'position_salary_max' => $positionMaxSalary,
                'position_allowance' => $positionAllowance,
                'position_base_salary' => optional($positionDetail)->base_salary,
                'position_allowance_default' => $positionAllowanceDefault,
                'department' => $employee->department ? ['name' => $employee->department->name] : null,
            ]);
        }

        return view('employees.show', compact('employee'));
    }

    public function createContract(): View
    {
        return view('contracts.form', [
            'contract' => new Contract([
                'contract_code' => $this->generateContractCode(),
                'status' => 'waiting_employee',
                'contract_status' => 'waiting_employee',
                'contract_type' => 'fixed_term',
                'terms' => ContractFixedTerms::forType('fixed_term'),
                'contract_content' => ContractFixedTerms::forType('fixed_term'),
                'base_salary' => 0,
                'allowance' => 0,
                'bonus' => 0,
                'allowed_unpaid_leave_days_per_month' => 1,
                'allowed_makeup_attendance_per_month' => 3,
                'allowed_maternity_leave_days' => 180,
            ]),
            'employees' => Employee::orderBy('name')->get(),
            'signers' => User::where('is_admin', true)->orWhere('is_hr', true)->orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'isEdit' => false,
        ]);
    }

    public function storeContract(ContractFormRequest $request, ContractService $contractService): RedirectResponse
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        $data = $this->prepareContractData($request, null);

        if ($this->hasActiveContract($data['employee_id'], null)) {
            return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng đang có hiệu lực.');
        }

        if ($request->input('sign_and_save')) {
            $data['employee_signed_at'] = Auth::user()->is_admin || Auth::user()->is_hr ? now()->toDateTimeString() : null;
            $data['director_signed_at'] = Auth::user()->is_admin ? now()->toDateTimeString() : null;
        }

        $contract = $contractService->createContract(Auth::user(), $data);

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tạo hợp đồng thành công.',
                'redirect' => route('contracts.index'),
            ]);
        }

        return redirect()->route('contracts.index')->with('success', 'Tạo hợp đồng thành công.');
    }

    public function showContract(Contract $contract): View
    {
        $contract->loadMissing(['employee.department', 'logs', 'renewals', 'parentContract']);

        // Bảng lương gần nhất (theo year desc, month desc)
        $payroll = null;
        if ($contract->employee) {
            $payroll = $contract->employee->payrolls()
                ->orderByDesc('year')->orderByDesc('month')->first();
        }

        $benefits = collect();
        if ($contract->employee) {
            $benefits = $contract->employee->employeeBenefits()->with('benefit')->get();
        }

        $daysRemaining = null;
        if ($contract->end_date) {
            $daysRemaining = (int) now()->diffInDays($contract->end_date, false);
        }

        $statusBadge = 'badge';
        if ($contract->status === 'expired' || ($contract->end_date && $contract->end_date->isPast())) {
            $statusBadge = 'badge expired';
        } elseif ($contract->status === 'pending') {
            $statusBadge = 'badge pending';
        }

        return view('contracts.show', compact(
            'contract', 'payroll', 'benefits', 'daysRemaining', 'statusBadge'
        ));
    }

    public function editContract(Contract $contract): View
    {
        return view('contracts.form', [
            'contract' => $contract,
            'employees' => Employee::orderBy('name')->get(),
            'signers' => User::where('is_admin', true)->orWhere('is_hr', true)->orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'isEdit' => true,
        ]);
    }

    public function updateContract(ContractFormRequest $request, Contract $contract, ContractService $contractService): RedirectResponse
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        $data = $this->prepareContractData($request, $contract);

        if ($this->hasActiveContract($data['employee_id'], $contract->id)) {
            return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng đang có hiệu lực.');
        }

        if ($request->input('sign_and_save')) {
            $data['employee_signed_at'] = Auth::user()->is_admin || Auth::user()->is_hr ? now()->toDateTimeString() : null;
            $data['director_signed_at'] = Auth::user()->is_admin ? now()->toDateTimeString() : null;
        }

        $contract = $contractService->updateContract(Auth::user(), $contract, $data);

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật hợp đồng thành công.',
                'redirect' => route('contracts.index'),
            ]);
        }

        return redirect()->route('contracts.index')->with('success', 'Cập nhật hợp đồng thành công.');
    }

    public function destroyContract(Contract $contract): RedirectResponse
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Xóa hợp đồng thành công.');
    }

    public function renewContract(Contract $contract, ContractService $contractService): View
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        // Lương mới nhất từ bảng lương (ưu tiên hơn lương hợp đồng cũ)
        $latestPayroll     = null;
        $proposedBase      = (float) ($contract->salary ?? $contract->base_salary ?? 0);
        $proposedAllowance = (float) ($contract->allowance ?? 0);

        if ($contract->employee) {
            $latestPayroll = $contract->employee->payrolls()
                ->orderByDesc('year')->orderByDesc('month')->first();
            if ($latestPayroll) {
                $proposedBase      = (float) ($latestPayroll->base_salary ?: $proposedBase);
                $proposedAllowance = (float) ($latestPayroll->allowance  ?: $proposedAllowance);
            }
        }

        // Ngày bắt đầu mới = ngày kết thúc HĐ cũ + 1 (hoặc hôm nay)
        $newStart = $contract->end_date
            ? $contract->end_date->addDay()->toDateString()
            : now()->toDateString();
        $newEnd = Carbon::parse($newStart)->addYear()->toDateString();

        return view('contracts.form', [
            'contract' => new Contract([
                'parent_contract_id'                  => $contract->id,
                'employee_id'                         => $contract->employee_id,
                'contract_code'                       => $this->generateContractCode(),
                'status'                              => 'waiting_employee',
                'contract_status'                     => 'waiting_employee',
                'base_salary'                         => $proposedBase,
                'allowance'                           => $proposedAllowance,
                'bonus'                               => $contract->bonus ?? 0,
                'contract_type'                       => $contract->contract_type,
                'start_date'                          => $newStart,
                'end_date'                            => $newEnd,
                'notes'                               => $contract->notes,
                'workplace'                           => $contract->workplace,
                'working_schedule'                    => $contract->working_schedule,
                'payment_method'                      => $contract->payment_method,
                'allowed_unpaid_leave_days_per_month' => $contract->allowed_unpaid_leave_days_per_month ?? 1,
                'allowed_makeup_attendance_per_month' => $contract->allowed_makeup_attendance_per_month ?? 3,
                'allowed_maternity_leave_days'        => $contract->allowed_maternity_leave_days ?? 180,
            ]),
            'employees'     => Employee::orderBy('name')->get(),
            'signers'       => User::where('is_admin', true)->orWhere('is_hr', true)->orderBy('name')->get(),
            'positions'     => Position::orderBy('name')->get(),
            'isEdit'        => false,
            'renewingFrom'  => $contract,
            'latestPayroll' => $latestPayroll,
        ]);
    }

    public function storeRenewalContract(ContractFormRequest $request, Contract $contract, ContractService $contractService): RedirectResponse
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        $data = $this->prepareContractData($request, null);

        // Gán parent_contract_id từ route nếu form không gửi lên
        if (empty($data['parent_contract_id'])) {
            $data['parent_contract_id'] = $contract->id;
        }

        $renewed = $contractService->renewContract(Auth::user(), $contract, $data);

        $successMsg = sprintf(
            'Gia hạn hợp đồng thành công. Mã HĐ mới: %s (lương CB: %s₫).',
            $renewed->contract_code,
            number_format($renewed->base_salary, 0, ',', '.')
        );

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => $successMsg,
                'redirect' => route('contracts.show', $renewed),
            ]);
        }

        return redirect()->route('contracts.show', $renewed)->with('success', $successMsg);
    }

    public function signContract(Contract $contract, Request $request, ContractService $contractService): RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        if (! $this->canSignContract($user, $employee, $contract)) {
            abort(403);
        }

        $party = $request->input('party', 'employee');
        $contractService->signContract($user, $contract, $party);

        return back()->with('success', 'Ký hợp đồng thành công.');
    }

    /** Đồng bộ lương hợp đồng theo bảng lương mới nhất */
    public function syncContractSalary(Contract $contract, ContractService $contractService): RedirectResponse
    {
        if (! $this->canManageContracts()) {
            abort(403);
        }

        $employee = $contract->employee;
        if (! $employee) {
            return back()->with('error', 'Không tìm thấy thông tin nhân viên.');
        }

        $payroll = $employee->payrolls()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (! $payroll) {
            return back()->with('error', 'Nhân viên chưa có bảng lương nào để đồng bộ.');
        }

        // Đồng bộ trực tiếp vào hợp đồng hiện tại (bất kể trạng thái),
        // không chỉ active/expiring — vì user đang ở trang show của đúng hợp đồng đó
        $updated = $contractService->syncSalaryToContract(Auth::user(), $contract, $payroll);

        return back()->with('success', sprintf(
            'Đã cập nhật lương hợp đồng [%s] theo bảng lương T%s/%s: lương CB %s₫, phụ cấp %s₫.',
            $updated->contract_code,
            $payroll->month,
            $payroll->year,
            number_format($updated->base_salary, 0, ',', '.'),
            number_format($updated->allowance,   0, ',', '.')
        ));
    }

    private function validateDepartment(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manager' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function validateEmployee(Request $request, ?int $employeeId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email,' . $employeeId],
            'position' => ['nullable', 'string', 'max:255'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive'],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'cccd' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'education' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string'],
            'leave_balance' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function prepareContractData(ContractFormRequest $request, ?Contract $contract = null): array
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? \App\Models\Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE;
        $data['contract_status'] = $data['status'];

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document');
        }

        $employee = $request->employee_id ? Employee::find($request->employee_id) : null;
        if ($employee) {
            $data['employee_id'] = $employee->id;
        }

        $data['allowance'] = (float) ($data['allowance'] ?? 0);
        $data['bonus'] = (float) ($data['bonus'] ?? 0);

        return $data;
    }

    private function resolveContractStatus(?string $startDate, ?string $endDate): string
    {
        if (! $startDate) {
            return 'pending';
        }

        $start = now()->parse($startDate);
        $end = $endDate ? now()->parse($endDate) : null;
        $today = now()->startOfDay();

        if ($start->gt($today)) {
            return 'pending';
        }

        if ($end && $end->lt($today)) {
            return 'expired';
        }

        return 'active';
    }

    private function generateContractCode(): string
    {
        $nextId = (int) (Contract::max('id') ?? 0) + 1;

        return 'HD-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function resolveContractBaseSalary(?string $position): int
    {
        return match ($position) {
            'Giám Đốc' => 13000000,
            'Trưởng Phòng Nhân Sự' => 10400000,
            default => 7800000,
        };
    }

    private function hasActiveContract(int $employeeId, ?int $excludeId): bool
    {
        $query = Contract::where('employee_id', $employeeId)
            ->whereIn('status', [\App\Models\Contract::STATUS_ACTIVE, \App\Models\Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE, \App\Models\Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, 'expiring'])
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function canManageContracts(): bool
    {
        $user = Auth::user();

        return $user && ($user->is_admin || $user->is_hr);
    }

    private function canSignContract(?User $user, ?Employee $employee, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        if ($employee && $contract->employee_id === $employee->id) {
            return true;
        }

        return false;
    }

    private function validateAttendance(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,absent,late,leave'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function syncDepartmentCount(?int $departmentId): void
    {
        if (! $departmentId) {
            return;
        }

        Department::whereKey($departmentId)->update([
            'employee_count' => Employee::where('department_id', $departmentId)->count(),
        ]);
    }

    public function getPositionByName(string $name)
    {
        $position = Position::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($name) . '%'])
            ->first();

        if (! $position) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $position->id,
            'name' => $position->name,
            'salary_range_min' => $position->salary_range_min,
            'salary_range_max' => $position->salary_range_max,
            'allowance' => $position->allowance,
            'base_salary' => $position->base_salary,
            'level' => $position->level,
        ]);
    }

    public function getPositionById(int $id)
    {
        $position = Position::find($id);

        if (! $position) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $position->id,
            'name' => $position->name,
            'salary_range_min' => $position->salary_range_min,
            'salary_range_max' => $position->salary_range_max,
            'allowance' => $position->allowance,
            'base_salary' => $position->base_salary,
            'level' => $position->level,
        ]);
    }

    public function getNextEmployeeCode(Request $request)
    {
        $departmentId = $request->query('department_id');
        
        if (!$departmentId) {
            return response()->json(['error' => 'department_id is required'], 400);
        }
        
        $department = Department::find($departmentId);
        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }
        
        $code = Employee::generateUniqueEmployeeCode($department);
        return response()->json(['code' => $code]);
    }

    private function generateEmployeeCode(): string
    {
        $prefix = 'NV-' . now()->format('Ym') . '-';
        $lastEmployee = Employee::where('employee_code', 'like', $prefix . '%')
            ->orderByDesc('employee_code')
            ->first();

        if ($lastEmployee && preg_match('/(\d+)$/', $lastEmployee->employee_code, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the authenticated user is HR or Admin
     * This is a simple check - can be replaced with proper Role/Permission system
     */
    private function isAdmin(): bool
    {
        return Auth::user()?->is_admin === true;
    }

    private function isHr(): bool
    {
        return Auth::user()?->is_hr === true;
    }

    private function isHROrAdmin(): bool
    {
        return $this->isAdmin() || $this->isHr();
    }
}
