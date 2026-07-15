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
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Mail\PayrollConfirmationMail;
use App\Services\PayrollCalculationService;
use Illuminate\Support\Facades\Mail;

class SmartHrController extends Controller
{
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
            return view('admin.dashboard', [
                'departmentCount' => Department::count(),
                'employeeCount' => Employee::count(),
                'contractCount' => Contract::count(),
                'latestEmployees' => Employee::with('department')->latest()->take(5)->get(),
                'latestContracts' => Contract::with('employee')->latest()->take(5)->get(),
            ]);
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
            Employee::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
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
        return view('employees.form', [
            'employee' => new Employee(['status' => 'active']),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $employee = Employee::create($this->validateEmployee($request));
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

    public function evaluations(): View
    {
        return view('hr.evaluations.index', [
            'evaluations' => EmployeeEvaluation::with(['employee', 'evaluator'])->latest()->paginate(10),
        ]);
    }

    public function createEvaluation(): View
    {
        return view('hr.evaluations.form', [
            'evaluation' => new EmployeeEvaluation(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storeEvaluation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'date_format:Y-m', Rule::unique('employee_evaluations')->where(function ($query) use ($request) {
                return $query->where('employee_id', $request->input('employee_id'));
            })],
            'rating' => ['required', 'integer', 'between:1,5'],
            'punctuality' => ['required', 'integer', 'between:0,10'],
            'task_completion' => ['required', 'integer', 'between:0,30'],
            'quality' => ['required', 'integer', 'between:0,20'],
            'technical_skill' => ['required', 'integer', 'between:0,10'],
            'responsibility' => ['required', 'integer', 'between:0,10'],
            'teamwork' => ['required', 'integer', 'between:0,10'],
            'attitude' => ['required', 'integer', 'between:0,10'],
            'summary' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
        ]);

        $data['evaluator_id'] = Auth::id();
        $data['score_total'] = $this->calculateEvaluationTotal($data);
        $data['classification'] = $this->classifyEvaluationScore($data['score_total']);
        $data['status'] = 'pending';
        $data['approved_by'] = null;
        $data['approved_at'] = null;

        EmployeeEvaluation::create($data);

        return redirect()->route('evaluations.index')->with('success', 'Tạo đánh giá nhân viên thành công.');
    }

    public function editEvaluation(EmployeeEvaluation $evaluation): View
    {
        return view('hr.evaluations.form', [
            'evaluation' => $evaluation,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function updateEvaluation(Request $request, EmployeeEvaluation $evaluation): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'date_format:Y-m', Rule::unique('employee_evaluations')->where(function ($query) use ($request, $evaluation) {
                return $query->where('employee_id', $request->input('employee_id'))->where('id', '!=', $evaluation->id);
            })],
            'rating' => ['required', 'integer', 'between:1,5'],
            'punctuality' => ['required', 'integer', 'between:0,10'],
            'task_completion' => ['required', 'integer', 'between:0,30'],
            'quality' => ['required', 'integer', 'between:0,20'],
            'technical_skill' => ['required', 'integer', 'between:0,10'],
            'responsibility' => ['required', 'integer', 'between:0,10'],
            'teamwork' => ['required', 'integer', 'between:0,10'],
            'attitude' => ['required', 'integer', 'between:0,10'],
            'summary' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
        ]);

        $data['score_total'] = $this->calculateEvaluationTotal($data);
        $data['classification'] = $this->classifyEvaluationScore($data['score_total']);
        $data['status'] = 'pending';
        $data['approved_by'] = null;
        $data['approved_at'] = null;

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
        return view('hr.evaluations.show', [
            'evaluation' => $evaluation->load(['employee.department', 'evaluator', 'approvedBy']),
        ]);
    }

    public function approveEvaluation(EmployeeEvaluation $evaluation): RedirectResponse
    {
        $evaluation->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('evaluations.index')->with('success', 'Đã duyệt đánh giá thành công.');
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

        if ($payroll->status !== 'approved') {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Chỉ có thể đánh dấu đã thanh toán sau khi phiếu lương được chuyển sang trạng thái sẵn sàng thanh toán.');
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Đã đánh dấu phiếu lương là đã thanh toán.');
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
            'type' => ['required', 'in:sick,personal,annual,unpaid'],
            'reason' => ['nullable', 'string'],
        ]);

        $data['days'] = \Carbon\Carbon::parse($data['end_date'])
            ->diffInDays(\Carbon\Carbon::parse($data['start_date'])) + 1;
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
            return response()->json([
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'position' => $employee->position,
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
                'status' => 'pending',
                'contract_status' => 'pending',
                'base_salary' => 0,
                'allowance' => 0,
                'bonus' => 0,
            ]),
            'employees' => Employee::orderBy('name')->get(),
            'signers' => User::where('is_admin', true)->orWhere('is_hr', true)->orderBy('name')->get(),
            'isEdit' => false,
        ]);
    }

    public function storeContract(ContractFormRequest $request): RedirectResponse
    {
        $data = $this->prepareContractData($request, null);

        if ($this->hasActiveContract($data['employee_id'], null)) {
            return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng đang có hiệu lực.');
        }

        $contract = Contract::create($data);

        return redirect()->route('contracts.index')->with('success', 'Tạo hợp đồng thành công.');
    }

    public function showContract(Contract $contract): View
    {
        // Eager load related employee and department
        $contract->loadMissing(['employee.department']);

        // Latest payroll for the employee (if any)
        $payroll = null;
        if ($contract->employee) {
            $payroll = $contract->employee->payrolls()->latest()->first();
        }

        // Employee benefits (with benefit details)
        $benefits = collect();
        if ($contract->employee) {
            $benefits = $contract->employee->employeeBenefits()->with('benefit')->get();
        }

        // Days remaining until end_date (0 if already expired or no end_date)
        $daysRemaining = null;
        if ($contract->end_date) {
            $daysRemaining = max(0, now()->diffInDays($contract->end_date));
        }

        // Status badge class (uses classes declared in layout)
        $statusBadge = 'badge';
        if ($contract->status === 'expired' || $contract->end_date && $contract->end_date->isPast()) {
            $statusBadge = 'badge expired';
        } elseif ($contract->status === 'pending') {
            $statusBadge = 'badge pending';
        }

        return view('contracts.show', compact('contract', 'payroll', 'benefits', 'daysRemaining', 'statusBadge'));
    }

    public function editContract(Contract $contract): View
    {
        return view('contracts.form', [
            'contract' => $contract,
            'employees' => Employee::orderBy('name')->get(),
            'signers' => User::where('is_admin', true)->orWhere('is_hr', true)->orderBy('name')->get(),
            'isEdit' => true,
        ]);
    }

    public function updateContract(ContractFormRequest $request, Contract $contract): RedirectResponse
    {
        $data = $this->prepareContractData($request, $contract);

        if ($this->hasActiveContract($data['employee_id'], $contract->id)) {
            return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng đang có hiệu lực.');
        }

        $contract->update($data);

        return redirect()->route('contracts.index')->with('success', 'Cập nhật hợp đồng thành công.');
    }

    public function destroyContract(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Xóa hợp đồng thành công.');
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
            'position' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    private function prepareContractData(ContractFormRequest $request, ?Contract $contract = null): array
    {
        $data = $request->validated();

        $computedStatus = $this->resolveContractStatus($data['start_date'] ?? null, $data['end_date'] ?? null);
        $data['status'] = ($data['status'] ?? 'pending') === 'canceled' ? 'canceled' : $computedStatus;
        $data['contract_status'] = $data['status'];

        if (! empty($data['contract_code'])) {
            $data['contract_code'] = strtoupper($data['contract_code']);
        } elseif ($contract && $contract->contract_code) {
            $data['contract_code'] = $contract->contract_code;
        } else {
            $data['contract_code'] = $this->generateContractCode();
        }

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('contracts', 'public');
            $data['document_path'] = $path;
            $data['document_name'] = $file->getClientOriginalName();
        } elseif ($contract && $contract->document_path) {
            $data['document_path'] = $contract->document_path;
            $data['document_name'] = $contract->document_name;
        }

        $employee = $request->employee_id ? Employee::find($request->employee_id) : null;
        $position = $employee?->position ?? null;
        $baseSalary = $this->resolveContractBaseSalary($position);

        $data['salary'] = (int) $baseSalary;
        $data['base_salary'] = (float) $baseSalary;
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
            ->whereIn('status', ['active', 'pending'])
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
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
