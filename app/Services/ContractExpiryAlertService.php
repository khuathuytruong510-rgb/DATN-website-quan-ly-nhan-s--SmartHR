<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\ContractExpiryAction;
use App\Models\ContractExpiryAlert;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ContractExpiryAlertService
{
    public function __construct(private readonly ContractService $contracts)
    {
    }

    /**
     * Quét hợp đồng đã ký, đồng bộ ACTIVE/EXPIRED theo ngày, gửi cảnh báo theo mốc (mỗi mốc một lần).
     */
    public function dispatch(?CarbonInterface $today = null): int
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $sent = 0;

        $this->lockAccountsForExpiredContracts($today);

        $contracts = Contract::query()
            ->with(['employee', 'renewals', 'latestExpiryAction'])
            ->whereNotNull('employee_signed_at')
            ->whereNotNull('director_signed_at')
            ->whereNotIn('status', [
                Contract::STATUS_CANCELLED,
                Contract::STATUS_TERMINATED,
                Contract::STATUS_REJECTED,
            ])
            ->whereNotNull('end_date')
            ->get();

        foreach ($contracts as $contract) {
            $this->contracts->syncStatus($contract);
            $contract->refresh()->loadMissing(['employee', 'renewals', 'latestExpiryAction']);

            if ($contract->isClosed()) {
                continue;
            }

            foreach ($this->reachedMilestones($contract, $today) as $milestone) {
                $sent += $this->notifyMilestone($contract, $milestone, $today);
            }
        }

        return $sent;
    }

    protected function lockAccountsForExpiredContracts(CarbonInterface $today): int
    {
        $locked = 0;
        $employees = Employee::query()->with(['user', 'contracts'])->get();

        foreach ($employees as $employee) {
            $hasExpiredContract = $employee->contracts->contains(function (Contract $contract) use ($today): bool {
                return $contract->end_date !== null
                    && $contract->end_date->copy()->startOfDay()->lt($today->copy()->startOfDay());
            });

            if (! $hasExpiredContract) {
                continue;
            }

            $hasCurrentContract = $employee->contracts->contains(function (Contract $contract) use ($today): bool {
                if (in_array($contract->status, [
                    Contract::STATUS_EXPIRED,
                    Contract::STATUS_CANCELLED,
                    Contract::STATUS_TERMINATED,
                    Contract::STATUS_REJECTED,
                ], true)) {
                    return false;
                }

                return ($contract->end_date === null || $contract->end_date->copy()->startOfDay()->gte($today->copy()->startOfDay()))
                    && ($contract->start_date === null || $contract->start_date->copy()->startOfDay()->lte($today->copy()->startOfDay()));
            });

            if ($hasCurrentContract) {
                continue;
            }

            $user = $employee->user ?: User::query()->where('email', $employee->email)->first();
            if (! $user || $user->isStaffUser() || $user->is_locked) {
                continue;
            }

            $user->update(['is_locked' => true]);
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'account_locked_contract_expired',
                'meta' => sprintf('employee:%d;reason:all_contracts_expired', $employee->id),
            ]);
            $locked++;
        }

        return $locked;
    }

    /**
     * HR ghi nhận hướng xử lý. Không tự tạo hợp đồng mới.
     */
    public function recordDecision(User $actor, Contract $contract, string $decision, ?string $reason = null): ContractExpiryAction
    {
        if (! $actor->is_hr) {
            throw new \RuntimeException('Chỉ HR được ghi nhận xử lý hợp đồng sắp hết hạn.');
        }

        if (! in_array($decision, [ContractExpiryAction::RENEW, ContractExpiryAction::NOT_RENEW, ContractExpiryAction::WAIT], true)) {
            throw new \InvalidArgumentException('Hướng xử lý không hợp lệ.');
        }

        $contract->loadMissing(['employee', 'renewals']);

        if (! $contract->needsExpiryHandling()) {
            throw new \RuntimeException('Hợp đồng này chưa đến mốc cần xử lý hoặc đã có hợp đồng kế tiếp.');
        }

        $action = DB::transaction(function () use ($actor, $contract, $decision, $reason): ContractExpiryAction {
            $row = ContractExpiryAction::create([
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'decided_by' => $actor->id,
                'decision' => $decision,
                'reason' => $reason,
            ]);

            $this->contracts->log(
                $contract,
                $actor,
                'expiry_handled',
                'HR xử lý hợp đồng sắp hết hạn',
                ['decision' => $decision, 'reason' => $reason]
            );

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'contract_expiry_handled',
                'meta' => sprintf(
                    'contract:%s; employee:%s; decision:%s',
                    $contract->contract_code ?: '#'.$contract->id,
                    $contract->employee?->name ?: $contract->employee_id,
                    $decision
                ),
            ]);

            return $row;
        });

        $this->notifyDecision($actor, $contract, $decision, $reason);

        return $action;
    }

    public function notifyRenewalActivated(Contract $contract, User $actor): void
    {
        $contract->loadMissing('employee');
        $employee = $contract->employee;
        if (! $employee) {
            return;
        }

        $end = optional($contract->end_date)->format('d/m/Y') ?: 'không xác định thời hạn';
        $message = sprintf(
            'Hợp đồng gia hạn của %s đã được Giám đốc ký và có hiệu lực từ %s đến %s.',
            $employee->name,
            optional($contract->start_date)->format('d/m/Y') ?: '—',
            $end
        );

        HrApprovalNotifier::send(
            (int) $employee->id,
            $actor,
            'Kết quả gia hạn hợp đồng',
            $message,
            [
                'type' => 'contract_renewal_result',
                'contract_id' => $contract->id,
                'parent_contract_id' => $contract->parent_contract_id,
            ]
        );

        $this->createNotification('hr', 'Hợp đồng gia hạn đã có hiệu lực', $message, [
            'type' => 'contract_renewal_result',
            'contract_id' => $contract->id,
            'employee_id' => $employee->id,
        ], $actor);
    }

    /**
     * @return list<string>
     */
    protected function reachedMilestones(Contract $contract, CarbonInterface $today): array
    {
        $days = $contract->daysUntilExpiry($today);
        if ($days === null) {
            return [];
        }

        $latest = $contract->latestExpiryAction;
        if ($latest && $latest->decision === ContractExpiryAction::NOT_RENEW) {
            return [];
        }

        $notice = (int) config('contracts.notice_days', 30);
        $urgent = (int) config('contracts.urgent_days', 7);
        $milestones = [];

        if ($days <= $notice) {
            $milestones[] = ContractExpiryAlert::MILESTONE_30;
        }
        if ($days <= $urgent && $days >= 0) {
            $milestones[] = ContractExpiryAlert::MILESTONE_7;
        }
        if ($days <= 0) {
            $milestones[] = ContractExpiryAlert::MILESTONE_EXPIRED;
        }
        if ($days < 0 && ! $contract->hasSuccessorContract()) {
            $milestones[] = ContractExpiryAlert::MILESTONE_OVERDUE;
        }

        if ($contract->hasSuccessorContract()) {
            $milestones = array_values(array_filter(
                $milestones,
                fn (string $m) => ! in_array($m, [ContractExpiryAlert::MILESTONE_EXPIRED, ContractExpiryAlert::MILESTONE_OVERDUE], true)
            ));
        }

        return $milestones;
    }

    protected function notifyMilestone(Contract $contract, string $milestone, CarbonInterface $today): int
    {
        $sent = 0;
        foreach ($this->recipientsFor($milestone) as $target) {
            if ($target === 'employee' && ! $contract->employee_id) {
                continue;
            }

            $log = ContractExpiryAlert::query()->firstOrCreate(
                [
                    'contract_id' => $contract->id,
                    'milestone' => $milestone,
                    'target' => $target,
                ],
                [
                    'days_remaining' => $contract->daysUntilExpiry($today),
                ]
            );

            if ($log->notification_id) {
                continue;
            }

            $payload = $this->messageFor($contract, $milestone, $target, $today);
            $notification = $this->createNotification($target, $payload['title'], $payload['message'], [
                'type' => 'contract_expiry',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'milestone' => $milestone,
                'days_remaining' => $contract->daysUntilExpiry($today),
                'end_date' => $contract->end_date?->toDateString(),
                'priority' => $this->priority($milestone),
            ]);

            $log->update(['notification_id' => $notification->id]);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return list<string>
     */
    protected function recipientsFor(string $milestone): array
    {
        return match ($milestone) {
            ContractExpiryAlert::MILESTONE_30 => ['hr'],
            ContractExpiryAlert::MILESTONE_7, ContractExpiryAlert::MILESTONE_EXPIRED => ['hr', 'director', 'employee'],
            ContractExpiryAlert::MILESTONE_OVERDUE => ['hr', 'director'],
            default => ['hr'],
        };
    }

    /**
     * @return array{title: string, message: string}
     */
    protected function messageFor(Contract $contract, string $milestone, string $target, CarbonInterface $today): array
    {
        $name = $contract->employee?->name ?: 'nhân viên';
        $end = optional($contract->end_date)->format('d/m/Y') ?: '—';
        $days = $contract->daysUntilExpiry($today) ?? 0;

        return match ($milestone) {
            ContractExpiryAlert::MILESTONE_7 => [
                'title' => '🔴 Hợp đồng sắp hết hạn khẩn cấp',
                'message' => sprintf(
                    'Hợp đồng của %s sẽ hết hạn sau %d ngày (%s). Đề nghị HR xử lý gia hạn hoặc thực hiện thủ tục liên quan.',
                    $name,
                    max($days, 0),
                    $end
                ),
            ],
            ContractExpiryAlert::MILESTONE_EXPIRED => [
                'title' => '🔴 Hợp đồng đã hết hạn',
                'message' => sprintf(
                    'Hợp đồng của %s đã hết hạn vào ngày %s. Vui lòng kiểm tra tình trạng hợp đồng mới.',
                    $name,
                    $end
                ),
            ],
            ContractExpiryAlert::MILESTONE_OVERDUE => [
                'title' => sprintf('🚨 Hợp đồng đã quá hạn %d ngày', abs($days)),
                'message' => sprintf(
                    'Hợp đồng của %s đã hết hạn từ ngày %s và chưa ghi nhận hợp đồng mới. Vui lòng kiểm tra và xử lý.',
                    $name,
                    $end
                ),
            ],
            default => [
                'title' => '⚠️ Hợp đồng sắp hết hạn',
                'message' => $target === 'hr'
                    ? sprintf(
                        'Hợp đồng của %s sẽ hết hạn vào ngày %s. Còn %d ngày. Vui lòng kiểm tra và thực hiện gia hạn hoặc xử lý theo quy định.',
                        $name,
                        $end,
                        $days
                    )
                    : sprintf('Hợp đồng của bạn sẽ hết hạn vào ngày %s. Còn %d ngày.', $end, $days),
            ],
        };
    }

    protected function notifyDecision(User $actor, Contract $contract, string $decision, ?string $reason): void
    {
        $name = $contract->employee?->name ?: 'nhân viên';
        $end = optional($contract->end_date)->format('d/m/Y') ?: '—';
        $note = $reason ? ' Lý do: '.$reason : '';

        if ($decision === ContractExpiryAction::RENEW) {
            $this->createNotification('director', 'Yêu cầu gia hạn hợp đồng cần xem xét', sprintf(
                'HR đề xuất gia hạn hợp đồng của %s (hết hạn %s).%s Hệ thống chưa tạo hợp đồng mới — chờ HR lập hồ sơ và Giám đốc ký/duyệt.',
                $name,
                $end,
                $note
            ), [
                'type' => 'contract_expiry_decision',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'decision' => $decision,
            ], $actor);

            return;
        }

        if ($decision === ContractExpiryAction::NOT_RENEW) {
            $message = sprintf(
                'HR quyết định không gia hạn hợp đồng của %s (hết hạn %s).%s Cần thực hiện quy trình nghỉ việc / chấm dứt hợp đồng.',
                $name,
                $end,
                $note
            );

            $this->createNotification('director', 'Hợp đồng không gia hạn — cần xử lý nghỉ việc', $message, [
                'type' => 'contract_expiry_decision',
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
                'decision' => $decision,
            ], $actor);

            if ($contract->employee_id) {
                HrApprovalNotifier::send(
                    (int) $contract->employee_id,
                    $actor,
                    'Thông báo về hợp đồng lao động',
                    sprintf(
                        'Hợp đồng của bạn hết hạn ngày %s sẽ không được gia hạn.%s Bộ phận Nhân sự sẽ hướng dẫn thủ tục liên quan.',
                        $end,
                        $note
                    ),
                    [
                        'type' => 'contract_expiry_decision',
                        'contract_id' => $contract->id,
                        'decision' => $decision,
                    ]
                );
            }
        }
    }

    protected function createNotification(string $target, string $title, string $message, array $data, ?User $actor = null): Notification
    {
        $senderId = $actor?->id
            ?? User::query()->where('is_hr', true)->value('id')
            ?? User::query()->value('id');

        return Notification::create([
            'sender_id' => $senderId,
            'target' => $target,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => $data,
        ]);
    }

    protected function priority(string $milestone): int
    {
        return match ($milestone) {
            ContractExpiryAlert::MILESTONE_OVERDUE => 1,
            ContractExpiryAlert::MILESTONE_EXPIRED => 2,
            ContractExpiryAlert::MILESTONE_7 => 3,
            default => 4,
        };
    }
}
