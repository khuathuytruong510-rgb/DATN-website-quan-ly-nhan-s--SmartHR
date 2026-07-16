<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class SalaryPaymentController extends Controller
{
    public function __construct(protected SalaryService $salaryService) {}

    /**
     * Danh sách thanh toán lương
     */
    public function index(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $status = $request->status ?? 'all';

        $query = SalaryPayment::with(['employee', 'employee.department', 'paidBy'])
            ->where('month', $month)
            ->where('year', $year);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $payments = $query->orderByDesc('id')->paginate(20);

        return view('salary_payments.index', compact(
            'payments',
            'month',
            'year',
            'status'
        ));
    }

    /**
     * Tạo thanh toán từ bảng lương
     */
    public function create(Payroll $payroll)
    {
        // Kiểm tra xem bảng lương đã duyệt chưa
        if ($payroll->status !== 'approved') {
            return back()->with('error', 'Bảng lương chưa được duyệt!');
        }

        // Kiểm tra xem đã có thanh toán chưa
        $existing = SalaryPayment::where('payroll_id', $payroll->id)->first();
        if ($existing) {
            return redirect()->route('salary_payments.show', $existing);
        }

        // Tạo record thanh toán mới
        $payment = SalaryPayment::create([
            'employee_id' => $payroll->employee_id,
            'payroll_id' => $payroll->id,
            'code' => 'PAY-' . now()->format('YmdHis'),
            'month' => $payroll->month,
            'year' => $payroll->year,
            'total' => $payroll->total_salary,
            'deductions' => $payroll->insurance + $payroll->tax,
            'net' => $payroll->total_salary - ($payroll->insurance + $payroll->tax),
            'status' => 'pending',
        ]);

        return redirect()->route('salary_payments.show', $payment);
    }

    /**
     * Xem chi tiết thanh toán
     */
    public function show(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load(['employee', 'payroll', 'paidBy', 'logs.user']);

        return view('salary_payments.show', compact('salaryPayment'));
    }

    /**
     * Form sửa thanh toán
     */
    public function edit(SalaryPayment $salaryPayment)
    {
        // Chỉ cho phép sửa nếu chưa thanh toán
        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Không thể sửa thanh toán đã xử lý!');
        }

        $paymentMethods = ['bank_transfer', 'cash'];
        $banks = ['Vietcombank', 'Techcombank', 'BIDV', 'Agribank', 'Khác'];

        return view('salary_payments.edit', compact(
            'salaryPayment',
            'paymentMethods',
            'banks'
        ));
    }

    /**
     * Cập nhật thông tin thanh toán
     */
    public function update(Request $request, SalaryPayment $salaryPayment)
    {
        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Không thể sửa thanh toán đã xử lý!');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash',
            'bank' => 'nullable|required_if:payment_method,bank_transfer|string',
            'account_holder' => 'nullable|required_if:payment_method,bank_transfer|string',
            'account_number' => 'nullable|required_if:payment_method,bank_transfer|string',
            'transaction_code' => 'nullable|string',
            'cash_payer' => 'nullable|required_if:payment_method,cash|string',
            'notes' => 'nullable|string',
        ]);

        $salaryPayment->update($validated);

        return redirect()->route('salary_payments.show', $salaryPayment)
            ->with('success', 'Cập nhật thông tin thanh toán thành công!');
    }

    /**
     * Xử lý thanh toán
     */
    public function pay(Request $request, SalaryPayment $salaryPayment)
    {
        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Thanh toán không hợp lệ!');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash',
            'bank' => 'nullable|required_if:payment_method,bank_transfer|string',
            'account_holder' => 'nullable|required_if:payment_method,bank_transfer|string',
            'account_number' => 'nullable|required_if:payment_method,bank_transfer|string',
            'transaction_code' => 'nullable|string',
            'cash_payer' => 'nullable|required_if:payment_method,cash|string',
            'notes' => 'nullable|string',
        ]);

        // Xử lý thanh toán thông qua Service
        $this->salaryService->processPayment($salaryPayment, $validated);

        // Cập nhật status của payroll
        if ($salaryPayment->payroll) {
            $salaryPayment->payroll->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return redirect()->route('salary_payments.show', $salaryPayment)
            ->with('success', 'Thanh toán lương thành công!');
    }

    /**
     * Liệt kê bảng lương chưa có thanh toán
     */
    public function selectPayroll(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $payrolls = Payroll::with('employee', 'employee.department')
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved')
            ->whereDoesntHave('salaryPayment')
            ->orderByDesc('id')
            ->paginate(20);

        return view('salary_payments.select_payroll', compact(
            'payrolls',
            'month',
            'year'
        ));
    }

    /**
     * Export danh sách thanh toán
     */
    public function export(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $payments = SalaryPayment::with(['employee', 'employee.department'])
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('employee_id')
            ->get();

        $filename = "thanh_toan_luong_{$month}_{$year}.csv";
        $handle = fopen('php://memory', 'w');

        // Header
        fputcsv($handle, [
            'Mã',
            'Nhân viên',
            'Phòng ban',
            'Hình thức',
            'Tổng lương',
            'Khấu trừ',
            'Thực lĩnh',
            'Trạng thái',
            'Ngày thanh toán'
        ], ';');

        // Data
        foreach ($payments as $p) {
            fputcsv($handle, [
                $p->code,
                $p->employee->name ?? '-',
                $p->employee->department->name ?? '-',
                $p->payment_method,
                $p->total,
                $p->deductions,
                $p->net,
                $p->status,
                $p->paid_at
            ], ';');
        }

        rewind($handle);
        $content = stream_get_clean();

        return response()->streamDownload(function () use ($handle) {
            fpassthru($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=$filename"
        ]);
    }

    /**
     * Xóa thanh toán
     */
    public function destroy(SalaryPayment $salaryPayment)
    {
        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Không thể xóa thanh toán đã xử lý!');
        }

        $salaryPayment->delete();

        return back()->with('success', 'Xóa thanh toán thành công!');
    }
}
