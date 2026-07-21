<?php

namespace App\Console\Commands;

use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Console\Command;

class AutoMarkPayrollReadyCommand extends Command
{
    protected $signature = 'payroll:auto-ready';

    protected $description = 'Tự động chuyển bảng lương chờ xác nhận sang đủ điều kiện thanh toán sau hạn 3 ngày';

    public function handle(PayrollPaymentWorkflowService $workflow): int
    {
        $count = $workflow->autoMarkReady();
        $this->info("Đã chuyển {$count} bảng lương sang đủ điều kiện thanh toán.");

        return self::SUCCESS;
    }
}
