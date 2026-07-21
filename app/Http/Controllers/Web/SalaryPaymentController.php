<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessSalaryPaymentRequest;
use App\Models\SalaryPayment;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\Request;

/**
 * Chỉ xem lịch sử phiếu thanh toán.
 * Thanh toán thật sự phải qua PayrollPaymentController (ready_for_payment).
 */
class SalaryPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = SalaryPayment::with('employee')
            ->when($request->month, fn ($q) => $q->where('month', $request->month))
            ->when($request->year, fn ($q) => $q->where('year', $request->year))
            ->when($request->department_id, fn ($q) => $q->whereHas('employee', fn ($qq) => $qq->where('department_id', $request->department_id)))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->employee, fn ($q) => $q->whereHas('employee', fn ($qq) => $qq->where('name', 'like', "%{$request->employee}%")));

        $payments = $query->orderByDesc('paid_at')->orderByDesc('id')->paginate(20);

        return view('salary_payments.index', compact('payments'));
    }

    public function show(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load('employee', 'payroll', 'logs');

        return view('salary_payments.show', compact('salaryPayment'));
    }

    public function pay(ProcessSalaryPaymentRequest $request, SalaryPayment $salaryPayment)
    {
        $salaryPayment->loadMissing('payroll');

        if ($salaryPayment->payroll_id && $salaryPayment->payroll) {
            if ($salaryPayment->payroll->status === PayrollPaymentWorkflowService::READY_FOR_PAYMENT) {
                return redirect()
                    ->route('payroll.payment.show', $salaryPayment->payroll)
                    ->with('info', 'Vui lòng thanh toán theo quy trình bảng lương (xác nhận → thanh toán).');
            }

            return back()->with(
                'error',
                'Không thể thanh toán ngoài quy trình. Phiếu phải ở trạng thái đủ điều kiện thanh toán.'
            );
        }

        return back()->with('error', 'Thiếu phiếu lương liên kết. Không thể thanh toán.');
    }
}
