<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryPayment;
use App\Models\Employee;
use App\Services\SalaryService;
use App\Http\Requests\ProcessSalaryPaymentRequest;

class SalaryPaymentController extends Controller
{
    protected $service;

    public function __construct(SalaryService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = SalaryPayment::with('employee')
            ->when($request->month, fn($q) => $q->where('month', $request->month))
            ->when($request->year, fn($q) => $q->where('year', $request->year))
            ->when($request->department_id, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('department_id', $request->department_id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->employee, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('name', 'like', "%{$request->employee}%")));

        $payments = $query->orderBy('paid_at', 'desc')->paginate(20);

        return view('salary_payments.index', compact('payments'));
    }

    public function show(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load('employee','logs');
        return view('salary_payments.show', compact('salaryPayment'));
    }

    public function pay(ProcessSalaryPaymentRequest $request, SalaryPayment $salaryPayment)
    {
        // check conditions
        if ($salaryPayment->status !== 'pending') {
            return back()->with('error', 'Chỉ có các bản ghi chưa thanh toán mới được xử lý.');
        }

        // conditions: payroll exists and approved etc. Basic check
        if ($salaryPayment->payroll_id && $salaryPayment->payroll && $salaryPayment->payroll->status !== 'calculated') {
            return back()->with('error', 'Bảng lương chưa được tính.');
        }

        $payment = $this->service->processPayment($salaryPayment, $request->validated());

        return redirect()->route('salary_payments.index')->with('success', 'Thanh toán thành công.');
    }
}
