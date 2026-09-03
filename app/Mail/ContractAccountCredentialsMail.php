<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractAccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $loginEmail,
        public string $plainPassword,
        public ?Contract $contract = null,
    ) {
    }

    public function build()
    {
        $subject = $this->contract
            ? 'Tài khoản SmartHR — ký hợp đồng '.$this->contract->contract_code
            : 'Tài khoản đăng nhập SmartHR';

        return $this->subject($subject)
            ->view('emails.contract_account_credentials');
    }
}
