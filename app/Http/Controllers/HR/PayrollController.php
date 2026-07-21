<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * Danh sách bảng lương
     */
    public function index(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $payrolls = Payroll::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('id')
            ->get();

        return view('hr.payroll.index', compact(
            'payrolls',
            'month',
            'year'
        ));
    }

    /**
     * Tính lương toàn bộ nhân viên
     */
    public function generate(
        Request $request,
        PayrollCalculationService $service
    ) {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $employees = Employee::all();

        foreach ($employees as $employee) {

            $service->calculate(
                $employee,
                $month,
                $year
            );
        }

        return redirect()
            ->route(
                'payroll.index',
                [
                    'month' => $month,
                    'year' => $year
                ]
            )
            ->with(
                'success',
                'Đã tính bảng lương thành công!'
            );
    }

    /**
     * Xem chi tiết bảng lương
     */
    public function show(Payroll $payroll)
    {
        $payroll->load('employee');

        return view(
            'hr.payroll.show',
            compact('payroll')
        );
    }

    /**
     * Duyệt bảng lương
     */
    public function approve(Payroll $payroll)
    {
        $payroll->update([
            'status' => 'approved'
        ]);

        return back()->with(
            'success',
            'Đã duyệt bảng lương.'
        );
    }

    /**
     * Duyệt bảng lương và tạo phiếu thanh toán
     */
    public function approveWithPayment(Payroll $payroll)
    {
        $payroll->update(['status' => 'approved']);

        // Tạo record thanh toán nếu chưa tồn tại
        if (!$payroll->salaryPayment) {
            SalaryPayment::create([
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
        }

        return back()->with(
            'success',
            'Đã duyệt bảng lương và tạo phiếu thanh toán.'
        );
    }

    /**
     * Đánh dấu đã thanh toán
     */
    public function paid(Payroll $payroll)
    {
        $payroll->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return back()->with(
            'success',
            'Đã thanh toán lương.'
        );
    }

    /**
     * Xóa bảng lương
     */
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();

        return back()->with(
            'success',
            'Đã xóa bảng lương.'
        );
    }

public function sendConfirmation(Payroll $payroll)
{
    $payroll->update([
        'status' => 'waiting_employee_confirmation',
        'confirmation_sent_at' => now(),
    ]);

    return back()->with(
        'success',
        'Đã gửi yêu cầu xác nhận bảng lương.'
    );
}
}