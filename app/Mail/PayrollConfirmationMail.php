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
        $confirmUrl = null;
        if ($this->payroll->confirmation_token) {
            $confirmUrl = url('/payroll/confirm/'.$this->payroll->confirmation_token);
        }

        $subject = $this->isRevision
            ? 'Bảng lương đã chỉnh sửa — xác nhận lại tháng '.$this->payroll->display_month
            : 'Xác nhận lương tháng '.$this->payroll->display_month;

        return $this->subject($subject)
            ->view('emails.payroll_confirmation')
            ->with([
                'payroll' => $this->payroll,
                'employee' => $this->payroll->employee,
                'confirmUrl' => $confirmUrl,
                'isRevision' => $this->isRevision,
            ]);
    }
}
