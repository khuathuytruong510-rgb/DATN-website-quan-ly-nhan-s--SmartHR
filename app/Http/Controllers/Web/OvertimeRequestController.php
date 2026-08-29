<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class OvertimeRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $items = OvertimeRequest::where('employee_id', $employee->id)->latest()->paginate(12);

        return view('employee.overtime.index', ['requests' => $items]);
    }

    public function create()
    {
        return view('employee.overtime.form');
    }

    public function store(StoreOvertimeRequest $request)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        $data = $request->validated();

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'overtime_submitted',
            'meta' => ($data['date'] ?? '').' '.$data['start_time'].'–'.$data['end_time'],
        ]);

        return redirect()->route('me.overtime_requests')->with('success', 'Đã gửi đăng ký tăng ca. Thời gian đăng ký chưa phải giờ tính lương — HR/Kế toán đối soát theo chấm công.');
    }

    public function show(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        $employee = Employee::where('email', $user->email)->firstOrFail();

        if ($overtimeRequest->employee_id !== $employee->id) {
            abort(403);
        }

        return view('employee.overtime.show', compact('overtimeRequest'));
    }
}
