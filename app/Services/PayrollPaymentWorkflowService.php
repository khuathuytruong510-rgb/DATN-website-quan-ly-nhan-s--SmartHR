<?php

namespace App\Services;

use App\Mail\PayrollConfirmationMail;
use App\Mail\SalaryPaidMail;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\SalaryHistory;
use App\Models\SalaryPayment;
use App\Models\SalaryReceiveChangeRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PayrollPaymentWorkflowService
{
    /** Kế toán đang chuẩn bị bảng lương. */
    public const DRAFT = 'draft';

    /** Kế toán đã tính xong, chờ HR kiểm tra dữ liệu nhân sự. */
    public const CALCULATED = 'calculated';

    /** HR đã kiểm tra/xác nhận dữ liệu bảng lương, chờ Giám đốc. */
    public const HR_CHECKED = 'hr_checked';

    /** @deprecated Dùng HR_CHECKED. */
    public const HR_APPROVED = self::HR_CHECKED;

    /** Giám đốc đã phê duyệt, chờ nhân viên xác nhận. */
    public const DIRECTOR_APPROVED = 'director_approved';

    /** Nhân viên báo sự cố trên phiếu đã phát hành. */
    public const PAYROLL_ISSUE = 'payroll_issue';

    /** Nhân viên đã xác nhận phiếu lương. */
    public const EMPLOYEE_CONFIRMED = 'employee_confirmed';

    /** Đủ điều kiện thanh toán (sau khi NV xác nhận). */
    public const READY_FOR_PAYMENT = 'ready_for_payment';

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
        return [self::DRAFT, self::CALCULATED, self::PAYROLL_ISSUE, 'pending'];
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

    public static function payableStatuses(): array
    {
        return [self::EMPLOYEE_CONFIRMED, self::READY_FOR_PAYMENT];
    }

    public function __construct(protected SalaryService $salaryService)
    {
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Nháp — kế toán đang chuẩn bị',
            self::CALCULATED, 'pending' => 'Kế toán đã tính — chờ HR kiểm tra',
            self::HR_CHECKED, 'hr_approved', 'hr_reviewed' => 'HR đã kiểm tra dữ liệu — chờ Giám đốc',
            self::DIRECTOR_APPROVED, 'waiting_confirmation', 'approved' => 'Giám đốc đã duyệt — chờ NV xác nhận',
            self::PAYROLL_ISSUE => 'Sự cố lương — chờ HR/Kế toán xử lý',
            self::EMPLOYEE_CONFIRMED => 'NV đã xác nhận — đủ điều kiện thanh toán',
            self::READY_FOR_PAYMENT => 'NV đã xác nhận — đủ điều kiện thanh toán',
            self::PAID => 'Đã thanh toán',
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

    public function canReviewByHr(Payroll $payroll): bool
    {
        return $this->isCalculated($payroll->status);
    }

    public function canFinalApprove(Payroll $payroll): bool
    {
        return $this->isHrApproved($payroll->status);
    }

    public function actorCanReview(?User $user, Payroll $payroll): bool
    {
        return $user && $user->is_hr && $this->canReviewByHr($payroll);
    }

    public function actorCanFinalApprove(?User $user, Payroll $payroll): bool
    {
        return $user && $user->is_director && $this->canFinalApprove($payroll);
    }

    public function canApprove(Payroll $payroll): bool
    {
        return $this->canFinalApprove($payroll);
    }

    public function canConfirm(Payroll $payroll): bool
    {
        return $this->isDirectorApproved($payroll->status)
            && $payroll->status !== self::PAYROLL_ISSUE
            && $payroll->confirmation_status !== 'issue_reported';
    }

    public function canPay(Payroll $payroll): bool
    {
        return in_array($payroll->status, self::payableStatuses(), true);
    }

    protected function lockPayroll(Payroll $payroll): Payroll
    {
        return Payroll::query()->whereKey($payroll->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Một cửa kiểm tra transition. Controller không được tự gán status từ request.
     */
    public function assertTransition(Payroll $payroll, string $to, ?User $actor = null): void
    {
        $allowed = match ($to) {
            self::HR_CHECKED => $this->canReviewByHr($payroll) && $actor?->is_hr,
            self::DIRECTOR_APPROVED => $this->canFinalApprove($payroll) && $actor?->is_director,
            self::EMPLOYEE_CONFIRMED => $this->canConfirm($payroll),
            self::PAYROLL_ISSUE => $this->canReportIssue($payroll),
            self::CALCULATED => $this->canRemediateIssue($payroll),
            self::PAID => $this->canPay($payroll) && $actor?->is_accountant,
            default => false,
        };

        if (! $allowed) {
            throw new RuntimeException('Không được chuyển trạng thái bảng lương theo cách này.');
        }
    }

    public function canReportIssue(Payroll $payroll): bool
    {
        if ($payroll->status === self::PAID || $payroll->status === self::PAYROLL_ISSUE) {
            return false;
        }

        return $this->isDirectorApproved($payroll->status)
            || in_array($payroll->status, self::payableStatuses(), true);
    }

    public function reportIssue(Payroll $payroll, string $issue, ?User $actor = null): Payroll
    {
        $actor ??= Auth::user();

        return DB::transaction(function () use ($payroll, $issue, $actor) {
            $payroll = $this->lockPayroll($payroll);
            $this->assertTransition($payroll, self::PAYROLL_ISSUE, $actor);

            $payroll->update([
                'status' => self::PAYROLL_ISSUE,
                'issue_report' => $issue,
                'issue_reported_at' => now(),
                'confirmation_status' => 'issue_reported',
                'confirmation_token' => null,
                'confirmed_at' => null,
            ]);

            ActivityLog::create([
                'user_id' => $actor?->id ?? Auth::id(),
                'action' => 'payroll_issue_reported',
                'meta' => sprintf('payroll:%d;reason:%s', $payroll->id, Str::limit($issue, 200)),
            ]);

            $payroll = $payroll->fresh(['employee']);
            $employeeName = optional($payroll->employee)->name ?? 'Nhân viên';
            $period = sprintf('%02d/%d', $payroll->month, $payroll->year);
            $this->notifyHr(
                $actor,
                "Báo sự cố lương — {$employeeName}",
                "Nhân viên {$employeeName} báo sự cố phiếu lương tháng {$period} (mã #{$payroll->id}):\n{$issue}",
                ['payroll_id' => $payroll->id, 'type' => 'payroll_issue']
            );

            return $payroll;
        });
    }

    public function canRemediateIssue(Payroll $payroll): bool
    {
        return $payroll->status === self::PAYROLL_ISSUE
            || $payroll->confirmation_status === 'issue_reported';
    }

    /**
     * HR/Kế toán khắc phục sự cố → tính lại (calculated), HR phải kiểm tra lại.
     */
    public function remediateIssue(Payroll $payroll, array $data, ?User $actor = null): Payroll
    {
        if (! $this->canRemediateIssue($payroll) || $payroll->status === self::PAID) {
            throw new RuntimeException('Chỉ khắc phục được phiếu lương đang có báo cáo sự cố.');
        }

        $actor ??= Auth::user();

        return DB::transaction(function () use ($payroll, $data, $actor) {
            $payroll = $this->lockPayroll($payroll);
            $this->assertTransition($payroll, self::CALCULATED, $actor);

            $workingSalary = (float) ($data['working_salary'] ?? $payroll->working_salary ?? 0);
            $overtimeSalary = (float) ($data['overtime_salary'] ?? $payroll->overtime_salary ?? 0);
            $allowance = (float) ($data['allowance'] ?? $payroll->allowance ?? 0);
            $bonus = (float) ($data['bonus'] ?? $payroll->bonus ?? 0);
            $insurance = (float) ($data['insurance'] ?? $payroll->insurance ?? 0);
            $tax = (float) ($data['tax'] ?? $payroll->tax ?? 0);
            $deduction = (float) ($data['deduction'] ?? $payroll->deduction ?? 0);
            $latePenaltyFee = (float) ($data['late_penalty_fee'] ?? $payroll->late_penalty_fee ?? 0);
            $baseSalary = (float) ($data['base_salary'] ?? $payroll->base_salary ?? 0);

            $total = round(
                $workingSalary + $overtimeSalary + $allowance + $bonus - $insurance - $tax - $deduction - $latePenaltyFee,
                2
            );

            $previousIssue = $payroll->issue_report;
            $amountBefore = (float) $payroll->total_salary;

            $payroll->update([
                'base_salary' => $baseSalary,
                'working_salary' => $workingSalary,
                'overtime_salary' => $overtimeSalary,
                'allowance' => $allowance,
                'bonus' => $bonus,
                'insurance' => $insurance,
                'tax' => $tax,
                'deduction' => $deduction,
                'late_penalty_fee' => $latePenaltyFee,
                'total_salary' => $total,
                'status' => self::CALCULATED,
                'confirmation_status' => 'pending',
                'confirmed_at' => null,
                'confirmation_deadline' => null,
                'confirmation_token' => null,
                'issue_report' => null,
                'issue_reported_at' => null,
                'sent_at' => null,
                'email_status' => 'pending',
            ]);

            $payroll = $payroll->fresh(['employee']);
            $this->snapshotPayoutAccount($payroll);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'payroll_issue_remediated',
                'meta' => sprintf(
                    'payroll:%d;prev_issue:%s;amount_before:%s;amount_after:%s',
                    $payroll->id,
                    Str::limit((string) $previousIssue, 120),
                    $amountBefore,
                    $total
                ),
            ]);

            return $payroll;
        });
    }

    /**
     * HR kiểm tra dữ liệu nhân sự trên bảng lương (không phải phê duyệt tài chính).
     */
    public function reviewByHr(Payroll $payroll, ?User $actor = null): Payroll
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
                'action' => 'payroll_hr_checked',
                'meta' => 'payroll:'.$payroll->id,
            ]);

            return $payroll->fresh(['employee']);
        });
    }

    /**
     * Giám đốc phê duyệt cuối → phát hành phiếu, chờ NV xác nhận + gửi thông báo/email.
     */
    public function approve(Payroll $payroll, ?User $actor = null): Payroll
    {
        $actor ??= Auth::user();

        $payroll = DB::transaction(function () use ($payroll, $actor) {
            $payroll = $this->lockPayroll($payroll);
            $this->assertTransition($payroll, self::DIRECTOR_APPROVED, $actor);

            $token = Str::random(48);

            $payroll->update([
                'status' => self::WAITING_CONFIRMATION,
                'confirmation_status' => 'pending',
                'confirmation_deadline' => now()->addDays(3),
                'confirmation_token' => $token,
                'sent_at' => now(),
                'sent_by' => $actor->id,
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
                'Bảng lương cần xác nhận',
                'Bảng lương tháng '.$payroll->display_month.' đã được Giám đốc phê duyệt. Vui lòng xác nhận trong 3 ngày.'
            );
        } catch (\Throwable) {
        }

        $this->sendConfirmationEmail($payroll->fresh(['employee']));

        return $payroll->fresh(['employee']);
    }

    /**
     * NV xác nhận (web hoặc email) → đủ điều kiện thanh toán
     */
    public function confirm(Payroll $payroll, ?User $actor = null, bool $auto = false): Payroll
    {
        return DB::transaction(function () use ($payroll, $actor, $auto) {
            $payroll = $this->lockPayroll($payroll);

            if ($payroll->status === self::PAID) {
                throw new RuntimeException('Phiếu đã thanh toán. Không thể xác nhận lại.');
            }

            if (in_array($payroll->status, self::payableStatuses(), true)) {
                return $payroll;
            }

            $this->assertTransition($payroll, self::EMPLOYEE_CONFIRMED, $actor);

            $payroll->update([
                'status' => self::EMPLOYEE_CONFIRMED,
                'confirmation_status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmation_token' => null,
            ]);

            ActivityLog::create([
                'user_id' => $actor?->id ?? Auth::id(),
                'action' => $auto ? 'payroll_auto_ready' : 'payroll_confirmed',
                'meta' => 'payroll:'.$payroll->id,
            ]);

            return $payroll->fresh(['employee']);
        });
    }

    /**
     * Tự động chuyển sau 3 ngày không phản hồi
     */
    public function autoMarkReady(): int
    {
        $count = 0;
        $items = Payroll::query()
            ->whereIn('status', self::directorApprovedStatuses())
            ->where(function ($q) {
                $q->whereNull('confirmation_status')
                    ->orWhere('confirmation_status', '!=', 'issue_reported');
            })
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('confirmation_deadline')
                        ->where('confirmation_deadline', '<=', now());
                })->orWhere(function ($q2) {
                    $q2->whereNull('confirmation_deadline')
                        ->whereNotNull('sent_at')
                        ->where('sent_at', '<=', now()->subDays(3));
                });
            })
            ->get();

        foreach ($items as $payroll) {
            try {
                $this->confirm($payroll, null, true);
                $count++;
            } catch (RuntimeException) {
                // Phiếu đang sự cố hoặc không hợp lệ — bỏ qua, không làm fail cả batch.
            }
        }

        return $count;
    }

    /**
     * Kế toán xác nhận thanh toán
     */
    public function markPaid(Payroll $payroll, array $data, ?User $actor = null): Payroll
    {
        $actor ??= Auth::user();

        $payroll = DB::transaction(function () use ($payroll, $data, $actor) {
            $payroll = Payroll::query()->whereKey($payroll->id)->lockForUpdate()->firstOrFail();

            if ($payroll->status === self::PAID) {
                throw new RuntimeException('Phiếu đã thanh toán. Không thể thanh toán lần hai.');
            }

            $this->assertTransition($payroll, self::PAID, $actor);

            $method = $data['payment_method'] ?? 'bank_transfer';
            $employee = $payroll->employee;
            if (! $employee) {
                throw new RuntimeException('Thiếu thông tin nhân viên.');
            }

            $this->snapshotPayoutAccount($payroll->fresh(['employee']));
            $payroll = $payroll->fresh(['employee']);
            $employee = $payroll->employee;

            $payment = $payroll->salaryPayment;
            if (! $payment) {
                try {
                    $payment = SalaryPayment::create([
                        'employee_id' => $payroll->employee_id,
                        'payroll_id' => $payroll->id,
                        'code' => 'PAY-'.now()->format('YmdHis').'-'.$payroll->id,
                        'month' => $payroll->month,
                        'year' => $payroll->year,
                        'total' => $payroll->total_salary,
                        'deductions' => (float) ($payroll->insurance ?? 0) + (float) ($payroll->tax ?? 0),
                        'net' => $payroll->total_salary,
                        'status' => 'pending',
                    ]);
                } catch (QueryException) {
                    throw new RuntimeException('Phiếu đã có thanh toán. Không thể thanh toán lần hai.');
                }
            }

            $reference = $data['transaction_code'] ?? $data['payment_reference'] ?? null;

            $payment = $this->salaryService->processPayment($payment, [
                'payment_method' => $method,
                'bank' => $payroll->payout_bank_name ?: $employee->bank_name,
                'account_number' => $payroll->payout_account_number ?: $employee->account_number,
                'account_holder' => $payroll->payout_account_holder ?: $employee->account_holder,
                'transaction_code' => $reference,
                'notes' => $data['notes'] ?? 'Thanh toán lương',
            ]);

            $payroll->update([
                'status' => self::PAID,
                'paid_at' => now(),
                'paid_by' => $actor?->id,
                'payment_method' => $method,
            ]);

            SalaryHistory::recordFromPaidPayroll($payroll->fresh(['employee', 'salaryPayment']), $actor);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'payroll_paid',
                'meta' => sprintf(
                    'payroll:%d;method:%s;by:%s;ref:%s',
                    $payroll->id,
                    $method,
                    $actor?->id ?? 'system',
                    $reference ?: 'cash'
                ),
            ]);

            return $payroll->fresh(['employee', 'salaryPayment']);
        });

        try {
            $this->notifyEmployee(
                $payroll->fresh(['employee']),
                $actor,
                'Đã thanh toán lương',
                'Lương tháng '.$payroll->display_month.' đã được thanh toán.'
            );
        } catch (\Throwable) {
        }

        $payment = $payroll->salaryPayment;
        if ($payment && $payroll->employee && filter_var($payroll->employee->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($payroll->employee->email)->send(new SalaryPaidMail($payment));
            } catch (\Throwable) {
            }
        }

        return $payroll->fresh(['employee', 'salaryPayment']);
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
                throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi HR xử lý.');
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
                throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi HR xử lý.');
            }

            $this->notifyHr(
                Auth::user(),
                'Yêu cầu đổi thông tin nhận lương',
                ($employee->name ?? 'Nhân viên').' gửi yêu cầu thay đổi QR/STK.',
                ['change_request_id' => $request->id, 'employee_id' => $employee->id]
            );

            return $request;
        });
    }

    public function reviewBankChangeRequest(SalaryReceiveChangeRequest $request, bool $approve, ?User $actor = null, ?string $note = null): SalaryReceiveChangeRequest
    {
        $actor ??= Auth::user();

        return DB::transaction(function () use ($request, $approve, $actor, $note) {
            $request = SalaryReceiveChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw new RuntimeException('Yêu cầu đã được xử lý.');
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

            $this->notifyEmployeeById(
                $request->employee_id,
                $actor,
                $approve ? 'Yêu cầu đổi STK/QR đã được duyệt' : 'Yêu cầu đổi STK/QR bị từ chối',
                $approve
                    ? 'Thông tin nhận lương của bạn đã được cập nhật.'
                    : ('Yêu cầu bị từ chối.'.($note ? ' Lý do: '.$note : ''))
            );

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

    protected function sendConfirmationEmail(Payroll $payroll, bool $isRevision = false): void
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
