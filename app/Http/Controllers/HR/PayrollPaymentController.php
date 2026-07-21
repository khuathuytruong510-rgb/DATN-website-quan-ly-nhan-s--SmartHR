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

    protected function bankRules(bool $required = true): array
    {
        $banks = config('banks', []);

        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:100',
            Rule::in(array_merge($banks, array_filter([optional(request()->route('payroll')?->employee)->bank_name]))),
        ];
    }

    protected function assertCanPay(): void
    {
        $user = request()->user();
        if (! $user || (! $user->is_admin && ! $user->is_accountant)) {
            abort(403, 'Chỉ Kế toán/Admin được thanh toán lương.');
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

        $payroll->load(['employee', 'salaryPayment']);

        return view('hr.payroll.payment', [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'workflow' => $this->workflow,
        ]);
    }

    public function updateBank(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->assertCanPay();

        $data = $request->validate([
            'bank_name' => $this->bankRules(true),
            'account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9]{6,20}$/'],
            'account_holder' => ['required', 'string', 'max:150'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng.',
            'bank_name.in' => 'Ngân hàng không hợp lệ.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_number.regex' => 'Số tài khoản chỉ gồm 6–20 chữ số.',
            'account_holder.required' => 'Vui lòng nhập chủ tài khoản.',
            'qr_image.image' => 'File QR phải là ảnh.',
            'qr_image.mimes' => 'QR chỉ nhận jpg, jpeg, png, webp.',
            'qr_image.max' => 'Ảnh QR tối đa 4MB.',
        ]);

        if (! $payroll->employee) {
            return back()->with('error', 'Thiếu thông tin nhân viên.');
        }

        $this->workflow->updateEmployeeBank(
            $payroll->employee,
            $data,
            $request->file('qr_image')
        );

        return back()->with('success', 'Đã lưu thông tin nhận lương.');
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

        if ($data['payment_method'] === 'bank_transfer') {
            if (blank($employee->account_number) || blank($employee->account_holder) || blank($employee->bank_name)) {
                throw ValidationException::withMessages([
                    'account_number' => 'Vui lòng lưu đủ Ngân hàng / Số TK / Chủ TK trước khi xác nhận chuyển khoản.',
                ]);
            }
        }

        if ($data['payment_method'] === 'cash') {
            $data['transaction_code'] = null;
        }

        $data['notes'] = $this->buildFixedNote($payroll, $employee, $data['payment_method']);

        try {
            $this->workflow->markPaid($payroll->fresh(['employee', 'salaryPayment']), $data, $request->user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', 'Đã thanh toán lương thành công.');
    }

    protected function buildFixedNote(Payroll $payroll, $employee, string $method): string
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
            $employee->account_number ?: 'chưa có',
            $employee->account_holder ?: $name,
            $employee->bank_name ?: 'chưa rõ NH'
        );
    }
}
