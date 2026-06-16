<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
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

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'departmentCount' => Department::count(),
            'employeeCount' => Employee::count(),
            'contractCount' => Contract::count(),
            'latestEmployees' => Employee::with('department')->latest()->take(5)->get(),
            'latestContracts' => Contract::with('employee')->latest()->take(5)->get(),
        ]);
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

    public function employees(): View
    {
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

    public function storeEmployee(Request $request): RedirectResponse
    {
        $employee = Employee::create($this->validateEmployee($request));
        $this->syncDepartmentCount($employee->department_id);

        return redirect()->route('employees.index')->with('success', 'Tạo nhân viên thành công.');
    }

    public function editEmployee(Employee $employee): View
    {
        return view('employees.form', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function updateEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $oldDepartmentId = $employee->department_id;
        $employee->update($this->validateEmployee($request, $employee->id));

        $this->syncDepartmentCount($oldDepartmentId);
        $this->syncDepartmentCount($employee->department_id);

        return redirect()->route('employees.index')->with('success', 'Cập nhật nhân viên thành công.');
    }

    public function destroyEmployee(Employee $employee): RedirectResponse
    {
        $departmentId = $employee->department_id;
        $employee->delete();
        $this->syncDepartmentCount($departmentId);

        return redirect()->route('employees.index')->with('success', 'Xóa nhân viên thành công.');
    }

    public function contracts(): View
    {
        return view('contracts.index', [
            'contracts' => Contract::with('employee')->latest()->paginate(10),
        ]);
    }

    public function attendance(): View
    {
        return view('hr.attendance.index', [
            'attendances' => \App\Models\Attendance::with('employee')->latest()->paginate(10),
        ]);
    }

    public function payroll(): View
    {
        return view('hr.payroll.index', [
            'payrolls' => Payroll::with('employee')->latest()->paginate(10),
        ]);
    }

    public function leaveRequests(Request $request): View
{
    $query = \App\Models\LeaveRequest::with('employee');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('type')) {
        $query->where('type', $request->type);
    }

    $leaveRequests = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('hr.leave.index', compact('leaveRequests'));
}

    public function createContract(): View
    {
        return view('contracts.form', [
            'contract' => new Contract(['status' => 'active']),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function storeContract(Request $request): RedirectResponse
    {
        Contract::create($this->validateContract($request));

        return redirect()->route('contracts.index')->with('success', 'Tạo hợp đồng thành công.');
    }

    public function editContract(Contract $contract): View
    {
        return view('contracts.form', [
            'contract' => $contract,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function updateContract(Request $request, Contract $contract): RedirectResponse
    {
        $contract->update($this->validateContract($request));

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

    private function validateContract(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,pending,expired'],
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

}
