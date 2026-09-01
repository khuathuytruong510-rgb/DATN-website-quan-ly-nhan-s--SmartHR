<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryReceiveChangeRequest;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryReceiveChangeRequestController extends Controller
{
    public function __construct(protected PayrollPaymentWorkflowService $workflow)
    {
    }

    protected function assertHr(): void
    {
        $user = request()->user();
        if (! $user || ! $user->is_hr) {
            abort(403);
        }
    }

    public function index(): View
    {
        $this->assertHr();

        $requests = SalaryReceiveChangeRequest::with(['employee', 'reviewer'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('id')
            ->paginate(20);

        return view('hr.payroll.bank_requests', compact('requests'));
    }

    public function approve(Request $request, SalaryReceiveChangeRequest $changeRequest): RedirectResponse
    {
        $this->assertHr();

        try {
            $this->workflow->reviewBankChangeRequest(
                $changeRequest,
                true,
                $request->user(),
                $request->input('review_note')
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã duyệt yêu cầu. Thông tin nhận lương đã được cập nhật.');
    }

    public function reject(Request $request, SalaryReceiveChangeRequest $changeRequest): RedirectResponse
    {
        $this->assertHr();

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->workflow->reviewBankChangeRequest(
                $changeRequest,
                false,
                $request->user(),
                $data['review_note'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã từ chối yêu cầu.');
    }
}
