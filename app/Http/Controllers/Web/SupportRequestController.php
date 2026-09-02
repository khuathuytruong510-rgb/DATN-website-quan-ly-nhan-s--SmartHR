<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\Employee;
use App\Services\SupportRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupportRequestController extends Controller
{
    public function __construct(protected SupportRequestService $service)
    {
    }

    public function index()
    {
        $employee = $this->currentEmployee();

        $requests = SupportRequest::where('employee_id', $employee->id)->latest()->paginate(12);

        return view('employee.support.index', compact('requests'));
    }

    public function create()
    {
        $employee = $this->currentEmployee();

        return view('employee.support.form', compact('employee'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $employee = $this->currentEmployee();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'in:payroll,attendance,document,personnel,other'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $this->service->submit($employee, $user, $data, $request->file('attachment'));

        return redirect()->route('me.support_requests')->with(
            'success',
            'Đã gửi yêu cầu hỗ trợ cho '.\App\Support\RequestApprover::queueLabel($employee).' duyệt.'
        );
    }

    public function followUp(Request $request, SupportRequest $supportRequest)
    {
        $employee = $this->currentEmployee();

        if ($supportRequest->employee_id !== $employee->id) {
            abort(403);
        }

        $data = $request->validate([
            'follow_up' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($supportRequest, $employee, $data) {
                $row = SupportRequest::query()->whereKey($supportRequest->id)->lockForUpdate()->firstOrFail();
                if ((int) $row->employee_id !== (int) $employee->id) {
                    abort(403);
                }
                if (! in_array($row->status, [SupportRequest::PENDING, SupportRequest::PROCESSING], true)) {
                    throw new RuntimeException('Không thể bổ sung nội dung khi yêu cầu đã giải quyết.');
                }

                $row->update([
                    'follow_up' => trim(($row->follow_up ? $row->follow_up."\n---\n" : '').$data['follow_up']),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã bổ sung nội dung. Bạn không tự đóng yêu cầu.');
    }

    public function feedback(Request $request, SupportRequest $supportRequest)
    {
        $user = auth()->user();
        $employee = $this->currentEmployee();

        if ($supportRequest->employee_id !== $employee->id) {
            abort(403);
        }

        $data = $request->validate([
            'employee_feedback' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->service->submitFeedback($supportRequest, $employee, $user, $data['employee_feedback']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi phản hồi cho HR.');
    }

    public function show(SupportRequest $supportRequest)
    {
        $employee = $this->currentEmployee();

        if ($supportRequest->employee_id !== $employee->id) {
            abort(403);
        }

        return view('employee.support.show', compact('supportRequest'));
    }

    private function currentEmployee(): Employee
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee() ?? Employee::where('email', $user?->email)->first();
        if (! $employee) {
            abort(404);
        }

        return $employee;
    }
}
