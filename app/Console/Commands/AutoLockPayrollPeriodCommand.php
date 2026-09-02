<?php

namespace App\Console\Commands;

use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoLockPayrollPeriodCommand extends Command
{
    protected $signature = 'payroll:auto-lock-period
                            {--date= : Ngày tham chiếu Y-m-d (mặc định hôm nay)}
                            {--dry-run : Chỉ liệt kê kỳ sẽ chốt, không ghi DB}';

    protected $description = 'Tự động chốt kỳ lương đã kết thúc (sau ngày cuối tháng) để Kế toán tính lương';

    public function handle(PayrollPeriodLockService $locks): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now();

        $lastCompleted = $asOf->copy()->startOfMonth()->subMonth();
        $this->info(sprintf(
            'Tham chiếu %s → chốt các kỳ đến %02d/%d (đã hết tháng).',
            $asOf->toDateString(),
            $lastCompleted->month,
            $lastCompleted->year
        ));

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: không ghi DB.');

            return self::SUCCESS;
        }

        $count = $locks->autoLockCompletedPeriods($asOf);
        $this->info("Đã chốt {$count} kỳ lương.");

        return self::SUCCESS;
    }
}
