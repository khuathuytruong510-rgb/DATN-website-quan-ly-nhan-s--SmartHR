<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayrollPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payroll $payroll;

    /**
     * Create a new message instance.
     */
    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject('Thông báo đã thanh toán lương')
            ->view('emails.payroll_paid');
    }
}