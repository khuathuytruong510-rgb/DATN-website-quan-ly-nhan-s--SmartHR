<?php

namespace App\Services;

use App\Mail\PayrollPendingMail;
use App\Mail\PayrollPaidMail;
use App\Models\EmailLog;
use App\Models\Payroll;
use Illuminate\Support\Facades\Mail;

class PayrollEmailService
{
    /**
     * Gửi email xác nhận bảng lương
     */
    public function sendPending(Payroll $payroll): bool
    {
        try {

            if (!$payroll->confirm_token) {
                $payroll->generateConfirmToken();
            }

            Mail::to($payroll->employee->email)
                ->send(new PayrollPendingMail($payroll));

            EmailLog::create([
                'employee_id' => $payroll->employee_id,
                'payroll_id' => $payroll->id,
                'status' => 'success',
                'message' => 'Pending email',
                'sent_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {

            EmailLog::create([
                'employee_id' => $payroll->employee_id,
                'payroll_id' => $payroll->id,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            report($e);

            return false;
        }
    }

    /**
     * Gửi email đã thanh toán
     */
    public function sendPaid(Payroll $payroll): bool
    {
        try {

            Mail::to($payroll->employee->email)
                ->send(new PayrollPaidMail($payroll));

            EmailLog::create([
                'employee_id' => $payroll->employee_id,
                'payroll_id' => $payroll->id,
                'status' => 'success',
                'message' => 'Paid email',
                'sent_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {

            EmailLog::create([
                'employee_id' => $payroll->employee_id,
                'payroll_id' => $payroll->id,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            report($e);

            return false;
        }
    }
}