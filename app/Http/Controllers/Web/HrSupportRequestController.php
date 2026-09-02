<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SupportRequest;
use App\Services\SupportRequestService;
use App\Support\RequestApprover;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HrSupportRequestController extends Controller
{
    public function __construct(protected SupportRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->assertCanView();

        $user = $request->user();
        $status = $request->query('status');
        $query = SupportRequest::with(['employee.user'])->latest();
        if (in_array($status, [SupportRequest::PENDING, SupportRequest::PROCESSING, SupportRequest::RESOLVED], true)) {
            $query->where('status', $status);
        }

        $visibleIds = Employee::query()->with('user')->get()
            ->filter(fn (Employee $employee) => RequestApprover::canReview($user, $employee))
            ->pluck('id');
        $query->whereIn('employee_id', $visibleIds->isEmpty() ? [0] : $visibleIds->all());

        return view('hr.support.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'reviewer' => $user,
        ]);
    }

    public function show(SupportRequest $supportRequest): View
    {
        $this->assertCanView();
        $supportRequest->load('employee.user');
        $user = request()->user();
        $canReview = RequestApprover::canReview($user, $supportRequest->employee);
        if ($user?->canManageHr() && ! $canReview) {
            abort(403, 'HR không quản lý yêu cầu của Giám đốc hoặc yêu cầu do Giám đốc xử lý.');
        }

        return view('hr.support.show', [
            'ticket' => $supportRequest,
            'canReview' => $canReview,
            'handlerLabel' => RequestApprover::queueLabel($supportRequest->employee),
        ]);
    }

    public function approve(SupportRequest $supportRequest): RedirectResponse
    {
        $supportRequest->loadMissing('employee');
        abort_unless(
            RequestApprover::canReview(request()->user(), $supportRequest->employee),
            403,
            RequestApprover::isDirectorEmployee($supportRequest->employee)
                ? 'HR không quản lý yêu cầu của Giám đốc.'
                : (RequestApprover::needsDirector($supportRequest->employee)
                    ? 'Yêu cầu hỗ trợ của HR do Giám đốc duyệt và xử lý.'
                    : 'Chỉ HR được duyệt yêu cầu hỗ trợ của nhân viên.')
        );

        try {
            $this->service->approve($supportRequest, request()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('support_requests.show', $supportRequest)
            ->with('success', 'Đã duyệt. Người gửi đã được thông báo. Tiếp tục xử lý rồi bấm Đã xử lý.');
    }

    public function resolve(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $supportRequest->loadMissing('employee');
        abort_unless(
            RequestApprover::canReview($request->user(), $supportRequest->employee),
            403,
            RequestApprover::isDirectorEmployee($supportRequest->employee)
                ? 'HR không quản lý yêu cầu của Giám đốc.'
                : (RequestApprover::needsDirector($supportRequest->employee)
                    ? 'Yêu cầu hỗ trợ của HR do Giám đốc xử lý.'
                    : 'Chỉ HR được xử lý yêu cầu hỗ trợ của nhân viên.')
        );

        $data = $request->validate([
            'hr_reply' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            $this->service->resolve($supportRequest, $request->user(), $data['hr_reply'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('support_requests.show', $supportRequest)
            ->with('success', 'Đã xử lý. Người gửi đã được thông báo và có thể gửi phản hồi.');
    }

    private function assertCanView(): void
    {
        $user = request()->user();
        if (! $user || (! $user->canManageHr() && ! $user->is_director)) {
            abort(403);
        }
    }
}
