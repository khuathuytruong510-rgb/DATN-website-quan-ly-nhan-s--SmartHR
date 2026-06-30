<?php

namespace App\Services;


use App\Models\Payroll;
use App\Jobs\SendPayrollEmailJob;

class PayrollEmailService
{
    public function send(Payroll $payroll)
{

    try {

        SendPayrollEmailJob::dispatch($payroll);

        EmailLog::create([

            'employee_id' => $payroll->employee_id,

            'payroll_id' => $payroll->id,

            'status' => 'success',

            'message' => 'Gửi email thành công',

            'sent_at' => now()

        ]);

    } catch (\Exception $e) {

        EmailLog::create([

            'employee_id' => $payroll->employee_id,

            'payroll_id' => $payroll->id,

            'status' => 'failed',

            'message' => $e->getMessage(),

            'sent_at' => now()

        ]);

    }

}
}