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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PayrollPaymentWorkflowService
{
    public const PENDING = 'pending';

    public const WAITING_CONFIRMATION = 'waiting_confirmation';

    public const READY_FOR_PAYMENT = 'ready_for_payment';

    public const PAID = 'paid';

    public function __construct(protected SalaryService $salaryService)
    {
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            self::PENDING => 'Chờ duyệt',
            self::WAITING_CONFIRMATION => 'Chờ xác nhận của nhân viên',
            self::READY_FOR_PAYMENT => 'Đủ điều kiện thanh toán',
            self::PAID => 'Đã thanh toán',
            'approved' => 'Chờ xác nhận của nhân viên',
            default => $status ?? '—',
        };
    }

    public function canApprove(Payroll $payroll): bool
    {
        return $payroll->status === self::PENDING;
    }

    public function canConfirm(Payroll $payroll): bool
    {
        return in_array($payroll->status, [self::WAITING_CONFIRMATION, 'approved'], true)
            && $payroll->confirmation_status !== 'issue_reported';
    }

    public function canPay(Payroll $payroll): bool
    {
        return $payroll->status === self::READY_FOR_PAYMENT;
    }

    public function canRemediateIssue(Payroll $payroll): bool
    {
        return $payroll->confirmation_status === 'issue_reported'
            && $payroll->status !== self::PAID;
    }

    /**
     * HR/Admin/Kế toán khắc phục sự cố → cập nhật số liệu, gửi lại NV xác nhận.
     */
    public function remediateIssue(Payroll $payroll, array $data, ?User $actor = null): Payroll
    {
        if (! $this->canRemediateIssue($payroll)) {
            throw new RuntimeException('Chỉ khắc phục được phiếu lương đang có báo cáo sự cố.');
        }

        $actor ??= Auth::user();

        return DB::transaction(function () use ($payroll, $data, $actor) {
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

            $token = Str::random(48);
            $previousIssue = $payroll->issue_report;
            $fixNote = trim((string) ($data['fix_note'] ?? ''));

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
                'status' => self::WAITING_CONFIRMATION,
                'confirmation_status' => 'pending',
                'confirmed_at' => null,
                'confirmation_deadline' => now()->addDays(3),
                'confirmation_token' => $token,
                'issue_report' => null,
                'issue_reported_at' => null,
                'sent_at' => now(),
                'sent_by' => $actor?->id,
                'email_status' => 'pending',
            ]);

            $payroll = $payroll->fresh(['employee']);

            $message = 'Bảng lương tháng '.$payroll->display_month.' đã được chỉnh sửa sau báo cáo sự cố. Vui lòng xác nhận lại trong 3 ngày.';
            if ($fixNote !== '') {
                $message .= ' Ghi chú: '.$fixNote;
            }

            $this->notifyEmployee(
                $payroll,
                $actor,
                'Bảng lương đã chỉnh sửa — cần xác nhận lại',
                $message
            );

            $this->sendConfirmationEmail($payroll, true);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'payroll_issue_remediated',
                'meta' => 'payroll:'.$payroll->id.';prev_issue:'.Str::limit((string) $previousIssue, 120),
            ]);

            return $payroll;
        });
    }

    /**
     * HR/Admin duyệt → chờ NV xác nhận + gửi thông báo/email
     */
    public function approve(Payroll $payroll, ?User $actor = null): Payroll
    {
        if (! $this->canApprove($payroll)) {
            throw new RuntimeException('Chỉ duyệt được bảng lương đang chờ duyệt.');
        }

        $actor ??= Auth::user();
        $token = Str::random(48);

        $payroll->update([
            'status' => self::WAITING_CONFIRMATION,
            'confirmation_status' => 'pending',
            'confirmation_deadline' => now()->addDays(3),
            'confirmation_token' => $token,
            'sent_at' => now(),
            'sent_by' => $actor?->id,
            'email_status' => 'pending',
        ]);

        $payroll = $payroll->fresh(['employee']);

        $this->notifyEmployee(
            $payroll,
            $actor,
            'Bảng lương cần xác nhận',
            'Bảng lương tháng '.$payroll->display_month.' đã được duyệt. Vui lòng xác nhận trong 3 ngày.'
        );

        $this->sendConfirmationEmail($payroll);

        ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => 'payroll_approved',
            'meta' => 'payroll:'.$payroll->id,
        ]);

        return $payroll;
    }

    /**
     * NV xác nhận (web hoặc email) → đủ điều kiện thanh toán
     */
    public function confirm(Payroll $payroll, ?User $actor = null, bool $auto = false): Payroll
    {
        if ($payroll->status === self::READY_FOR_PAYMENT || $payroll->status === self::PAID) {
            return $payroll;
        }

        if ($payroll->confirmation_status === 'issue_reported') {
            throw new RuntimeException(
                $auto
                    ? 'Bỏ qua: phiếu đang có báo cáo sự cố, chờ khắc phục.'
                    : 'Bảng lương đang có báo cáo sự cố. Vui lòng chờ Admin/HR/Kế toán khắc phục trước khi xác nhận.'
            );
        }

        if ($payroll->status !== self::WAITING_CONFIRMATION && $payroll->status !== 'approved') {
            throw new RuntimeException('Bảng lương không ở trạng thái chờ xác nhận.');
        }

        $payroll->update([
            'status' => self::READY_FOR_PAYMENT,
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
    }

    /**
     * Tự động chuyển sau 3 ngày không phản hồi
     */
    public function autoMarkReady(): int
    {
        $count = 0;
        $items = Payroll::query()
            ->whereIn('status', [self::WAITING_CONFIRMATION, 'approved'])
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
     * Kế toán/Admin xác nhận thanh toán
     */
    public function markPaid(Payroll $payroll, array $data, ?User $actor = null): Payroll
    {
        if (! $this->canPay($payroll)) {
            throw new RuntimeException('Chỉ thanh toán khi bảng lương đủ điều kiện thanh toán.');
        }

        $actor ??= Auth::user();
        $method = $data['payment_method'] ?? 'bank_transfer';

        return DB::transaction(function () use ($payroll, $data, $actor, $method) {
            $employee = $payroll->employee;
            if (! $employee) {
                throw new RuntimeException('Thiếu thông tin nhân viên.');
            }

            // Cập nhật STK/QR nếu được gửi kèm
            $bankFields = array_filter([
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_holder' => $data['account_holder'] ?? null,
                'qr_image' => $data['qr_image'] ?? null,
            ], fn ($v) => filled($v));

            if ($bankFields !== []) {
                $employee->fill($bankFields)->save();
            }

            $payment = $payroll->salaryPayment;
            if (! $payment) {
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
            }

            $payment = $this->salaryService->processPayment($payment, [
                'payment_method' => $method,
                'bank' => $employee->bank_name,
                'account_number' => $employee->account_number,
                'account_holder' => $employee->account_holder,
                'transaction_code' => $data['transaction_code'] ?? null,
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
                'meta' => 'payroll:'.$payroll->id.';method:'.$method.';by:'.($actor?->id ?? 'system'),
            ]);

            $this->notifyEmployee(
                $payroll->fresh(['employee']),
                $actor,
                'Đã thanh toán lương',
                'Lương tháng '.$payroll->display_month.' đã được thanh toán.'
            );

            return $payroll->fresh(['employee', 'salaryPayment']);
        });
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
        $pending = SalaryReceiveChangeRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi HR xử lý.');
        }

        $qrPath = null;
        if ($qrFile) {
            $qrPath = $qrFile->store('employee-qr-requests', 'public');
        }

        $request = SalaryReceiveChangeRequest::create([
            'employee_id' => $employee->id,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'account_holder' => $data['account_holder'] ?? null,
            'qr_image' => $qrPath,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        $this->notifyHr(
            Auth::user(),
            'Yêu cầu đổi thông tin nhận lương',
            ($employee->name ?? 'Nhân viên').' gửi yêu cầu thay đổi QR/STK.',
            ['change_request_id' => $request->id, 'employee_id' => $employee->id]
        );

        return $request;
    }

    public function reviewBankChangeRequest(SalaryReceiveChangeRequest $request, bool $approve, ?User $actor = null, ?string $note = null): SalaryReceiveChangeRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Yêu cầu đã được xử lý.');
        }

        $actor ??= Auth::user();

        return DB::transaction(function () use ($request, $approve, $actor, $note) {
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
