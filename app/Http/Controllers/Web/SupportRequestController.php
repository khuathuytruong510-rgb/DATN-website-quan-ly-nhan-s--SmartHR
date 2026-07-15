<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'type' => ['required', 'in:payroll,attendance,document,other'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('support_attachments');
            $data['attachment'] = $path;
        }

        $data['employee_id'] = $employee->id;

        SupportRequest::create($data + ['status' => 'pending']);

        return redirect()->route('me.support_requests')->with('success', 'Đã gửi yêu cầu hỗ trợ.');
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
