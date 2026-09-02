<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use App\Support\LeaveTypes;
use App\Traits\HasLeaveLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends ApiController
{
    use HasLeaveLimit;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = LeaveRequest::with('employee');

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 10), 50);
        return response()->json($query->paginate($perPage));
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $leave = LeaveRequest::with('employee')->findOrFail($id);
        return response()->json($leave);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'half_day' => 'nullable|boolean',
            'type' => 'required|string',
            'reason' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
            'urgent_reason' => 'required_if:is_urgent,1|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::findOrFail($validator->validated()['employee_id']);
        $typeValidator = Validator::make($request->all(), [
            'type' => 'required|'.LeaveTypes::validationRule($employee),
        ]);
        if ($typeValidator->fails()) {
            return response()->json(['errors' => $typeValidator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_urgent'] = filter_var($data['is_urgent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['half_day'] = filter_var($data['half_day'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $leave = app(LeaveRequestService::class)->submit(
                $employee,
                $request->user(),
                $data
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($leave, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Chỉ sửa đơn đang chờ duyệt.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'half_day' => 'nullable|boolean',
            'type' => 'sometimes|required|string',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $employee = isset($data['employee_id'])
            ? Employee::findOrFail($data['employee_id'])
            : $leave->employee;
        if (array_key_exists('type', $data)) {
            $typeValidator = Validator::make($data, [
                'type' => 'required|'.LeaveTypes::validationRule($employee),
            ]);
            if ($typeValidator->fails()) {
                return response()->json(['errors' => $typeValidator->errors()], 422);
            }
        }
        unset($data['status'], $data['approved_by'], $data['approved_at']);
        if (isset($data['half_day'])) {
            $data['half_day'] = filter_var($data['half_day'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $halfDay = $data['half_day'] ?? $leave->half_day ?? false;
            $data['days'] = $this->calculateLeaveDays($data['start_date'], $data['end_date'], $halfDay);
        }

        try {
            app(\App\Services\PayrollPeriodLockService::class)->assertWritableRange(
                $data['start_date'] ?? $leave->start_date,
                $data['end_date'] ?? $leave->end_date,
                'đơn nghỉ phép'
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $leave->update($data);
        return response()->json($leave);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);
        $leave = LeaveRequest::findOrFail($id);
        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Chỉ xóa đơn đang chờ duyệt.'], 422);
        }
        try {
            app(\App\Services\PayrollPeriodLockService::class)->assertWritableRange(
                $leave->start_date,
                $leave->end_date,
                'đơn nghỉ phép'
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $leave->delete();

        return response()->json(null, 204);
    }
}
