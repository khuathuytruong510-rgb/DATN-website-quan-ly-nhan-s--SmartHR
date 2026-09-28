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

    public bool $isRevision;

    public function __construct(Payroll $payroll, bool $isRevision = false)
    {
        $this->payroll = $payroll;
        $this->isRevision = $isRevision;
    }

    public function build()
    {
        $subject = $this->isRevision
            ? 'Bảng lương đã cập nhật tháng '.$this->payroll->display_month
            : 'Thông báo bảng lương tháng '.$this->payroll->display_month;

        return $this->subject($subject)
            ->view('emails.payroll_confirmation')
            ->with([
                'payroll' => $this->payroll,
                'employee' => $this->payroll->employee,
                'isRevision' => $this->isRevision,
            ]);
    }
}
