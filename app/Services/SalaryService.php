<?php

namespace App\Services;

use App\Models\SalaryHistory;
use App\Models\SalaryPayment;
use App\Models\SalaryPaymentLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalaryPaidMail;

class SalaryService
{
    public function processPayment(SalaryPayment $payment, array $data = []) : SalaryPayment
    {
        return DB::transaction(function () use ($payment, $data) {
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
                if ($payroll->status !== 'paid') {
                    $payroll->update([
                        'status' => 'paid',
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

            // send mail to employee if email exists
            if ($payment->employee && !empty($payment->employee->email)) {
                try {
                    Mail::to($payment->employee->email)->send(new SalaryPaidMail($payment));
                } catch (\Throwable $e) {
                    // record mail failure
                    $this->recordLog($payment, 'mail_failed', $e->getMessage());
                }
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
