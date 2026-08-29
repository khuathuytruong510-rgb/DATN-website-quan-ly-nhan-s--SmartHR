<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Services\SalaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        if (! request()->user()?->is_accountant) {
            abort(403, 'Chỉ Kế toán được tạo thanh toán.');
        }

        if (! in_array($payroll->status, \App\Services\PayrollPaymentWorkflowService::payableStatuses(), true)) {
            return back()->with('error', 'Chỉ tạo thanh toán khi bảng lương đủ điều kiện thanh toán (NV đã xác nhận).');
        }

        return redirect()
            ->route('payroll.payment.show', $payroll)
            ->with('info', 'Thanh toán qua trang quy trình bảng lương.');
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
        if (! request()->user()?->is_accountant) {
            abort(403, 'Chỉ Kế toán được sửa phiếu thanh toán.');
        }

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
        if (! $request->user()?->is_accountant) {
            abort(403, 'Chỉ Kế toán được sửa phiếu thanh toán.');
        }

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
        if (! $request->user()?->is_accountant) {
            abort(403, 'Chỉ Kế toán được thanh toán lương.');
        }

        $salaryPayment->loadMissing('payroll');

        if ($salaryPayment->payroll_id && $salaryPayment->payroll) {
            if (in_array($salaryPayment->payroll->status, \App\Services\PayrollPaymentWorkflowService::payableStatuses(), true)) {
                return redirect()
                    ->route('payroll.payment.show', $salaryPayment->payroll)
                    ->with('info', 'Vui lòng thanh toán theo quy trình bảng lương.');
            }
        }

        return back()->with('error', 'Không thể thanh toán ngoài quy trình xác nhận bảng lương.');
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
            ->whereIn('status', \App\Services\PayrollPaymentWorkflowService::payableStatuses())
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
     * Gửi phiếu lương qua email
     */
    public function sendEmail(SalaryPayment $salaryPayment)
    {
        if (!$salaryPayment->employee || !$salaryPayment->employee->email) {
            return back()->with('error', 'Nhân viên chưa có email!');
        }

        try {
            Mail::to($salaryPayment->employee->email)
                ->send(new \App\Mail\SalaryPaidMail($salaryPayment));

            $this->salaryService->recordLog($salaryPayment, 'email_sent', 'Gửi phiếu qua email');

            return back()->with('success', 'Gửi phiếu lương thành công!');
        } catch (\Exception $e) {
            $this->salaryService->recordLog($salaryPayment, 'email_failed', $e->getMessage());
            return back()->with('error', 'Gửi email thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Xóa thanh toán
     */
    public function destroy(SalaryPayment $salaryPayment)
    {
        if (! request()->user()?->is_accountant) {
            abort(403, 'Chỉ Kế toán được xóa phiếu thanh toán chưa chi.');
        }

        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Không thể xóa thanh toán đã xử lý!');
        }

        $salaryPayment->delete();

        return back()->with('success', 'Xóa thanh toán thành công!');
    }
}
