<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\LeaveRequest;
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
        $this->currentUser($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:sick,personal,annual,unpaid',
            'reason' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
            'urgent_reason' => 'required_if:is_urgent,1|nullable|string|max:500',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_urgent'] = filter_var($data['is_urgent'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $limitCheck = $this->checkLeaveLimit(
            $data['employee_id'],
            $data['start_date'],
            $data['end_date']
        );

        if ($limitCheck['exceeded'] && empty($data['is_urgent'])) {
            return response()->json([
                'errors' => [
                    'leave_limit' => [
                        "Nhân viên đã sử dụng {$limitCheck['used_days']}/{$limitCheck['max_days']} ngày nghỉ phép trong tháng này. " .
                        "Vui lòng liên hệ bộ phận hỗ trợ nếu cần nghỉ thêm với lý do thuyết phục."
                    ]
                ]
            ], 422);
        }

        $data['days'] = \Carbon\Carbon::parse($data['end_date'])
                        ->diffInDays(\Carbon\Carbon::parse($data['start_date'])) + 1;

        $leave = LeaveRequest::create($data);
        return response()->json($leave, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $leave = LeaveRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'type' => 'sometimes|required|in:sick,personal,annual,unpaid',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $data['days'] = \Carbon\Carbon::parse($data['end_date'])
                            ->diffInDays(\Carbon\Carbon::parse($data['start_date'])) + 1;
        }

        $leave->update($data);
        return response()->json($leave);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $leave = LeaveRequest::findOrFail($id);
        $leave->delete();

        return response()->json(null, 204);
    }
}
