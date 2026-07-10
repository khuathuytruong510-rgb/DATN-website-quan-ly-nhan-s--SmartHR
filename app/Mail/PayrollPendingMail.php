<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayrollPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payroll $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function build()
    {
        return $this
            ->subject('Thông báo xác nhận bảng lương')
            ->view('emails.payroll_pending');
    }
}