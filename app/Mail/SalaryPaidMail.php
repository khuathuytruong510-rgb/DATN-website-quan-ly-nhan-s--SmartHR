<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SalaryPayment;

class SalaryPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;

    public function __construct(SalaryPayment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        return $this->subject('Thông báo thanh toán lương')
            ->view('emails.salary_paid')
            ->with(['payment' => $this->payment]);
    }
}
