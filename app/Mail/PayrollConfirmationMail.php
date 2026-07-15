<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayrollConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payroll $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function build()
    {
        return $this->subject('Xác nhận lương tháng ' . $this->payroll->display_month)
            ->view('emails.payroll_confirmation')
            ->with([
                'payroll' => $this->payroll,
                'employee' => $this->payroll->employee,
            ]);
    }
}
