<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-payroll-emails')]
#[Description('Command description')]
class SendPayrollEmails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
{
    $today = now()->toDateString();

    $payrolls = Payroll::with('employee')
        ->whereDate('paid_at', $today)
        ->where('status', 'paid')
        ->get();

    foreach ($payrolls as $payroll) {
        Mail::to($payroll->employee->email)
            ->send(new PayrollMail($payroll));
    }

    $this->info('Đã gửi email bảng lương.');
}
}
