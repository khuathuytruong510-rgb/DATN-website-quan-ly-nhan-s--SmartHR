<?php

namespace App\Console\Commands;

use App\Mail\PayrollConfirmationMail;
use App\Models\Payroll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email? : Email nhận thử} {--payroll= : ID bảng lương để gửi mẫu xác nhận}';

    protected $description = 'Gửi email thử qua Gmail SMTP để kiểm tra cấu hình mail';

    public function handle(): int
    {
        $email = $this->argument('email') ?: config('mail.from.address');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email không hợp lệ.');

            return self::FAILURE;
        }

        $this->info('Mailer: '.config('mail.default'));
        $this->info('Host: '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
        $this->info('From: '.config('mail.from.address'));
        $this->info('To: '.$email);

        try {
            $payrollId = $this->option('payroll');
            if ($payrollId) {
                $payroll = Payroll::with('employee')->findOrFail($payrollId);
                Mail::to($email)->send(new PayrollConfirmationMail($payroll));
                $this->info('Đã gửi email xác nhận lương (payroll #'.$payroll->id.').');
            } else {
                Mail::raw(
                    "Đây là email test từ SmartHR.\nThời gian: ".now()->toDateTimeString()."\nNếu bạn nhận được mail này, cấu hình Gmail SMTP đã OK.",
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('SmartHR — Test email Gmail SMTP');
                    }
                );
                $this->info('Đã gửi email test thuần (raw).');
            }
        } catch (\Throwable $e) {
            $this->error('Gửi thất bại: '.$e->getMessage());
            $this->line('Gợi ý: kiểm tra App Password Gmail, MAIL_USERNAME/MAIL_PASSWORD, và bật 2FA.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
