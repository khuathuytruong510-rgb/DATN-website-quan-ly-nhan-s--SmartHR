<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\SalaryHistory;
use App\Models\SalaryPayment;
use App\Models\SalaryPaymentLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalaryService
{
    public function processPayment(SalaryPayment $payment, array $data = []): SalaryPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = SalaryPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->payroll_id) {
                $payroll = Payroll::query()->whereKey($payment->payroll_id)->lockForUpdate()->first();
                $payment->setRelation('payroll', $payroll);
            } else {
                $payment->loadMissing('payroll');
            }

            if ($payment->status === 'paid' || $payment->payroll?->status === PayrollPaymentWorkflowService::PAID) {
                throw new RuntimeException('Phiếu đã thanh toán. Không thể thanh toán lần hai.');
            }

            if ($payment->payroll_id && $payment->payroll) {
                $status = $payment->payroll->status;
                if (! in_array($status, PayrollPaymentWorkflowService::payableStatuses(), true)) {
                    throw new RuntimeException(
                        'Chỉ thanh toán khi bảng lương đủ điều kiện (nhân viên đã xác nhận).'
                    );
                }
            }

            $payment->fill([
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'bank' => $data['bank'] ?? $payment->bank,
                'account_holder' => $data['account_holder'] ?? $payment->account_holder,
                'account_number' => $data['account_number'] ?? $payment->account_number,
                'transaction_code' => $data['transaction_code'] ?? $payment->transaction_code,
                'cash_payer' => $data['cash_payer'] ?? $payment->cash_payer,
                'notes' => $data['notes'] ?? $payment->notes,
                'status' => 'paid',
                'paid_by' => Auth::id(),
                'paid_at' => now(),
            ]);

            $payment->save();

            $this->recordLog($payment, 'paid', $data['notes'] ?? null);

            if ($payment->payroll_id && $payment->payroll) {
                $payroll = $payment->payroll;
                if ($payroll->status !== PayrollPaymentWorkflowService::PAID) {
                    $payroll->update([
                        'status' => PayrollPaymentWorkflowService::PAID,
                        'paid_at' => $payroll->paid_at ?? now(),
                        'paid_by' => $payroll->paid_by ?? Auth::id(),
                        'payment_method' => $payroll->payment_method ?? ($data['payment_method'] ?? $payment->payment_method),
                    ]);
                }

                SalaryHistory::recordFromPaidPayroll(
                    $payroll->fresh(['employee', 'salaryPayment']),
                    Auth::user() instanceof User ? Auth::user() : null
                );
            }

            return $payment;
        });
    }

    public function recordLog(SalaryPayment $payment, string $action, ?string $notes = null)
    {
        return SalaryPaymentLog::create([
            'salary_payment_id' => $payment->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip' => request()->ip(),
            'device' => request()->userAgent(),
            'notes' => $notes,
        ]);
    }
}
