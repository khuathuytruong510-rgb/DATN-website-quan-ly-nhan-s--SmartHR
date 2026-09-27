<?php

namespace App\Console\Commands;

use App\Services\PayrollPaymentWorkflowService;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoFinalizeMonthlyPayrollCommand extends Command
{
    protected $signature = 'payroll:auto-finalize-monthly {--date= : Ngày tham chiếu Y-m-d (mặc định hôm nay)}';

    protected $description = 'Tự hoàn tất các bước HR và Giám đốc còn chờ trong kỳ lương tháng trước';

    public function handle(PayrollPaymentWorkflowService $workflow, PayrollPeriodLockService $locks): int
    {
        try {
            $asOf = $this->option('date') ? Carbon::parse((string) $this->option('date')) : now();
        } catch (\Throwable) {
            $this->error('Ngày tham chiếu không hợp lệ. Dùng định dạng Y-m-d.');

            return self::FAILURE;
        }

        $period = $asOf->copy()->startOfMonth()->subMonth();
        $month = (int) $period->month;
        $year = (int) $period->year;

        if (! $locks->isLocked($month, $year)
            || ! $locks->isHrVerified($month, $year)
            || $locks->hasPendingUnlockRequest($month, $year)) {
            $this->warn(sprintf('Bỏ qua tự chốt kỳ %02d/%d vì kỳ chưa khóa/kiểm tra hoặc đang chờ mở khóa.', $month, $year));

            return self::SUCCESS;
        }

        $result = $workflow->autoFinalizePeriod($month, $year);
        $this->info(sprintf(
            'Kỳ %02d/%d: hệ thống kiểm tra HR %d phiếu và tự phê duyệt %d phiếu; chưa thanh toán.',
            $month,
            $year,
            $result['reviewed'],
            $result['approved']
        ));

        return self::SUCCESS;
    }
}