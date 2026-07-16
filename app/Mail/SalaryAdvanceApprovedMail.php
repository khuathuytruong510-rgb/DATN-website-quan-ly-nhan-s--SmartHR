<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SalaryAdvance;

class SalaryAdvanceApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $advance;

    public function __construct(SalaryAdvance $advance)
    {
        $this->advance = $advance;
    }

    public function build()
    {
        return $this->subject('Thông báo ứng lương')
            ->view('emails.salary_advance_approved')
            ->with(['advance' => $this->advance]);
    }
}
