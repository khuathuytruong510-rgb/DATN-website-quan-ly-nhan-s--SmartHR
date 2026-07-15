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
        $data['employee_id'] = $employee->id;

        OvertimeRequest::create($data + ['status' => 'pending']);

        return redirect()->route('me.overtime_requests')->with('success', 'Đã gửi yêu cầu tăng ca.');
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
