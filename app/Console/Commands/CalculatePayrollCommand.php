<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\PayrollCalculationService;
use App\Services\PayrollPaymentWorkflowService;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculatePayrollCommand extends Command
{
    protected $signature = 'payroll:auto-calculate-monthly {--date= : Ngày tham chiếu Y-m-d (mặc định hôm nay)}';

    protected $description = 'Tự tính bảng lương tháng trước vào ngày 15 hàng tháng';

    public function handle(
        PayrollCalculationService $calculator,
        PayrollPeriodLockService $locks
    ): int {
        try {
            $asOf = $this->option('date') ? Carbon::parse((string) $this->option('date')) : now();
        } catch (\Throwable) {
            $this->error('Ngày tham chiếu không hợp lệ. Dùng định dạng Y-m-d.');

            return self::FAILURE;
        }

        $period = $asOf->copy()->startOfMonth()->subMonth();
        $month = (int) $period->month;
        $year = (int) $period->year;

        if (! $locks->find($month, $year)) {
            $locks->lockBySystem($month, $year);
        }

        if (! $locks->isLocked($month, $year) || $locks->hasPendingUnlockRequest($month, $year)) {
            $this->warn(sprintf('Bỏ qua kỳ %02d/%d vì kỳ đang mở hoặc chờ duyệt mở khóa.', $month, $year));

            return self::SUCCESS;
        }

        if (! $locks->isHrVerified($month, $year)) {
            $locks->autoVerify($month, $year);
        }

        try {
            $result = $calculator->calculatePeriod($month, $year, null, true);
        } catch (\Throwable $e) {
            $this->error(sprintf('Không thể tự tính kỳ %02d/%d: %s', $month, $year, $e->getMessage()));

            return self::FAILURE;
        }

        $systemUserId = User::query()
            ->where(fn ($query) => $query->where('is_hr', true)->orWhere('is_admin', true))
            ->orderBy('id')
            ->value('id');

        if ($systemUserId) {
            ActivityLog::create([
                'user_id' => $systemUserId,
                'action' => 'payroll_auto_calculated',
                'meta' => sprintf(
                    'period:%02d/%d;calculated:%d;skipped:%d;by:system',
                    $month,
                    $year,
                    $result['calculated'],
                    $result['skipped']
                ),
            ]);
        }

        $this->info(sprintf(
            'Đã tự tính kỳ %02d/%d: %d phiếu; bỏ qua %d phiếu.',
            $month,
            $year,
            $result['calculated'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
