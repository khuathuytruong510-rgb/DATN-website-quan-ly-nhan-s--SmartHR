<?php

namespace App\Console\Commands;

use App\Services\PayrollPaymentWorkflowService;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoFinalizeMonthlyPayrollCommand extends Command
{
    protected $signature = 'payroll:auto-finalize-monthly {--date= : Ngày tham chiếu Y-m-d (mặc định hôm nay)}';

    protected $description = 'Legacy: không còn tự gửi duyệt / tự phê duyệt — Kế toán và Giám đốc thao tác thủ công';

    public function handle(PayrollPaymentWorkflowService $workflow, PayrollPeriodLockService $locks): int
    {
        // Quy trình mới: hệ thống chỉ tự khóa kỳ + tự tính sau khi HR xác nhận.
        // Gửi duyệt (Kế toán) và phê duyệt (Giám đốc) là thao tác thủ công.
        $this->info('Bỏ qua auto-finalize: Kế toán gửi duyệt và Giám đốc phê duyệt thủ công.');

        return self::SUCCESS;
    }
}