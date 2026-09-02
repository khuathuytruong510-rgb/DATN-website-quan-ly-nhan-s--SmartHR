<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
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
        return PayrollPeriodLock::with(['locker', 'unlocker', 'verifier', 'unlockRequester'])
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function isLocked(int $month, int $year): bool
    {
        return (bool) $this->find($month, $year)?->is_locked;
    }

    public function isHrVerified(int $month, int $year): bool
    {
        return (bool) $this->find($month, $year)?->hr_verified_at;
    }

    public function isReadyForCalculation(int $month, int $year): bool
    {
        $period = $this->find($month, $year);

        return $period
            && $period->is_locked
            && $period->hr_verified_at
            && $period->unlock_request_status !== 'pending';
    }

    public function hasPendingUnlockRequest(int $month, int $year): bool
    {
        return ($this->find($month, $year)?->unlock_request_status) === 'pending';
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
                sprintf(
                    'Kỳ %02d/%d chưa chốt. Hệ thống tự chốt sau ngày cuối tháng.',
                    $month,
                    $year
                )
            );
        }

        if ($this->hasPendingUnlockRequest($month, $year)) {
            throw new RuntimeException(
                sprintf('Kỳ %02d/%d đang chờ Giám đốc duyệt mở khóa. Chưa thể tính lương.', $month, $year)
            );
        }

        if (! $this->isHrVerified($month, $year)) {
            throw new RuntimeException(
                sprintf(
                    'Kỳ %02d/%d đã chốt nhưng HR chưa xác nhận kiểm tra nguồn. Kế toán chỉ tính sau khi HR xác nhận.',
                    $month,
                    $year
                )
            );
        }
    }

    public function assertWritableDate(DateTimeInterface|string $date, string $context = 'dữ liệu đầu vào'): void
    {
        $carbon = Carbon::parse($date);
        if ($this->isLocked((int) $carbon->month, (int) $carbon->year)) {
            throw new RuntimeException(
                sprintf(
                    'Kỳ %02d/%d đã chốt. Không thể sửa %s. HR gửi yêu cầu mở khóa để Giám đốc duyệt nếu cần chỉnh.',
                    $carbon->month,
                    $carbon->year,
                    $context
                )
            );
        }
    }

    public function assertWritableRange(DateTimeInterface|string $start, DateTimeInterface|string $end, string $context = 'dữ liệu đầu vào'): void
    {
        if ($this->isRangeLocked($start, $end)) {
            throw new RuntimeException(
                sprintf('Kỳ lương liên quan đã chốt. Không thể sửa %s. Cần Giám đốc duyệt mở khóa.', $context)
            );
        }
    }

    /**
     * Khóa mọi kỳ đã kết thúc (trước tháng hiện tại) nếu chưa chốt.
     */
    public function autoLockCompletedPeriods(?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy();
        $lastCompleted = $asOf->copy()->startOfMonth()->subMonth();

        $cursor = $lastCompleted->copy()->subMonths(23)->startOfMonth();
        if ($cursor->lt(Carbon::create(2025, 1, 1))) {
            $cursor = Carbon::create(2025, 1, 1)->startOfMonth();
        }

        $locked = 0;
        while ($cursor->lte($lastCompleted)) {
            if ($this->lockBySystem((int) $cursor->month, (int) $cursor->year)) {
                $locked++;
            }
            $cursor->addMonth();
        }

        return $locked;
    }

    public function lockBySystem(int $month, int $year): ?PayrollPeriodLock
    {
        return DB::transaction(function () use ($month, $year) {
            $period = $this->lockedRow($month, $year);

            if ($period->is_locked) {
                return null;
            }

            // Không tự khóa lại khi đang chờ GĐ duyệt mở khóa.
            if ($period->unlock_request_status === 'pending') {
                return null;
            }

            $period->fill([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => null,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'unlock_reason' => null,
                'hr_verified_at' => null,
                'hr_verified_by' => null,
                'unlock_request_status' => null,
                'unlock_requested_at' => null,
                'unlock_requested_by' => null,
                'unlock_request_reason' => null,
            ]);
            $period->save();

            $this->logSystem($month, $year, 'payroll_period_auto_locked');

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    /** HR xác nhận đã kiểm tra nguồn → Kế toán được tính. */
    public function markHrVerified(int $month, int $year, User $actor): PayrollPeriodLock
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được xác nhận đã kiểm tra nguồn kỳ lương.');
        }

        return DB::transaction(function () use ($month, $year, $actor) {
            $period = $this->lockedRow($month, $year);

            if (! $period->is_locked) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d chưa chốt. Không thể xác nhận kiểm tra.', $month, $year));
            }
            if ($period->unlock_request_status === 'pending') {
                throw new RuntimeException('Đang chờ Giám đốc duyệt mở khóa. Không thể xác nhận kiểm tra.');
            }
            if ($period->hr_verified_at) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d đã được HR xác nhận kiểm tra.', $month, $year));
            }

            $period->update([
                'hr_verified_at' => now(),
                'hr_verified_by' => $actor->id,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_period_hr_verified',
                'meta' => sprintf('period:%02d/%d', $month, $year),
            ]);

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    /** HR gửi yêu cầu mở khóa → Giám đốc duyệt. */
    public function requestUnlock(int $month, int $year, User $actor, string $reason): PayrollPeriodLock
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được gửi yêu cầu mở khóa kỳ lương.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw new RuntimeException('Cần lý do mở khóa (tối thiểu 10 ký tự). Lý do gửi Giám đốc duyệt.');
        }

        return DB::transaction(function () use ($month, $year, $actor, $reason) {
            $period = $this->lockedRow($month, $year);

            if (! $period->exists || ! $period->is_locked) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d không đang khóa.', $month, $year));
            }
            if ($period->unlock_request_status === 'pending') {
                throw new RuntimeException(sprintf('Kỳ %02d/%d đã có yêu cầu mở khóa chờ Giám đốc duyệt.', $month, $year));
            }

            $inWorkflow = Payroll::query()
                ->where('month', $month)
                ->where('year', $year)
                ->whereNotIn('status', PayrollPaymentWorkflowService::recalculableStatuses())
                ->exists();

            if ($inWorkflow) {
                throw new RuntimeException(
                    'Kỳ đã có phiếu vào vòng duyệt / thanh toán. Không mở khóa nguồn. Dùng vòng sự cố lương.'
                );
            }

            $period->update([
                'unlock_request_status' => 'pending',
                'unlock_requested_at' => now(),
                'unlock_requested_by' => $actor->id,
                'unlock_request_reason' => $reason,
                // Tạm thu hồi xác nhận HR cho đến khi xử lý xong yêu cầu.
                'hr_verified_at' => null,
                'hr_verified_by' => null,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_period_unlock_requested',
                'meta' => sprintf('period:%02d/%d;reason:%s', $month, $year, $reason),
            ]);

            Notification::create([
                'sender_id' => $actor->id,
                'target' => 'director',
                'title' => sprintf('Yêu cầu mở khóa kỳ lương %02d/%d', $month, $year),
                'message' => sprintf(
                    "HR %s đề nghị mở khóa kỳ %02d/%d để chỉnh chấm công / nghỉ phép / OT.\n\nLý do:\n%s",
                    $actor->name,
                    $month,
                    $year,
                    $reason
                ),
                'data' => [
                    'type' => 'payroll_period_unlock_request',
                    'month' => $month,
                    'year' => $year,
                ],
                'is_read' => false,
            ]);

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    public function approveUnlock(int $month, int $year, User $director): PayrollPeriodLock
    {
        if (! $director->is_director) {
            throw new RuntimeException('Chỉ Giám đốc được duyệt mở khóa kỳ lương.');
        }

        return DB::transaction(function () use ($month, $year, $director) {
            $period = $this->lockedRow($month, $year);

            if ($period->unlock_request_status !== 'pending') {
                throw new RuntimeException(sprintf('Kỳ %02d/%d không có yêu cầu mở khóa chờ duyệt.', $month, $year));
            }

            $reason = (string) $period->unlock_request_reason;
            $requesterId = $period->unlock_requested_by;

            $period->update([
                'is_locked' => false,
                'unlocked_at' => now(),
                'unlocked_by' => $director->id,
                'unlock_reason' => $reason,
                'unlock_request_status' => null,
                'unlock_requested_at' => null,
                'unlock_requested_by' => null,
                'unlock_request_reason' => null,
                'hr_verified_at' => null,
                'hr_verified_by' => null,
            ]);

            ActivityLog::create([
                'user_id' => $director->id,
                'action' => 'payroll_period_unlocked',
                'meta' => sprintf('period:%02d/%d;reason:%s;approved_by:director', $month, $year, $reason),
            ]);

            Notification::create([
                'sender_id' => $director->id,
                'target' => 'hr',
                'title' => sprintf('Đã duyệt mở khóa kỳ %02d/%d', $month, $year),
                'message' => sprintf(
                    'Giám đốc %s đã duyệt mở khóa kỳ %02d/%d. HR có thể chỉnh chấm công / nghỉ phép / OT, rồi xác nhận kiểm tra lại.',
                    $director->name,
                    $month,
                    $year
                ),
                'data' => [
                    'type' => 'payroll_period_unlock_approved',
                    'month' => $month,
                    'year' => $year,
                ],
                'is_read' => false,
            ]);

            if ($requesterId) {
                // giữ log
            }

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    public function rejectUnlock(int $month, int $year, User $director, ?string $note = null): PayrollPeriodLock
    {
        if (! $director->is_director) {
            throw new RuntimeException('Chỉ Giám đốc được từ chối mở khóa kỳ lương.');
        }

        return DB::transaction(function () use ($month, $year, $director, $note) {
            $period = $this->lockedRow($month, $year);

            if ($period->unlock_request_status !== 'pending') {
                throw new RuntimeException(sprintf('Kỳ %02d/%d không có yêu cầu mở khóa chờ duyệt.', $month, $year));
            }

            $period->update([
                'unlock_request_status' => null,
                'unlock_requested_at' => null,
                'unlock_requested_by' => null,
                'unlock_request_reason' => null,
            ]);

            ActivityLog::create([
                'user_id' => $director->id,
                'action' => 'payroll_period_unlock_rejected',
                'meta' => sprintf('period:%02d/%d;note:%s', $month, $year, $note ?: '—'),
            ]);

            Notification::create([
                'sender_id' => $director->id,
                'target' => 'hr',
                'title' => sprintf('Từ chối mở khóa kỳ %02d/%d', $month, $year),
                'message' => sprintf(
                    "Giám đốc %s từ chối mở khóa kỳ %02d/%d.\n%s",
                    $director->name,
                    $month,
                    $year,
                    $note ? 'Ghi chú: '.$note : 'Kỳ vẫn khóa — tiếp tục kiểm tra trên dữ liệu hiện có.'
                ),
                'data' => [
                    'type' => 'payroll_period_unlock_rejected',
                    'month' => $month,
                    'year' => $year,
                ],
                'is_read' => false,
            ]);

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    /**
     * Sau khi GĐ mở khóa và HR sửa xong: khóa lại kỳ (thủ công) để tiếp tục kiểm tra.
     * Không đánh dấu verified — HR phải bấm xác nhận kiểm tra riêng.
     */
    public function relockAfterEdit(int $month, int $year, User $actor): PayrollPeriodLock
    {
        if (! $actor->is_hr) {
            throw new RuntimeException('Chỉ HR được khóa lại kỳ sau khi chỉnh sửa.');
        }

        return DB::transaction(function () use ($month, $year, $actor) {
            $period = $this->lockedRow($month, $year);

            if ($period->is_locked) {
                throw new RuntimeException(sprintf('Kỳ %02d/%d đã khóa.', $month, $year));
            }

            $period->fill([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $actor->id,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'hr_verified_at' => null,
                'hr_verified_by' => null,
                'unlock_request_status' => null,
                'unlock_requested_at' => null,
                'unlock_requested_by' => null,
                'unlock_request_reason' => null,
            ]);
            $period->save();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_period_locked',
                'meta' => sprintf('period:%02d/%d;after_edit:1', $month, $year),
            ]);

            return $period->fresh(['locker', 'unlocker', 'verifier', 'unlockRequester']);
        });
    }

    /** @deprecated Dùng relockAfterEdit / lockBySystem */
    public function lock(int $month, int $year, User $actor): PayrollPeriodLock
    {
        return $this->relockAfterEdit($month, $year, $actor);
    }

    /** @deprecated Dùng requestUnlock */
    public function unlock(int $month, int $year, User $actor, string $reason): PayrollPeriodLock
    {
        return $this->requestUnlock($month, $year, $actor, $reason);
    }

    private function lockedRow(int $month, int $year): PayrollPeriodLock
    {
        return PayrollPeriodLock::query()
            ->where('month', $month)
            ->where('year', $year)
            ->lockForUpdate()
            ->first() ?? new PayrollPeriodLock([
                'month' => $month,
                'year' => $year,
            ]);
    }

    private function logSystem(int $month, int $year, string $action): void
    {
        $systemUserId = User::query()->where('is_hr', true)->orderBy('id')->value('id')
            ?? User::query()->where('is_admin', true)->orderBy('id')->value('id');

        if ($systemUserId) {
            ActivityLog::create([
                'user_id' => $systemUserId,
                'action' => $action,
                'meta' => sprintf('period:%02d/%d;by:system', $month, $year),
            ]);
        }
    }
}
