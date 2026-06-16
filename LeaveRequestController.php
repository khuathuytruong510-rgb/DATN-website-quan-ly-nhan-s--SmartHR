<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends ApiController
{
    /**
     * [API] API lấy danh sách đơn nghỉ phép trả về JSON (Postman / Vue.js)
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = LeaveRequest::with(['employee', 'approver']);

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

    /**
     * [API] API xem chi tiết 1 đơn nghỉ phép
     */
    public function show($id): \Illuminate\Http\JsonResponse
    {
        $leave = LeaveRequest::with(['employee', 'approver'])->findOrFail($id);
        return response()->json($leave);
    }

    /**
     * [GỘP CHUNG WEB & API] Xử lý lưu đơn xin nghỉ phép vào DB
     */
    public function store(Request $request)
    {
        // Kiểm tra token nếu request đến từ luồng API chuyên biệt
        if ($request->expectsJson() || $request->ajax()) {
            $this->currentUser($request);
        }        
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:annual,sick,personal,unpaid',
            'reason' => 'required|string',
            'status' => 'nullable|in:pending,approved,rejected',
            'approved_by_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        
        // Tự động tính số ngày nghỉ thực tế bằng Carbon
        $start = \Carbon\Carbon::parse($data['start_date']);
            $end = \Carbon\Carbon::parse($data['end_date']);

            $data['days'] = $start->diffInDays($end, false) + 1;

            // hoặc an toàn hơn:
            $data['days'] = abs($start->diffInDays($end)) + 1;
        // ĐỒNG BỘ DỮ LIỆU CẢ 2 CỘT (Sửa lỗi General error: 1364 Field 'days' doesn't have a default value)

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        $leave = LeaveRequest::create($data);

        // Trả về dữ liệu dạng JSON nếu gọi qua API
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($leave, 201);
        }

        // Chuyển hướng về giao diện trang danh sách kèm Session thông báo nếu gửi từ Form Web
        return redirect()->route('leave_requests.index')->with('success', 'Gửi đơn xin nghỉ phép thành công.');
    }

    /**
     * [API] Xử lý cập nhật thông tin đơn qua API
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $leave = LeaveRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'type' => 'sometimes|required|in:annual,sick,personal,unpaid',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['start_date']) || isset($data['end_date'])) {

            $startDate = $data['start_date'] ?? $leave->start_date;
            $endDate = $data['end_date'] ?? $leave->end_date;

            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            $data['days'] = abs($start->diffInDays($end)) + 1;
        }

        $leave->update($data);

        return response()->json($leave);
    }

    /**
     * [API] Xóa đơn xin nghỉ phép
     */
   public function destroy(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        $leave->delete();

        return redirect()
            ->route('leave_requests.index')
            ->with('success', 'Xóa đơn nghỉ phép thành công!');
    }

    /**
     * [GỘP CHUNG WEB & API] Xử lý Phê duyệt / Từ chối đơn nghỉ phép (STT 14)
     */
    public function approve(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:approved,rejected',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $leaveRequest = LeaveRequest::findOrFail($id);

    $leaveRequest->update([
        'status' => $request->status,
    ]);

    return redirect()
        ->route('leave_requests.index')
        ->with('success', 'Cập nhật trạng thái đơn nghỉ phép thành công!');
}
    /**
     * [WEB] Hiển thị giao diện danh sách đơn nghỉ phép kèm Bộ lọc trạng thái đơn (STT 12)
     */
    public function leaveRequests(Request $request): \Illuminate\View\View
    {
        $query = LeaveRequest::with('employee');

        // Thực hiện gán điều kiện lọc trạng thái chuẩn chỉ
        if ($request->filled('status')) {
            $statusVal = trim(strtolower($request->status)); 
            $query->where('status', $statusVal);
        }

        // Sắp xếp đơn mới nhất xếp lên đầu
        $query->orderBy('created_at', 'desc');

        // Phân trang kết quả
        $leaveRequests = $query->paginate(10)->withQueryString();

        // Xử lý sửa đổi hiển thị số ngày trống hoặc âm trực tiếp
        foreach ($leaveRequests as $leave) {
            $daysField = $leave->total_days ?? $leave->days;
            if ($daysField <= 0 || empty($daysField)) {
                $start = \Carbon\Carbon::parse($leave->start_date);
                $end = \Carbon\Carbon::parse($leave->end_date);
                $leave->calculated_display_days = abs($start->diffInDays($end)) + 1;
            } else {
                $leave->calculated_display_days = $daysField;
            }
        }

        return view('hr.leave.index', [
            'leaveRequests' => $leaveRequests,
        ]);
    }

    /**
     * [WEB] Hiển thị giao diện Form tạo đơn nghỉ phép (STT 13)
     */
    public function create()
    {
        return view('hr.leave.form', [
            'employees' => \App\Models\Employee::orderBy('name')->get()
        ]);
    }
}