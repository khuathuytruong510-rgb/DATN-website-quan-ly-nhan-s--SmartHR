<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\DeletionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DeletionRequestController extends Controller
{
    public function __construct(private readonly DeletionRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->assertViewer();

        $query = DeletionRequest::with('submittedBy', 'reviewedBy', 'appliedBy')->latest();

        if (in_array($request->query('status'), [
            DeletionRequest::STATUS_PENDING,
            DeletionRequest::STATUS_APPROVED,
            DeletionRequest::STATUS_APPLIED,
            DeletionRequest::STATUS_REJECTED,
            DeletionRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $request->query('status'));
        }

        if (in_array($request->query('kind'), [DeletionRequest::KIND_EMPLOYEE, DeletionRequest::KIND_DEPARTMENT], true)) {
            $query->where('kind', $request->query('kind'));
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return view('hr.deletion_requests.index', [
            'requests' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'kind', 'search']),
        ]);
    }

    public function show(DeletionRequest $deletionRequest): View
    {
        $this->assertViewer();

        return view('hr.deletion_requests.show', [
            'request' => $deletionRequest->load(['submittedBy', 'reviewedBy', 'appliedBy']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->assertManager();

        $kind = $request->query('kind');
        $targetId = (int) $request->query('target');

        if (! in_array($kind, [DeletionRequest::KIND_EMPLOYEE, DeletionRequest::KIND_DEPARTMENT], true)) {
            abort(404);
        }

        $target = $kind === DeletionRequest::KIND_EMPLOYEE
            ? Employee::find($targetId)
            : Department::find($targetId);

        if (! $target) {
            abort(404);
        }

        $pending = DeletionRequest::where('kind', $kind)
            ->where('requestable_id', $target->id)
            ->where('requestable_type', get_class($target))
            ->where('status', DeletionRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            abort(422, $target->name.' đang có yêu cầu xóa chờ Giám đốc duyệt.');
        }

        return view('hr.deletion_requests.create', [
            'kind' => $kind,
            'target' => $target,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertManager();

        $data = $request->validate([
            'kind' => ['required', 'in:employee,department'],
            'target' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $deletionRequest = $this->service->submit(
                $request->user(),
                $data['kind'],
                (int) $data['target'],
                $data['reason']
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)
            ->with('success', 'Yêu cầu xóa đã gửi tới Giám đốc duyệt.');
    }

    public function approve(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->assertApprover();

        $note = trim((string) $request->input('review_note'));

        try {
            $this->service->approve($request->user(), $deletionRequest, $note ?: null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)
            ->with('success', 'Đã duyệt. HR sẽ thực hiện xóa.');
    }

    public function reject(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->assertApprover();

        $data = $request->validate([
            'review_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->service->reject($request->user(), $deletionRequest, $data['review_note']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)
            ->with('success', 'Đã từ chối yêu cầu xóa.');
    }

    public function execute(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->assertManager();

        try {
            $this->service->execute($request->user(), $deletionRequest);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)
            ->with('success', 'Đã thực hiện xóa.');
    }

    public function cancel(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->assertManager();

        $note = trim((string) $request->input('cancellation_note'));

        try {
            $this->service->cancel($request->user(), $deletionRequest, $note ?: null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('deletion_requests.show', $deletionRequest)
            ->with('success', 'Đã hủy yêu cầu xóa.');
    }

    protected function assertViewer(): void
    {
        abort_if(! DeletionRequestService::actorCanView(request()->user()), 403, 'Bạn không có quyền xem yêu cầu xóa.');
    }

    protected function assertManager(): void
    {
        abort_if(! DeletionRequestService::actorCanManage(request()->user()), 403, 'Chỉ HR/Admin được tạo hoặc thực hiện yêu cầu xóa.');
    }

    protected function assertApprover(): void
    {
        abort_if(! DeletionRequestService::actorCanApprove(request()->user()), 403, 'Chỉ Giám đốc mới được duyệt yêu cầu xóa.');
    }
}