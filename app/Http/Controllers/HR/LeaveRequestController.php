<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends ApiController
{
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
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
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
