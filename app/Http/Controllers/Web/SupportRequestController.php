<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $requests = SupportRequest::where('employee_id', $employee->id)->latest()->paginate(12);

        return view('employee.support.index', compact('requests'));
    }

    public function create()
    {
        return view('employee.support.form');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'in:payroll,attendance,document,personnel,other'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('support_attachments');
        }

        DB::transaction(function () use ($user, $employee, $data, $attachment) {
            SupportRequest::create([
                'employee_id' => $employee->id,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'type' => $data['type'],
                'attachment' => $attachment,
                'status' => SupportRequest::PENDING,
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'support_submitted',
                'meta' => $data['subject'],
            ]);
        });

        return redirect()->route('me.support_requests')->with('success', 'Đã gửi yêu cầu hỗ trợ.');
    }

    public function followUp(Request $request, SupportRequest $supportRequest)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

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
                    throw new \RuntimeException('Không thể bổ sung nội dung khi yêu cầu đã giải quyết.');
                }

                $row->update([
                    'follow_up' => trim(($row->follow_up ? $row->follow_up."\n---\n" : '').$data['follow_up']),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã bổ sung nội dung. Bạn không tự đóng yêu cầu.');
    }

    public function show(SupportRequest $supportRequest)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        if ($supportRequest->employee_id !== $employee->id) {
            abort(403);
        }

        return view('employee.support.show', compact('supportRequest'));
    }
}
