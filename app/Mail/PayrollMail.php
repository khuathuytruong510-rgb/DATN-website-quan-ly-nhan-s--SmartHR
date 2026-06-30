<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PayrollMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payroll $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Thông báo chốt lương tháng ' . $this->payroll->month,
    );
}

    public function content(): Content
    {
        return new Content(
            view: 'emails.payroll',
            with: [
                'payroll' => $this->payroll,
            ],
        );
    }
}