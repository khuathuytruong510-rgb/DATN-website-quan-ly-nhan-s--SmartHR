<?php

namespace App\Jobs;

use App\Mail\PayrollMail;
use App\Models\Payroll;
use App\Models\EmailLog;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPayrollEmailJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public function __construct(public Payroll $payroll)
    {
    }

    public function handle(): void
{
    if (!$this->payroll->employee || empty($this->payroll->employee->email)) {

        EmailLog::create([
            'employee_id' => $this->payroll->employee_id,
            'payroll_id'  => $this->payroll->id,
            'status'      => 'failed',
            'message'     => 'Nhân viên chưa có email.',
            'sent_at'     => now(),
        ]);

        return;
    }

    try {

        Mail::to($this->payroll->employee->email)
            ->send(new PayrollMail($this->payroll));

        EmailLog::create([
            'employee_id' => $this->payroll->employee_id,
            'payroll_id'  => $this->payroll->id,
            'status'      => 'success',
            'message'     => 'Gửi email thành công.',
            'sent_at'     => now(),
        ]);

    } catch (Exception $e) {

        EmailLog::create([
            'employee_id' => $this->payroll->employee_id,
            'payroll_id'  => $this->payroll->id,
            'status'      => 'failed',
            'message'     => $e->getMessage(),
            'sent_at'     => now(),
        ]);

        throw $e;
    }
}
}