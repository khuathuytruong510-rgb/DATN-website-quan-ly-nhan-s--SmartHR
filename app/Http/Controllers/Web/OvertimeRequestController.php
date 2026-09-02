<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Services\OvertimeRequestService;
use App\Support\RequestApprover;

class OvertimeRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        if (! $employee) {
            abort(404);
        }

        $items = OvertimeRequest::where('employee_id', $employee->id)->latest()->paginate(12);

        return view('employee.overtime.index', [
            'requests' => $items,
            'employee' => $employee,
        ]);
    }

    public function create()
    {
        $employee = auth()->user()?->linkedEmployee();
        if (! $employee) {
            abort(404);
        }

        return view('employee.overtime.form', compact('employee'));
    }

    public function store(StoreOvertimeRequest $request)
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        if (! $employee) {
            abort(404);
        }

        try {
            app(OvertimeRequestService::class)->submit($employee, $user, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['date' => $e->getMessage()]);
        }

        return redirect()->route('me.overtime_requests')->with(
            'success',
            'Đã gửi đăng ký tăng ca cho '.RequestApprover::queueLabel($employee).' duyệt.'
        );
    }

    public function show(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        $employee = $user?->linkedEmployee();
        if (! $employee || $overtimeRequest->employee_id !== $employee->id) {
            abort(403);
        }

        return view('employee.overtime.show', compact('overtimeRequest'));
    }
}
