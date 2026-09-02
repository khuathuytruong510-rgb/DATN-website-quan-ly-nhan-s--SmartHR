<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\DeletionRequest;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriodLock;
use App\Models\SalaryReceiveChangeRequest;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService as W;
use Illuminate\Support\Facades\Schema;

class NavBadgeCounts
{
    /** @return array<string, int> */
    public function for(User $user): array
    {
        if ($user->is_admin && ! $user->is_hr && ! $user->is_director) {
            return [
                'notifications' => $this->unreadNotifications($user, ['all', 'admin']),
                'accounts' => 0,
            ];
        }

        if ($user->is_director && ! $user->is_hr) {
            return $this->forDirector($user);
        }

        if ($user->is_hr) {
            return $this->forHr($user);
        }

        if ($user->is_accountant) {
            return $this->forAccountant($user);
        }

        return $this->forEmployee($user);
    }

    /** @return array<string, int> */
    protected function forDirector(User $user): array
    {
        $notifications = $this->unreadNotifications($user, ['all', 'director']);
        $deletions = DeletionRequest::query()->where('status', DeletionRequest::PENDING)->count();
        $contracts = Contract::query()
            ->whereIn('status', [
                Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
                Contract::STATUS_PENDING_SIGNATURE,
                'waiting_director',
            ])
            ->count();
        $payrollApprove = Payroll::query()
            ->whereIn('status', W::hrCheckedStatuses())
            ->count();
        $unlockRequests = PayrollPeriodLock::query()
            ->where('unlock_request_status', 'pending')
            ->count();
        $leave = LeaveRequest::query()->where('status', 'pending')->count();
        $overtime = OvertimeRequest::query()->where('status', OvertimeRequest::STATUS_PENDING)->count();
        $support = SupportRequest::query()->where('status', SupportRequest::PENDING)->count();

        $approvals = $deletions + $contracts + $payrollApprove + $unlockRequests + $leave + $overtime + $support;

        return [
            'notifications' => $notifications,
            'approvals' => $approvals,
            'deletion_requests' => $deletions,
            'contracts' => $contracts,
            'payroll' => $payrollApprove + $unlockRequests,
            'leave_requests' => $leave,
            'overtime_requests' => $overtime,
            'support_requests' => $support,
            'unlock_requests' => $unlockRequests,
        ];
    }

    /** @return array<string, int> */
    protected function forHr(User $user): array
    {
        $notifications = $this->unreadNotifications($user, ['all', 'hr']);
        $leave = LeaveRequest::query()->where('status', 'pending')->count();
        $overtime = OvertimeRequest::query()->where('status', OvertimeRequest::STATUS_PENDING)->count();
        $support = SupportRequest::query()->where('status', SupportRequest::PENDING)->count();
        $bank = Schema::hasTable('salary_receive_change_requests')
            ? SalaryReceiveChangeRequest::query()->where('status', 'pending')->count()
            : 0;
        $issues = Payroll::query()
            ->where(function ($q) {
                $q->where('status', W::PAYROLL_ISSUE)
                    ->orWhere('confirmation_status', 'issue_reported');
            })
            ->count();
        $payrollReview = Payroll::query()->whereIn('status', W::calculatedStatuses())->count();
        $needVerify = PayrollPeriodLock::query()
            ->where('is_locked', true)
            ->whereNull('hr_verified_at')
            ->where(function ($q) {
                $q->whereNull('unlock_request_status')->orWhere('unlock_request_status', '!=', 'pending');
            })
            ->count();
        $deletions = DeletionRequest::query()->where('status', DeletionRequest::PENDING)->count();

        return [
            'notifications' => $notifications,
            'leave_requests' => $leave,
            'overtime_requests' => $overtime,
            'support_requests' => $support,
            'deletion_requests' => $deletions,
            'payroll' => $payrollReview + $issues + $bank + $needVerify,
            'payroll_review' => $payrollReview,
            'payroll_issues' => $issues,
            'bank_requests' => $bank,
            'period_verify' => $needVerify,
        ];
    }

    /** @return array<string, int> */
    protected function forAccountant(User $user): array
    {
        $payable = Payroll::query()->whereIn('status', W::payableStatuses())->count();
        $issues = Payroll::query()
            ->where(function ($q) {
                $q->where('status', W::PAYROLL_ISSUE)
                    ->orWhere('confirmation_status', 'issue_reported');
            })
            ->count();
        $readyPeriods = PayrollPeriodLock::query()
            ->where('is_locked', true)
            ->whereNotNull('hr_verified_at')
            ->count();

        return [
            'generate' => $readyPeriods,
            'payroll' => $payable,
            'issues' => $issues,
            'notifications' => 0,
        ];
    }

    /** @return array<string, int> */
    protected function forEmployee(User $user): array
    {
        $employee = $user->linkedEmployee();
        $notifications = $this->unreadEmployeeNotifications($user);
        $confirm = 0;
        $contracts = 0;
        $support = 0;

        if ($employee) {
            $confirm = Payroll::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', W::directorApprovedStatuses())
                ->count();
            $contracts = Contract::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', [
                    Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
                    'waiting_employee',
                    'waiting_employee_signature',
                ])
                ->count();
            $support = SupportRequest::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', [SupportRequest::PENDING, 'approved'])
                ->count();
        }

        return [
            'notifications' => $notifications,
            'payroll' => $confirm,
            'contracts' => $contracts,
            'support_requests' => $support,
            'leave_requests' => 0,
            'overtime_requests' => 0,
        ];
    }

    /** @param  list<string>  $targets */
    protected function unreadNotifications(User $user, array $targets): int
    {
        return Notification::query()
            ->whereIn('target', $targets)
            ->where(function ($q) {
                $q->where('is_read', false)->orWhereNull('is_read');
            })
            ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $user->id))
            ->count();
    }

    protected function unreadEmployeeNotifications(User $user): int
    {
        $employee = $user->linkedEmployee();

        return Notification::query()
            ->where(function ($q) use ($employee) {
                $q->where('target', 'all')->orWhere('target', 'employee');
                if ($employee) {
                    $q->orWhere(function ($inner) use ($employee) {
                        $inner->where('target', 'employee')
                            ->where('data->employee_id', $employee->id);
                    });
                }
            })
            ->whereDoesntHave('reads', fn ($r) => $r->where('user_id', $user->id))
            ->count();
    }
}
