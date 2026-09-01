<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Payroll;
use App\Models\PayrollPeriodLock;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollPeriodLockService
{
    public function find(int $month, int $year): ?PayrollPeriodLock
    {
        return PayrollPeriodLock::with(['locker', 'unlocker'])
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function isLocked(int $month, int $year): bool
    {
        return (bool) $this->find($month, $year)?->is_locked;
    }

    public function isDateLocked(DateTimeInterface|string $date): bool
    {
        $carbon = Carbon::parse($date);

        return $this->isLocked((int) $carbon->month, (int) $carbon->year);
    }

    public function isRangeLocked(DateTimeInterface|string $start, DateTimeInterface|string $end): bool
    {
        $cursor = Carbon::parse($start)->startOfMonth();
        $last = Carbon::parse($end)->startOfMonth();

        while ($cursor->lte($last)) {
            if ($this->isLocked((int) $cursor->month, (int) $cursor->year)) {
                return true;
            }
            $cursor->addMonth();
        }

        return false;
    }

    public function assertUnlockedForCalculation(int $month, int $year): void
    {
        if (! $this->isLocked($month, $year)) {
            throw new RuntimeException(
                sprintf('HR chưa chốt dữ liệu kỳ %02d/%d. Kế toán chỉ được tính lương sau khi HR khóa kỳ.', $month, $year)
            );
        }
    }

    public function assertWritableDate(DateTimeInterface|string $date, string $context = 'dữ liệu đầu vào'): void
    {
        $carbon = Carbon::parse($date);
        if ($this->isLocked((int) $carbon->month, (int) $carbon->year)) {
            throw new RuntimeException(
                sprintf('Kỳ %02d/%d đã chốt. Không thể sửa %s. HR phải mở khóa kỳ (có ghi nhật ký) nếu cần chỉnh.', $carbon->month, $carbon->year, $context)
            );
        }
    }

    public function assertWritableRange(DateTimeInterface|string $start, DateTimeInterface|string $end, string $context = 'dữ liệu đầu vào'): void
    {
        if ($this->isRangeLocked($start, $end)) {
            throw new RuntimeException(
                sprintf('Kỳ lương liên quan đã chốt. Không thể sửa %s. HR phải mở khóa kỳ nếu cần chỉnh.', $context)
            );
        }
    }

    public function lock(int $month, int $year, User $actor): PayrollPeriodLock
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được chốt dữ liệu kỳ lương.');
        }

        return DB::transaction(function () use ($month, $year, $actor) {
            $period = PayrollPeriodLock::query()
                ->where('month', $month)
                ->where('year', $year)
                ->lockForUpdate()
                ->first() ?? new PayrollPeriodLock([
                    'month' => $month,
                    'year' => $year,
                ]);

            if ($period->is_locked) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d đã được chốt.', $month, $year));
            }

            $period->fill([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $actor->id,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'unlock_reason' => null,
            ]);
            $period->save();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_period_locked',
                'meta' => sprintf('period:%02d/%d', $month, $year),
            ]);

            return $period->fresh(['locker', 'unlocker']);
        });
    }

    public function unlock(int $month, int $year, User $actor, string $reason): PayrollPeriodLock
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được mở khóa kỳ lương.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw new RuntimeException('Cần lý do mở khóa (tối thiểu 10 ký tự). Lý do được ghi nhật ký.');
        }

        return DB::transaction(function () use ($month, $year, $actor, $reason) {
            $period = PayrollPeriodLock::query()
                ->where('month', $month)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $period || ! $period->is_locked) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d không đang khóa.', $month, $year));
            }

            $inWorkflow = Payroll::query()
                ->where('month', $month)
                ->where('year', $year)
                ->whereNotIn('status', PayrollPaymentWorkflowService::recalculableStatuses())
                ->exists();

            if ($inWorkflow) {
                throw new RuntimeException(
                    'Kỳ đã có phiếu vào vòng HR kiểm tra / Giám đốc duyệt / NV xác nhận / đã thanh toán. Không mở khóa để sửa nguồn. Dùng vòng sự cố chính thức.'
                );
            }

            $period->update([
                'is_locked' => false,
                'unlocked_at' => now(),
                'unlocked_by' => $actor->id,
                'unlock_reason' => $reason,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_period_unlocked',
                'meta' => sprintf('period:%02d/%d;reason:%s', $month, $year, $reason),
            ]);

            return $period->fresh(['locker', 'unlocker']);
        });
    }
}
