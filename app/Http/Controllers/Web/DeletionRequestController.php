<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\DeletionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeletionRequestController extends Controller
{
    public function __construct(protected DeletionRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->assertCanView();

        $status = $request->query('status');
        $query = DeletionRequest::with(['requester', 'reviewer'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('id');

        if (in_array($status, [DeletionRequest::PENDING, DeletionRequest::APPROVED, DeletionRequest::REJECTED], true)) {
            $query->where('status', $status);
        }

        return view('hr.deletions.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'canReview' => (bool) $request->user()?->is_director,
            'canManageHr' => (bool) $request->user()?->canManageHr(),
        ]);
    }

    public function show(DeletionRequest $deletionRequest): View
    {
        $this->assertCanView();
        $deletionRequest->load(['requester', 'reviewer']);

        return view('hr.deletions.show', [
            'deletionRequest' => $deletionRequest,
            'canReview' => (bool) request()->user()?->is_director && $deletionRequest->isPending(),
        ]);
    }

    public function createEmployee(Employee $employee): View|RedirectResponse
    {
        $this->assertHr();
        abort_unless(\App\Support\RequestApprover::hrMayManage(auth()->user(), $employee), 403, 'HR không quản lý hồ sơ Giám đốc.');

        if ($employee->isTerminated()) {
            return redirect()
                ->route('employees.show', $employee)
                ->with('error', 'Nhân viên đã nghỉ việc. Hồ sơ lịch sử vẫn được lưu.');
        }

        if ($pending = $this->service->pendingFor(DeletionRequest::EMPLOYEE, $employee->id)) {
            return redirect()
                ->route('deletion_requests.show', $pending)
                ->with('error', 'Đã có đề nghị nghỉ việc của nhân viên này đang chờ Giám đốc duyệt.');
        }
        if ($transferId = $this->service->pendingTransferIdForEmployee($employee->id)) {
            return redirect()
                ->route('deletion_requests.show', $transferId)
                ->with('error', 'Nhân viên này đang chờ Giám đốc duyệt chuyển phòng ban.');
        }

        $employee->loadMissing('user', 'department');

        return view('hr.deletions.form', [
            'subjectType' => DeletionRequest::EMPLOYEE,
            'employee' => $employee,
            'department' => null,
            'action' => route('deletion_requests.store_employee', $employee),
        ]);
    }

    public function createDepartment(Department $department): View|RedirectResponse
    {
        $this->assertHr();

        if ($department->isBoard()) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Không xóa hoặc điều chuyển Ban Giám đốc.');
        }

        if ($pending = $this->service->pendingFor(DeletionRequest::DEPARTMENT, $department->id)) {
            return redirect()
                ->route('deletion_requests.show', $pending)
                ->with('error', 'Đã có yêu cầu xóa phòng ban này đang chờ Giám đốc duyệt.');
        }

        $employees = $department->employees()
            ->whereIn('status', Employee::workingStatuses())
            ->orderBy('name')
            ->get()
            ->reject(fn (Employee $row) => \App\Support\RequestApprover::isDirectorEmployee($row));

        return view('hr.deletions.form', [
            'subjectType' => DeletionRequest::DEPARTMENT,
            'employee' => null,
            'department' => $department,
            'action' => route('deletion_requests.store_department', $department),
            'employees' => $employees,
            'otherDepartments' => Department::query()
                ->notBoard()
                ->where('id', '!=', $department->id)
                ->orderBy('name')
                ->get(),
            'pendingEmployeeDeletions' => DeletionRequest::query()
                ->where('subject_type', DeletionRequest::EMPLOYEE)
                ->where('status', DeletionRequest::PENDING)
                ->whereIn('subject_id', $employees->pluck('id'))
                ->pluck('id', 'subject_id'),
            'pendingEmployeeTransfers' => $this->service->pendingTransferMap($employees->pluck('id')->all()),
        ]);
    }

    public function storeEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $this->assertHr();
        $data = $this->validatedEvidence($request);

        try {
            $this->service->submitEmployee(
                $employee,
                $request->user(),
                $data['reason'] ?? null,
                $request->file('document'),
                $data['last_working_day'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('deletion_requests.index')
            ->with('success', 'Đã gửi đề nghị nghỉ việc cho Giám đốc duyệt. Hồ sơ chưa bị khóa.');
    }

    public function storeDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->assertHr();
        $data = $this->validatedEvidence($request);

        try {
            $this->service->submitDepartment(
                $department,
                $request->user(),
                $data['reason'] ?? null,
                $request->file('document')
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('deletion_requests.index')
            ->with('success', 'Đã gửi đề nghị xóa phòng ban cho Giám đốc duyệt.');
    }

    public function transferEmployees(Request $request, Department $department): RedirectResponse
    {
        $this->assertHr();

        $data = $request->validate([
            'target_department_id' => ['required', 'integer', 'exists:departments,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'transfer_all' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        if ((int) $department->id === (int) $data['target_department_id']) {
            return back()->withInput()->withErrors([
                'target_department_id' => 'Phòng ban đích phải khác phòng ban hiện tại.',
            ]);
        }

        $target = Department::findOrFail($data['target_department_id']);
        $ids = $request->boolean('transfer_all')
            ? $department->employees()->whereIn('status', Employee::workingStatuses())->pluck('id')->all()
            : ($data['employee_ids'] ?? []);

        try {
            $this->service->transferEmployees(
                $department,
                $target,
                $ids,
                $request->user(),
                $data['reason'] ?? null,
                $request->file('document')
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('deletion_requests.index')
            ->with('success', 'Đã gửi đề nghị chuyển nhân viên cho Giám đốc duyệt. Hồ sơ vẫn thuộc phòng ban hiện tại cho đến khi được duyệt.');
    }

    public function approve(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($request->user()?->is_director, 403, 'Chỉ Giám đốc được duyệt yêu cầu này.');

        try {
            $this->service->approve($deletionRequest, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $deletionRequest->fresh();
        if ($fresh?->isTransfer()) {
            $message = 'Đã duyệt điều chuyển. Hồ sơ nhân viên đã cập nhật phòng ban đích.';
        } elseif ($fresh?->isEmployee()) {
            $message = 'Đã duyệt nghỉ việc. Hồ sơ lịch sử được giữ lại, hợp đồng đã chấm dứt, tài khoản đăng nhập đã khóa.';
        } else {
            $message = 'Đã xóa phòng ban và lưu vào lịch sử.';
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)->with('success', $message);
    }

    public function reject(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($request->user()?->is_director, 403, 'Chỉ Giám đốc được từ chối yêu cầu này.');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->service->reject($deletionRequest, $request->user(), $data['rejection_reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $deletionRequest->fresh();

        return redirect()->route('deletion_requests.show', $deletionRequest)->with(
            'success',
            $fresh?->isTransfer()
                ? 'Đã từ chối điều chuyển. Hồ sơ nhân viên giữ nguyên phòng ban hiện tại.'
                : ($fresh?->isEmployee()
                    ? 'Đã từ chối nghỉ việc. Nhân viên tiếp tục làm việc.'
                    : 'Đã từ chối yêu cầu xóa phòng ban.')
        );
    }

    public function replyTransferFeedback(Request $request, DeletionRequest $deletionRequest, int $employee): RedirectResponse
    {
        $this->assertHr();

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->service->replyTransferFeedback($deletionRequest, $employee, $request->user(), $data['reply']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi phản hồi giải quyết cho nhân viên.');
    }

    public function document(DeletionRequest $deletionRequest): StreamedResponse
    {
        $this->assertCanView();
        abort_unless($deletionRequest->document_path && Storage::disk('public')->exists($deletionRequest->document_path), 404);

        return Storage::disk('public')->download(
            $deletionRequest->document_path,
            $deletionRequest->document_name ?: basename($deletionRequest->document_path)
        );
    }

    private function validatedEvidence(Request $request): array
    {
        return $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'last_working_day' => ['nullable', 'date'],
        ]);
    }

    private function assertHr(): void
    {
        abort_unless(request()->user()?->canManageHr(), 403, 'Chỉ HR được gửi đề nghị.');
    }

    public function createTransfer(Request $request): View|RedirectResponse
    {
        $this->assertHr();

        $candidates = Employee::query()
            ->with(['department', 'positionDetail'])
            ->whereIn('status', Employee::workingStatuses())
            ->orderBy('name')
            ->get()
            ->reject(fn (Employee $row) => \App\Support\RequestApprover::isDirectorProfile($row))
            ->values();

        $departments = Department::query()->orderBy('name')->get();
        $fromFilterId = $request->integer('from') ?: null;

        $employee = null;
        if ($request->filled('employee')) {
            $employee = Employee::with(['department', 'positionDetail'])->find($request->integer('employee'));
            if ($employee && ! \App\Support\RequestApprover::hrMayManage($request->user(), $employee)) {
                abort(403, 'HR không quản lý hồ sơ Giám đốc.');
            }
            if ($employee && $this->service->pendingFor(DeletionRequest::EMPLOYEE, $employee->id)) {
                return redirect()
                    ->route('deletion_requests.show', $this->service->pendingFor(DeletionRequest::EMPLOYEE, $employee->id))
                    ->with('error', 'Nhân viên đang chờ Giám đốc duyệt nghỉ việc.');
            }
            if ($employee && $transferId = $this->service->pendingTransferIdForEmployee($employee->id)) {
                return redirect()
                    ->route('deletion_requests.show', $transferId)
                    ->with('error', 'Nhân viên này đang chờ Giám đốc duyệt chuyển phòng ban.');
            }
            if ($employee && ! $fromFilterId) {
                $fromFilterId = $employee->department_id ? (int) $employee->department_id : null;
            }
        }

        $filteredCandidates = $fromFilterId
            ? $candidates->where('department_id', $fromFilterId)->values()
            : collect();

        $from = $employee?->department;

        return view('hr.deletions.transfer', [
            'from' => $from,
            'employee' => $employee,
            'candidates' => $candidates,
            'filteredCandidates' => $filteredCandidates,
            'departments' => $departments,
            'fromFilterId' => $fromFilterId,
            'otherDepartments' => $departments
                ->when($from, fn ($rows) => $rows->where('id', '!=', $from->id)->values())
                ->values(),
        ]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        $this->assertHr();

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'target_department_id' => ['required', 'integer', 'exists:departments,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $employee = Employee::query()->with('department')->findOrFail((int) $data['employee_id']);
        abort_unless(\App\Support\RequestApprover::hrMayManage($request->user(), $employee), 403, 'HR không quản lý hồ sơ Giám đốc.');

        // Phòng nguồn luôn lấy từ hồ sơ nhân viên. Bỏ qua source_department_id / from_department_id nếu client gửi lên.

        if (! $employee->department_id) {
            return back()->withInput()->with(
                'error',
                'Nhân viên chưa được phân công phòng ban. Vui lòng cập nhật hồ sơ nhân viên trước khi tạo yêu cầu điều chuyển.'
            );
        }

        if ((int) $employee->department_id === (int) $data['target_department_id']) {
            return back()->withInput()->withErrors([
                'target_department_id' => 'Phòng ban đích phải khác phòng ban hiện tại.',
            ]);
        }

        $from = $employee->department;
        $target = Department::findOrFail($data['target_department_id']);

        try {
            $this->service->transferEmployees(
                $from,
                $target,
                [$employee->id],
                $request->user(),
                $data['reason'] ?? null,
                $request->file('document')
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('deletion_requests.index')
            ->with('success', 'Đã gửi đề nghị chuyển nhân viên cho Giám đốc duyệt. Hồ sơ vẫn thuộc phòng ban hiện tại cho đến khi được duyệt.');
    }

    private function assertCanView(): void
    {
        $user = request()->user();
        abort_unless($user && ($user->is_hr || $user->is_director || $user->is_admin), 403);
    }
}
