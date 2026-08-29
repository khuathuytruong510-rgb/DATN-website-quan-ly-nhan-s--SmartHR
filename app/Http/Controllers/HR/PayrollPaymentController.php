<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollPaymentController extends Controller
{
    public function __construct(protected PayrollPaymentWorkflowService $workflow)
    {
    }

    protected function assertCanPay(): void
    {
        $user = request()->user();
        if (! $user || ! $user->is_accountant) {
            abort(403, 'Chỉ Kế toán được thanh toán lương.');
        }
    }

    public function show(Payroll $payroll): View|RedirectResponse
    {
        $this->assertCanPay();

        if (! $this->workflow->canPay($payroll) && $payroll->status !== 'paid') {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', 'Bảng lương chưa đủ điều kiện thanh toán.');
        }

        $payroll->load(['employee', 'salaryPayment', 'paidByUser']);

        return view('hr.payroll.payment', [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'workflow' => $this->workflow,
        ]);
    }

    public function updateBank(Request $request, Payroll $payroll): RedirectResponse
    {
        abort(403, 'Kế toán không sửa hồ sơ nhân viên. Thông tin STK đã chốt khi HR kiểm tra. Đổi STK do HR xử lý.');
    }

    public function confirm(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->assertCanPay();
        $payroll->load('employee');

        $data = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,cash'],
            'transaction_code' => [
                Rule::requiredIf($request->input('payment_method') === 'bank_transfer'),
                'nullable',
                'string',
                'max:50',
                Rule::when(
                    $request->input('payment_method') === 'bank_transfer',
                    ['regex:/^[A-Za-z0-9\-_]{6,50}$/']
                ),
            ],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'transaction_code.required' => 'Vui lòng nhập mã giao dịch khi thanh toán chuyển khoản.',
            'transaction_code.regex' => 'Mã giao dịch gồm 6–50 ký tự (chữ, số, - hoặc _).',
        ]);

        $employee = $payroll->employee;
        if (! $employee) {
            throw ValidationException::withMessages([
                'account_number' => 'Thiếu thông tin nhân viên.',
            ]);
        }

        $bankName = $payroll->payout_bank_name ?: $employee->bank_name;
        $accountNumber = $payroll->payout_account_number ?: $employee->account_number;
        $accountHolder = $payroll->payout_account_holder ?: $employee->account_holder;

        if ($data['payment_method'] === 'bank_transfer') {
            if (blank($accountNumber) || blank($accountHolder) || blank($bankName)) {
                throw ValidationException::withMessages([
                    'account_number' => 'Phiếu chưa có STK đã chốt. HR cần kiểm tra / cập nhật thông tin nhận lương trước khi thanh toán chuyển khoản.',
                ]);
            }
        }

        if ($data['payment_method'] === 'cash') {
            $data['transaction_code'] = null;
        }

        $payload = [
            'payment_method' => $data['payment_method'],
            'transaction_code' => $data['transaction_code'] ?? null,
            'notes' => $this->buildFixedNote($payroll, $employee, $data['payment_method'], $bankName, $accountNumber, $accountHolder),
        ];

        try {
            $this->workflow->markPaid($payroll->fresh(['employee', 'salaryPayment']), $payload, $request->user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', 'Đã thanh toán lương thành công.');
    }

    protected function buildFixedNote(Payroll $payroll, $employee, string $method, ?string $bankName = null, ?string $accountNumber = null, ?string $accountHolder = null): string
    {
        $period = sprintf('%02d/%s', (int) $payroll->month, $payroll->year);
        $amount = number_format($payroll->total_salary ?? 0, 0, '.', ',');
        $name = $employee->name ?? 'N/A';

        if ($method === 'cash') {
            return sprintf(
                'Thanh toán lương tháng %s cho nhân viên %s bằng tiền mặt. Số tiền: %s ₫.',
                $period,
                $name,
                $amount
            );
        }

        return sprintf(
            'Thanh toán lương tháng %s cho nhân viên %s bằng chuyển khoản. Số tiền: %s ₫. STK: %s — %s (%s).',
            $period,
            $name,
            $amount,
            $accountNumber ?: ($employee->account_number ?: 'chưa có'),
            $accountHolder ?: ($employee->account_holder ?: $name),
            $bankName ?: ($employee->bank_name ?: 'chưa rõ NH')
        );
    }
}
