<?php

namespace App\Services;

use App\Mail\PayrollConfirmationMail;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\SalaryReceiveChangeRequest;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\RequestApprover;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PayrollPaymentWorkflowService
{
    /** Kế toán đang chuẩn bị bảng lương. */
    public const DRAFT = 'draft';

    /** Hệ thống đã tính xong, chờ Kế toán gửi Giám đốc duyệt. */
    public const CALCULATED = 'calculated';

    /** Kế toán đã gửi duyệt (giữ mã DB hr_checked), chờ Giám đốc. */
    public const HR_CHECKED = 'hr_checked';

    /** @deprecated Dùng HR_CHECKED. */
    public const HR_APPROVED = self::HR_CHECKED;

    /** Giám đốc đã phê duyệt — bước cuối; hệ thống thông báo bảng lương đến NV. */
    public const DIRECTOR_APPROVED = 'director_approved';

    /** @deprecated Đã bỏ tính năng sự cố lương — giữ hằng số để đọc dữ liệu cũ. */
    public const PAYROLL_ISSUE = 'payroll_issue';

    /** @deprecated Đã bỏ bước NV xác nhận / thanh toán — giữ để đọc dữ liệu cũ. */
    public const EMPLOYEE_CONFIRMED = 'employee_confirmed';

    /** @deprecated Đã bỏ thanh toán lương — giữ để đọc dữ liệu cũ. */
    public const READY_FOR_PAYMENT = 'ready_for_payment';

    /** @deprecated Đã bỏ thanh toán lương — giữ để đọc dữ liệu cũ. */
    public const PAID = 'paid';

    /** Alias tương thích dữ liệu cũ. */
    public const PENDING = self::CALCULATED;

    public const HR_REVIEWED = self::HR_CHECKED;

    public const WAITING_CONFIRMATION = self::DIRECTOR_APPROVED;

    public static function calculatedStatuses(): array
    {
        return [self::CALCULATED, 'pending'];
    }

    public static function recalculableStatuses(): array
    {
        return [self::DRAFT, self::CALCULATED, 'pending'];
    }

    public static function hrCheckedStatuses(): array
    {
        return [self::HR_CHECKED, 'hr_approved', 'hr_reviewed'];
    }

    /** @deprecated Đọc dữ liệu cũ. Dùng hrCheckedStatuses(). */
    public static function hrApprovedStatuses(): array
    {
        return self::hrCheckedStatuses();
    }

    public static function directorApprovedStatuses(): array
    {
        return [self::DIRECTOR_APPROVED, 'waiting_confirmation', 'approved'];
    }

    /** @deprecated Đã bỏ thanh toán lương. */
    public static function payableStatuses(): array
    {
        return [self::EMPLOYEE_CONFIRMED, self::READY_FOR_PAYMENT];
    }

    /** Trạng thái kết thúc quy trình (GĐ đã duyệt + đã/đang thông báo NV). */
    public static function completedStatuses(): array
    {
        return array_values(array_unique(array_merge(
            self::directorApprovedStatuses(),
            self::payableStatuses(),
            [self::PAID]
        )));
    }

    public function __construct(protected SalaryService $salaryService)
    {
    }

    /** @deprecated Không còn hạn xác nhận NV. */
    public static function confirmationDeadlineFor(Payroll $payroll): Carbon
    {
        $rawMonth = (string) $payroll->getRawOriginal('month');
        if (preg_match('/^(\d{4})-(\d{2})$/', $rawMonth, $matches)) {
            $period = Carbon::create((int) $matches[1], (int) $matches[2], 1);
        } else {
            $period = Carbon::create((int) $payroll->year, (int) $rawMonth, 1);
        }

        $deadline = $period->addMonthNoOverflow()->day(15)->setTime(23, 59);

        return $deadline->lessThanOrEqualTo(now()) ? $deadline->addMonthNoOverflow() : $deadline;
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Nháp — đang chuẩn bị',
            self::CALCULATED, 'pending' => 'Hệ thống đã tính — chờ Kế toán gửi duyệt',
            self::HR_CHECKED, 'hr_approved', 'hr_reviewed' => 'Kế toán đã gửi duyệt — chờ Giám đốc',
            self::DIRECTOR_APPROVED, 'waiting_confirmation', 'approved' => 'Giám đốc đã duyệt — đã thông báo NV',
            self::PAYROLL_ISSUE => 'Trạng thái cũ (đã bỏ sự cố lương)',
            self::EMPLOYEE_CONFIRMED, self::READY_FOR_PAYMENT => 'Trạng thái cũ (đã bỏ xác nhận / thanh toán)',
            self::PAID => 'Trạng thái cũ (đã bỏ thanh toán)',
            default => $status ?? '—',
        };
    }

    public function isCalculated(?string $status): bool
    {
        return in_array($status, self::calculatedStatuses(), true);
    }

    public function isHrChecked(?string $status): bool
    {
        return in_array($status, self::hrCheckedStatuses(), true);
    }

    public function isHrApproved(?string $status): bool
    {
        return $this->isHrChecked($status);
    }

    public function isDirectorApproved(?string $status): bool
    {
        return in_array($status, self::directorApprovedStatuses(), true);
    }

    /** Phiếu đã tính xong, Kế toán được gửi Giám đốc duyệt. */
    public function canSubmitToDirector(Payroll $payroll): bool
    {
        return $this->isCalculated($payroll->status);
    }

    /** @deprecated Dùng canSubmitToDirector() — bước này do Kế toán thực hiện. */
    public function canReviewByHr(Payroll $payroll): bool
    {
        return $this->canSubmitToDirector($payroll);
    }

    public function canFinalApprove(Payroll $payroll): bool
    {
        return $this->isHrApproved($payroll->status);
    }

    public function actorCanSubmitToDirector(?User $user, Payroll $payroll): bool
    {
        return $user && $user->canPayPayroll() && $this->canSubmitToDirector($payroll);
    }

    /** @deprecated Dùng actorCanSubmitToDirector() */
    public function actorCanReview(?User $user, Payroll $payroll): bool
    {
        return $this->actorCanSubmitToDirector($user, $payroll);
    }

    public function actorCanFinalApprove(?User $user, Payroll $payroll): bool
    {
        return $user && $user->canFinalApprovePayroll() && $this->canFinalApprove($payroll);
    }

    public function canApprove(Payroll $payroll): bool
    {
        return $this->canFinalApprove($payroll);
    }

    /** @deprecated Đã bỏ bước NV xác nhận. */
    public function canConfirm(Payroll $payroll): bool
    {
        return false;
    }

    /** @deprecated Đã bỏ thanh toán lương. */
    public function canPay(Payroll $payroll): bool
    {
        return false;
    }

    protected function lockPayroll(Payroll $payroll): Payroll
    {
        return Payroll::query()->whereKey($payroll->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Một cửa kiểm tra transition. Controller không được tự gán status từ request.
     * Quy trình: nháp/đã tính → KT gửi duyệt → GĐ duyệt (kết thúc).
     */
    public function assertTransition(Payroll $payroll, string $to, ?User $actor = null): void
    {
        $allowed = match ($to) {
            self::HR_CHECKED => $this->canSubmitToDirector($payroll) && $actor?->canPayPayroll(),
            self::DIRECTOR_APPROVED => $this->canFinalApprove($payroll) && $actor?->canFinalApprovePayroll(),
            default => false,
        };

        if (! $allowed) {
            throw new RuntimeException('Không được chuyển trạng thái bảng lương theo cách này.');
        }
    }

    /**
     * Kế toán kiểm tra phiếu đã tính → gửi Giám đốc duyệt.
     * Giữ status DB `hr_checked` để tương thích dữ liệu cũ.
     */
    public function submitToDirector(Payroll $payroll, ?User $actor = null): Payroll
    {
        $actor ??= Auth::user();

        return DB::transaction(function () use ($payroll, $actor) {
            $payroll = $this->lockPayroll($payroll);
            $this->assertTransition($payroll, self::HR_CHECKED, $actor);

            $payroll->update([
                'status' => self::HR_CHECKED,
            ]);

            $this->snapshotPayoutAccount($payroll->fresh(['employee']));

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_submitted_to_director',
                'meta' => 'payroll:'.$payroll->id,
            ]);

            return $payroll->fresh(['employee']);
        });
    }

    /** @deprecated Dùng submitToDirector() */
    public function reviewByHr(Payroll $payroll, ?User $actor = null): Payroll
    {
        return $this->submitToDirector($payroll, $actor);
    }

    /**
     * Kế toán gửi duyệt tất cả phiếu đã tính trong kỳ.
     *
     * @return array{ok: int, failed: int}
     */
    public function submitAllToDirector(int $month, int $year, User $actor): array
    {
        if (! $actor->canPayPayroll()) {
            throw new RuntimeException('Chỉ kế toán được gửi bảng lương sang Giám đốc duyệt.');
        }

        $pending = Payroll::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('status', self::calculatedStatuses())
            ->orderBy('id')
            ->get();

        $ok = 0;
        $failed = 0;
        foreach ($pending as $payroll) {
            try {
                $this->submitToDirector($payroll, $actor);
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('ok', 'failed');
    }

    /**
     * @deprecated Đã bỏ thanh toán lương.
     *
     * @return array{ok: int, failed: int}
     */
    public function payAll(int $month, int $year, User $actor, array $data = []): array
    {
        throw new RuntimeException('Đã bỏ chức năng thanh toán lương. Quy trình dừng ở Giám đốc duyệt và thông báo bảng lương.');
    }

    /**
     * Giám đốc phê duyệt cuối → thông báo bảng lương đến nhân viên (bước kết thúc).
     */
    public function approve(Payroll $payroll, ?User $actor = null): Payroll
    {
        $actor ??= Auth::user();

        $payroll = DB::transaction(function () use ($payroll, $actor) {
            $payroll = $this->lockPayroll($payroll);
            $this->assertTransition($payroll, self::DIRECTOR_APPROVED, $actor);

            $payroll->update([
                'status' => self::DIRECTOR_APPROVED,
                'confirmation_status' => 'notified',
                'confirmation_deadline' => null,
                'confirmation_token' => null,
                'sent_at' => now(),
                'sent_by' => $actor->id,
                'director_approved_by' => $actor->id,
                'director_approved_name' => $actor->name,
                'director_approved_at' => now(),
                'email_status' => 'pending',
            ]);

            $payroll = $payroll->fresh(['employee']);
            $this->snapshotPayoutAccount($payroll);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_final_approved',
                'meta' => 'payroll:'.$payroll->id,
            ]);

            return $payroll->fresh(['employee']);
        });

        // Email/thông báo không phải bước chuyển trạng thái. SMTP lỗi không rollback duyệt.
        try {
            $this->notifyEmployee(
                $payroll,
                $actor,
                'Bảng lương đã được phê duyệt',
                'Bảng lương tháng '.$payroll->display_month.' đã được Giám đốc phê duyệt. Bạn có thể xem chi tiết trên trang Lương.'
            );
        } catch (\Throwable) {
        }

        $this->sendPayrollNotifyEmail($payroll->fresh(['employee']));

        return $payroll->fresh(['employee']);
    }

    /**
     * Legacy no-op: gửi duyệt (Kế toán) và phê duyệt (Giám đốc) là thao tác thủ công.
     *
     * @return array{reviewed: int, approved: int}
     */
    public function autoFinalizePeriod(int $month, int $year): array
    {
        return ['reviewed' => 0, 'approved' => 0];
    }

    /** @return array{0: ?Payroll, 1: bool} */
    protected function autoReviewBySystem(Payroll $payroll): array
    {
        return DB::transaction(function () use ($payroll): array {
            $payroll = $this->lockPayroll($payroll);

            if ($this->isHrChecked($payroll->status)) {
                return [$payroll->fresh(['employee']), false];
            }
            if (! $this->isCalculated($payroll->status)) {
                return [null, false];
            }

            $payroll->update(['status' => self::HR_CHECKED]);
            $payroll = $payroll->fresh(['employee']);
            $this->snapshotPayoutAccount($payroll);
            $this->logSystemPayrollAction('payroll_auto_hr_checked', $payroll);

            return [$payroll->fresh(['employee']), true];
        });
    }

    protected function autoApproveBySystem(Payroll $payroll): bool
    {
        $payroll = DB::transaction(function () use ($payroll): ?Payroll {
            $payroll = $this->lockPayroll($payroll);
            if (! $this->isHrChecked($payroll->status)) {
                return null;
            }

            $payroll->update([
                'status' => self::DIRECTOR_APPROVED,
                'confirmation_status' => 'notified',
                'confirmation_deadline' => null,
                'confirmation_token' => null,
                'sent_at' => now(),
                'sent_by' => null,
                'director_approved_by' => null,
                'director_approved_name' => 'Hệ thống (tự động)',
                'director_approved_at' => now(),
                'email_status' => 'pending',
            ]);

            $payroll = $payroll->fresh(['employee']);
            $this->snapshotPayoutAccount($payroll);
            $this->logSystemPayrollAction('payroll_auto_final_approved', $payroll);

            return $payroll->fresh(['employee']);
        });

        if (! $payroll) {
            return false;
        }

        try {
            $this->notifyEmployee(
                $payroll,
                null,
                'Bảng lương đã được phê duyệt',
                'Bảng lương tháng '.$payroll->display_month.' đã được phê duyệt. Bạn có thể xem chi tiết trên trang Lương.'
            );
        } catch (\Throwable) {
        }

        $this->sendPayrollNotifyEmail($payroll->fresh(['employee']));

        return true;
    }

    protected function logSystemPayrollAction(string $action, Payroll $payroll): void
    {
        $userId = User::query()
            ->where(fn ($query) => $query->where('is_hr', true)->orWhere('is_admin', true))
            ->orderBy('id')
            ->value('id');

        if ($userId) {
            ActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'meta' => sprintf('payroll:%d;period:%02d/%d;by:system', $payroll->id, $payroll->month, $payroll->year),
            ]);
        }
    }

    /** @deprecated Đã bỏ bước NV xác nhận. */
    public function confirm(Payroll $payroll, ?User $actor = null, bool $auto = false): Payroll
    {
        throw new RuntimeException('Đã bỏ bước nhân viên xác nhận bảng lương. Quy trình dừng ở Giám đốc duyệt và thông báo.');
    }

    /** @deprecated Đã bỏ bước tự xác nhận / thanh toán. */
    public function autoMarkReady(): int
    {
        return 0;
    }

    /** @deprecated Đã bỏ thanh toán lương. */
    public function markPaid(Payroll $payroll, array $data, ?User $actor = null): Payroll
    {
        throw new RuntimeException('Đã bỏ chức năng thanh toán lương. Quy trình dừng ở Giám đốc duyệt và thông báo bảng lương.');
    }

    public function updateEmployeeBank(Employee $employee, array $data, $qrFile = null): Employee
    {
        if ($qrFile) {
            if ($employee->qr_image) {
                Storage::disk('public')->delete($employee->qr_image);
            }
            $data['qr_image'] = $qrFile->store('employee-qr', 'public');
        }

        $employee->fill(array_filter([
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'account_holder' => $data['account_holder'] ?? null,
            'qr_image' => $data['qr_image'] ?? null,
        ], fn ($v) => $v !== null))->save();

        return $employee->fresh();
    }

    public function submitBankChangeRequest(Employee $employee, array $data, $qrFile = null): SalaryReceiveChangeRequest
    {
        return DB::transaction(function () use ($employee, $data, $qrFile) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $pending = SalaryReceiveChangeRequest::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi '.RequestApprover::queueLabel($employee).' xử lý.');
            }

            $qrPath = null;
            if ($qrFile) {
                $qrPath = $qrFile->store('employee-qr-requests', 'public');
            }

            try {
                $request = SalaryReceiveChangeRequest::create([
                    'employee_id' => $employee->id,
                    'bank_name' => $data['bank_name'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'account_holder' => $data['account_holder'] ?? null,
                    'qr_image' => $qrPath,
                    'note' => $data['note'] ?? null,
                    'status' => 'pending',
                ]);
            } catch (QueryException) {
                throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi '.RequestApprover::queueLabel($employee).' xử lý.');
            }

            RequestApprover::notifyQueue(
                $employee,
                Auth::user(),
                'Yêu cầu đổi thông tin nhận lương',
                ($employee->name ?? 'Nhân viên').' gửi yêu cầu thay đổi QR/STK.',
                ['type' => 'bank_change', 'change_request_id' => $request->id]
            );

            return $request;
        });
    }

    public function reviewBankChangeRequest(SalaryReceiveChangeRequest $request, bool $approve, ?User $actor = null, ?string $note = null): SalaryReceiveChangeRequest
    {
        $actor ??= Auth::user();

        return DB::transaction(function () use ($request, $approve, $actor, $note) {
            $request = SalaryReceiveChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $request->loadMissing('employee');
            if ($request->status !== 'pending') {
                throw new RuntimeException('Yêu cầu đã được xử lý.');
            }
            if (! RequestApprover::canReview($actor, $request->employee)) {
                throw new RuntimeException(
                    RequestApprover::needsDirector($request->employee)
                        ? 'Yêu cầu đổi STK/QR của HR do Giám đốc duyệt.'
                        : 'Chỉ HR được duyệt yêu cầu đổi STK/QR của nhân viên.'
                );
            }

            if ($approve) {
                $employee = $request->employee;
                $update = array_filter([
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_holder' => $request->account_holder,
                ], fn ($v) => filled($v));

                if ($request->qr_image) {
                    if ($employee->qr_image) {
                        Storage::disk('public')->delete($employee->qr_image);
                    }
                    $update['qr_image'] = $request->qr_image;
                }

                if ($update !== []) {
                    $employee->update($update);
                }
            }

            $request->update([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => $approve ? 'bank_change_approved' : 'bank_change_rejected',
                'meta' => 'request:'.$request->id.';employee:'.$request->employee_id,
            ]);

            if ($approve) {
                HrApprovalNotifier::approved($request->employee_id, $actor, 'Yêu cầu đổi STK/QR', [
                    'type' => 'bank_change',
                    'change_request_id' => $request->id,
                ]);
            } else {
                HrApprovalNotifier::rejected($request->employee_id, $actor, 'Yêu cầu đổi STK/QR', $note, [
                    'type' => 'bank_change',
                    'change_request_id' => $request->id,
                ]);
            }

            return $request->fresh(['employee']);
        });
    }

    /**
     * Chốt STK dùng cho kỳ đang xử lý. Không ghi đè nếu đã snapshot.
     */
    protected function snapshotPayoutAccount(Payroll $payroll): void
    {
        if (filled($payroll->payout_account_number) || filled($payroll->payout_bank_name)) {
            return;
        }

        $employee = $payroll->employee;
        if (! $employee) {
            return;
        }

        $payroll->forceFill([
            'payout_bank_name' => $employee->bank_name,
            'payout_account_number' => $employee->account_number,
            'payout_account_holder' => $employee->account_holder,
        ])->save();
    }

    /** Gửi email thông báo bảng lương (không còn nút xác nhận). */
    protected function sendPayrollNotifyEmail(Payroll $payroll, bool $isRevision = false): void
    {
        $employee = $payroll->employee;
        if (! $employee || ! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            $payroll->update(['email_status' => 'failed']);

            return;
        }

        try {
            Mail::to($employee->email)->send(new PayrollConfirmationMail($payroll, $isRevision));
            $payroll->update(['email_status' => 'sent']);
        } catch (\Throwable) {
            $payroll->update(['email_status' => 'failed']);
        }
    }

    /** @deprecated Dùng sendPayrollNotifyEmail(). */
    protected function sendConfirmationEmail(Payroll $payroll, bool $isRevision = false): void
    {
        $this->sendPayrollNotifyEmail($payroll, $isRevision);
    }

    protected function notifyEmployee(Payroll $payroll, ?User $actor, string $title, string $message): void
    {
        if (! $payroll->employee_id) {
            return;
        }

        $this->notifyEmployeeById($payroll->employee_id, $actor, $title, $message, [
            'payroll_id' => $payroll->id,
        ]);
    }

    protected function notifyEmployeeById(int $employeeId, ?User $actor, string $title, string $message, array $data = []): void
    {
        Notification::create([
            'sender_id' => $actor?->id,
            'target' => 'employee',
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => array_merge($data, ['employee_id' => $employeeId]),
        ]);
    }

    protected function notifyHr(?User $actor, string $title, string $message, array $data = []): void
    {
        Notification::create([
            'sender_id' => $actor?->id,
            'target' => 'hr',
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => $data,
        ]);
    }
}
