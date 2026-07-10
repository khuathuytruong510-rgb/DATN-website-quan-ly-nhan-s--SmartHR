<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\PayrollEmailService;

class PayrollController extends ApiController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Payroll::with('employee');

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($month = $request->query('month')) {
            $query->where('month', 'like', "{$month}%");
        }

        $perPage = min((int) $request->query('per_page', 10), 50);
        return response()->json($query->paginate($perPage));
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        return response()->json($payroll);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
            'base_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,approved,paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['total_salary'] = ($data['base_salary'] ?? 0) + ($data['allowance'] ?? 0) - ($data['deduction'] ?? 0);
        
        $payroll = Payroll::create($data);
        return response()->json($payroll, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $payroll = Payroll::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'month' => 'sometimes|required|date_format:Y-m',
            'base_salary' => 'sometimes|required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,approved,paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['base_salary']) || isset($data['allowance']) || isset($data['deduction'])) {
            $data['total_salary'] = ($data['base_salary'] ?? $payroll->base_salary) + 
                                   ($data['allowance'] ?? $payroll->allowance) - 
                                   ($data['deduction'] ?? $payroll->deduction);
        }
        
        $payroll->update($data);
        return response()->json($payroll);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();

        return response()->json(null, 204);
    }

    /**
 * Chốt lương và gửi email xác nhận
 */
public function sendPayroll($id, PayrollEmailService $mailService)
{
    $payroll = Payroll::with('employee')->findOrFail($id);
    if ($payroll->isPaid()) {
    return response()->json([
        'success' => false,
        'message' => 'Bảng lương đã được thanh toán.'
    ], 400);
}

    if (!$payroll->confirm_token) {
    $payroll->generateConfirmToken();
}

$payroll->status = 'pending';
$payroll->save();

$mailService->sendPending($payroll);

    return response()->json([
        'success' => true,
        'message' => 'Đã gửi email xác nhận bảng lương.'
    ]);
}

/**
 * Nhân viên xác nhận bảng lương
 */
public function confirmPayroll($token)
{
    $payroll = Payroll::where('confirm_token', $token)->first();

    if (!$payroll) {
        return response()->json([
            'success' => false,
            'message' => 'Liên kết xác nhận không hợp lệ.'
        ], 404);
    }

    if ($payroll->status == 'approved') {
        return response()->json([
            'success' => false,
            'message' => 'Bảng lương đã được xác nhận.'
        ]);
    }

    if ($payroll->status == 'paid') {
        return response()->json([
            'success' => false,
            'message' => 'Bảng lương đã được thanh toán.'
        ]);
    }

    $payroll->markAsApproved();

    return response()->json([
        'success' => true,
        'message' => 'Xác nhận bảng lương thành công.'
    ]);
}

/**
 * Thanh toán lương
 */
public function payPayroll($id, PayrollEmailService $mailService)
{
    $payroll = Payroll::with('employee')->findOrFail($id);
    if ($payroll->isPaid()) {
    return response()->json([
        'success' => false,
        'message' => 'Bảng lương đã được thanh toán.'
    ],400);
}
    if ($payroll->status != 'approved') {

        return response()->json([
            'success' => false,
            'message' => 'Nhân viên chưa xác nhận bảng lương.'
        ],400);

    }

    $payroll->markAsPaid();

    $mailService->sendPaid($payroll);

    return response()->json([
        'success'=>true,
        'message'=>'Đã thanh toán lương.'
    ]);
}
}
