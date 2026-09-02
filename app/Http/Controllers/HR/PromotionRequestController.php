<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\PromotionRequest;
use App\Services\ContractService;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionRequestController extends Controller
{
    public function __construct(protected PromotionService $service)
    {
    }

    protected function actorManageOK(): bool
    {
        $user = request()->user();

        return $user !== null && ($user->is_hr || $user->is_admin);
    }

    protected function actorDirectorOK(): bool
    {
        $user = request()->user();

        return $user !== null && $user->is_director;
    }

    protected function assertManage(): void
    {
        if (! $this->actorManageOK()) {
            abort(403, 'Chỉ HR được tạo, áp dụng hoặc hủy đề xuất thăng chức/tăng lương.');
        }
    }

    protected function assertDirector(): void
    {
        if (! $this->actorDirectorOK()) {
            abort(403, 'Chỉ Giám đốc được duyệt đề xuất thăng chức/tăng lương.');
        }
    }

    public function index(Request $request): View
    {
        $query = PromotionRequest::with(['employee.department', 'submittedBy', 'reviewedBy', 'appliedBy']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('employee_code', 'like', '%'.$search.'%');
            });
        }

        $requests = $query
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'applied', 'rejected', 'cancelled')")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('hr.promotions.index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'changeTypeLabels' => [
                PromotionRequest::CHANGE_PROMOTION => 'Thăng chức',
                PromotionRequest::CHANGE_SALARY_RAISE => 'Tăng lương',
                PromotionRequest::CHANGE_BOTH => 'Thăng chức & tăng lương',
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->assertManage();

        $employees = Employee::with(['department', 'positionDetail', 'contracts'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $positions = Position::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $selectedEmployeeId = $request->input('employee_id') ? (int) $request->input('employee_id') : null;

        return view('hr.promotions.create', compact('employees', 'positions', 'departments', 'selectedEmployeeId'))
            ->with('changeTypes', [
                PromotionRequest::CHANGE_PROMOTION => 'Thăng chức',
                PromotionRequest::CHANGE_SALARY_RAISE => 'Tăng lương',
                PromotionRequest::CHANGE_BOTH => 'Thăng chức & tăng lương',
            ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertManage();

        $data = $this->validateRequest($request);

        try {
            $promotion = $this->service->create($request->user(), Employee::findOrFail($data['employee_id']), $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('promotion_requests.show', $promotion)
            ->with('success', 'Đã tạo đề xuất, gửi Giám đốc duyệt.');
    }

    public function show(PromotionRequest $promotionRequest): View
    {
        $promotionRequest->load([
            'employee.department',
            'newPosition',
            'oldPosition',
            'department',
            'submittedBy',
            'reviewedBy',
            'appliedBy',
        ]);

        return view('hr.promotions.show', [
            'promotion' => $promotionRequest,
        ]);
    }

    public function approve(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->assertDirector();

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->service->approve(
                $request->user(),
                $promotionRequest,
                $data['review_note'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã duyệt đề xuất. Hệ thống đã cập nhật mức lương, ghi lịch sử và thông báo nhân viên.');
    }

    public function reject(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->assertDirector();

        $data = $request->validate([
            'review_note' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->service->reject(
                $request->user(),
                $promotionRequest,
                $data['review_note']
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã từ chối đề xuất.');
    }

    public function apply(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->assertManage();

        try {
            $this->service->apply($request->user(), $promotionRequest);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('promotion_requests.show', $promotionRequest)
            ->with('success', 'Đã áp dụng đề xuất: cập nhật chức vụ/hợp đồng và ghi lịch sử lương.');
    }

    public function cancel(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->assertManage();

        $data = $request->validate([
            'cancellation_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->service->cancel(
                $request->user(),
                $promotionRequest,
                $data['cancellation_note'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy đề xuất.');
    }

    protected function validateRequest(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'change_type' => ['required', 'in:promotion,salary_raise,both'],
            'new_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'new_position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'old_base_salary' => ['nullable', 'numeric', 'min:0'],
            'new_base_salary' => ['required', 'numeric', 'min:1'],
            'old_allowance' => ['nullable', 'numeric', 'min:0'],
            'new_allowance' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'document_number' => ['nullable', 'string', 'max:100'],
        ]);

        if (in_array($data['change_type'], ['promotion', 'both'], true) && empty($data['new_position'])) {
            $data['new_position'] = Position::find($data['new_position_id'] ?? 0)?->name;
            if (empty($data['new_position'])) {
                abort(422, 'Vui lòng chọn chức vụ mới cho đề xuất thăng chức.');
            }
        }

        $newPositionId = $data['new_position_id'] ?? null;
        if ($newPositionId) {
            $targetDeptId = $data['department_id'] ?? (int) Employee::whereKey($data['employee_id'])->value('department_id');
            if ($targetDeptId) {
                $posDeptId = (int) Position::whereKey($newPositionId)->value('department_id');
                if ($posDeptId && $posDeptId !== (int) $targetDeptId) {
                    abort(422, 'Chức vụ mới không thuộc phòng ban đã chọn (hoặc phòng ban hiện tại của nhân viên).');
                }
            }
        }

        return $data;
    }
}