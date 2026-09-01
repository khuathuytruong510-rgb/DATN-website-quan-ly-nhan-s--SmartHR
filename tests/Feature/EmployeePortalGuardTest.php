<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriodLock;
use App\Models\SalaryHistory;
use App\Models\SalaryPayment;
use App\Models\SalaryReceiveChangeRequest;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalGuardTest extends TestCase
{
    use RefreshDatabase;

    private function seedPeople(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'M']);

        $aliceUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $bobUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);

        $alice = Employee::create([
            'name' => 'Alice',
            'email' => $aliceUser->email,
            'user_id' => $aliceUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPA',
            'bank_name' => 'MB Bank',
            'account_number' => '111122223333',
            'account_holder' => 'NGUYEN VAN A',
        ]);
        $bob = Employee::create([
            'name' => 'Bob',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPB',
        ]);

        return compact('hr', 'director', 'accountant', 'department', 'aliceUser', 'bobUser', 'alice', 'bob');
    }

    private function payroll(Employee $employee, string $status, array $extra = []): Payroll
    {
        return Payroll::create(array_merge([
            'employee_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => $status,
        ], $extra));
    }

    public function test_employee_cannot_put_patch_or_delete_own_attendance(): void
    {
        ['aliceUser' => $user, 'alice' => $employee] = $this->seedPeople();
        $row = Attendance::create(['employee_id' => $employee->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:32:00']);

        $this->actingAs($user)->put(route('me.attendance.update', $row), [
            'check_in' => '08:00:00',
            'employee_id' => 999,
        ])->assertForbidden();

        $this->actingAs($user)->patch('/me/attendance/'.$row->id, ['check_in' => '08:00:00'])->assertForbidden();
        $this->actingAs($user)->delete('/me/attendance/'.$row->id)->assertForbidden();
        $this->assertSame('08:32:00', $row->fresh()->getRawOriginal('check_in'));
    }

    public function test_adjustment_ignores_tampered_identity_and_status_and_blocks_duplicate(): void
    {
        ['aliceUser' => $alice, 'alice' => $aliceEmp, 'bob' => $bob] = $this->seedPeople();
        $mine = Attendance::create(['employee_id' => $aliceEmp->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:32:00']);

        $this->actingAs($alice)->post(route('me.attendance.adjust', $mine), [
            'employee_id' => $bob->id,
            'attendance_id' => 999,
            'status' => AttendanceAdjustmentRequest::APPROVED,
            'reviewed_by' => $alice->id,
            'requested_check_in' => '08:00',
            'reason' => 'Quên chấm',
        ])->assertRedirect();

        $req = AttendanceAdjustmentRequest::where('attendance_id', $mine->id)->first();
        $this->assertNotNull($req);
        $this->assertSame($aliceEmp->id, (int) $req->employee_id);
        $this->assertSame(AttendanceAdjustmentRequest::PENDING, $req->status);
        $this->assertNull($req->reviewed_by);
        $this->assertSame('08:32:00', $mine->fresh()->getRawOriginal('check_in'));

        $this->actingAs($alice)->post(route('me.attendance.adjust', $mine), [
            'requested_check_in' => '07:30',
            'reason' => 'Gửi lần 2',
        ])->assertRedirect();

        $this->assertSame(1, AttendanceAdjustmentRequest::where('attendance_id', $mine->id)->count());
    }

    public function test_employee_cannot_approve_own_adjustment_or_hit_hr_endpoint(): void
    {
        ['aliceUser' => $alice, 'alice' => $aliceEmp, 'hr' => $hr] = $this->seedPeople();
        $row = Attendance::create(['employee_id' => $aliceEmp->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:32:00']);
        $req = AttendanceAdjustmentRequest::create([
            'employee_id' => $aliceEmp->id,
            'attendance_id' => $row->id,
            'work_date' => '2026-08-28',
            'current_check_in' => '08:32:00',
            'requested_check_in' => '08:00:00',
            'reason' => 'Quên',
            'status' => AttendanceAdjustmentRequest::PENDING,
        ]);

        $this->actingAs($alice)->post(route('attendance.adjustments.approve', $req))->assertForbidden();
        $this->assertSame(AttendanceAdjustmentRequest::PENDING, $req->fresh()->status);

        $this->actingAs($hr)->post(route('attendance.adjustments.approve', $req), [
            'review_note' => 'Đối chiếu camera',
        ])->assertRedirect();

        $fresh = $req->fresh();
        $this->assertSame(AttendanceAdjustmentRequest::APPROVED, $fresh->status);
        $this->assertSame($hr->id, (int) $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertNotNull($fresh->applied_at);
        $this->assertStringContainsString('Đối chiếu camera', (string) $fresh->review_note);
        $this->assertSame('08:00:00', $row->fresh()->getRawOriginal('check_in'));
    }

    public function test_locked_period_adjustment_is_exception_and_does_not_rewrite_attendance(): void
    {
        ['aliceUser' => $alice, 'alice' => $aliceEmp, 'hr' => $hr] = $this->seedPeople();
        PayrollPeriodLock::create([
            'month' => 8,
            'year' => 2026,
            'is_locked' => true,
            'locked_by' => $hr->id,
            'locked_at' => now(),
        ]);
        $row = Attendance::create(['employee_id' => $aliceEmp->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:32:00']);

        $this->actingAs($alice)->post(route('me.attendance.adjust', $row), [
            'requested_check_in' => '08:00',
            'reason' => 'Quên chấm sau khi khóa kỳ',
        ])->assertRedirect();

        $req = AttendanceAdjustmentRequest::where('attendance_id', $row->id)->first();
        $this->actingAs($hr)->post(route('attendance.adjustments.approve', $req))->assertRedirect();

        $fresh = $req->fresh();
        $this->assertSame(AttendanceAdjustmentRequest::APPROVED, $fresh->status);
        $this->assertSame($hr->id, (int) $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertNull($fresh->applied_at);
        $this->assertStringContainsString('kỳ đã khóa', mb_strtolower($fresh->review_note));
        $this->assertSame('08:32:00', $row->fresh()->getRawOriginal('check_in'));
    }

    public function test_payroll_confirm_state_matrix_and_status_tamper(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'accountant' => $accountant] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $calculated = $this->payroll($emp, PayrollPaymentWorkflowService::CALCULATED, ['month' => 1]);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $calculated), ['status' => 'paid'])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);

        $checked = $this->payroll($emp, PayrollPaymentWorkflowService::HR_CHECKED, ['month' => 2]);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $checked), ['status' => 'paid'])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $checked->fresh()->status);

        $approved = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED, ['month' => 3]);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $approved), [
            'status' => 'paid',
            'employee_id' => 999,
            'total_salary' => 1,
            'paid_by' => $alice->id,
        ])->assertRedirect();
        $fresh = $approved->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $fresh->status);
        $this->assertSame($emp->id, (int) $fresh->employee_id);
        $this->assertEquals(10000000, (float) $fresh->total_salary);
        $this->assertNull($fresh->paid_at);

        $workflow->markPaid($fresh, ['payment_method' => 'cash'], $accountant);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $fresh))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $fresh->fresh()->status);

        $this->actingAs($alice)->put('/me/payroll/'.$fresh->id, ['status' => 'paid'])->assertForbidden();
        $this->actingAs($alice)->patch('/me/payroll/'.$fresh->id, ['status' => 'paid'])->assertForbidden();
        $this->actingAs($alice)->delete('/me/payroll/'.$fresh->id)->assertForbidden();
    }

    public function test_issue_loop_cannot_skip_to_paid(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'hr' => $hr, 'director' => $director, 'accountant' => $accountant] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED);

        $workflow->reportIssue($payroll->fresh(), 'Sai ngày công', $alice);
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $payroll->fresh()->status);

        try {
            $workflow->markPaid($payroll->fresh(), ['payment_method' => 'cash'], $accountant);
            $this->fail('Không được thanh toán phiếu đang báo sai.');
        } catch (\RuntimeException) {
        }

        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $payroll->fresh()->status);
    }

    public function test_issue_loop_returns_to_hr_then_director_before_confirm(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'hr' => $hr, 'director' => $director] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED);

        $workflow->reportIssue($payroll->fresh(), 'Sai OT', $alice);
        $workflow->remediateIssue($payroll->fresh(), ['base_salary' => 10000000, 'working_salary' => 10000000], $hr);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);

        $this->actingAs($alice)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.review', $payroll->fresh()))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();
        $this->actingAs($alice)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);
    }

    public function test_bank_change_pending_blocks_second_request_and_snapshot_keeps_old_account(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'hr' => $hr, 'director' => $director, 'accountant' => $accountant] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::CALCULATED);

        $workflow->reviewByHr($payroll->fresh(), $hr);
        $this->assertSame('111122223333', $payroll->fresh()->payout_account_number);

        $this->actingAs($alice)->post(route('me.payroll.bank_change'), [
            'bank_name' => 'MB Bank',
            'account_number' => '999988887777',
            'account_holder' => 'NGUYEN VAN A',
            'note' => 'Đổi STK',
        ])->assertRedirect();

        $this->actingAs($alice)->post(route('me.payroll.bank_change'), [
            'bank_name' => 'Vietcombank',
            'account_number' => '000011112222',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertRedirect();

        $this->assertSame(1, SalaryReceiveChangeRequest::where('employee_id', $emp->id)->where('status', 'pending')->count());
        $this->assertSame('111122223333', $emp->fresh()->account_number);

        $pending = SalaryReceiveChangeRequest::where('employee_id', $emp->id)->first();
        $workflow->reviewBankChangeRequest($pending, true, $hr);
        $this->assertSame('999988887777', $emp->fresh()->account_number);
        $this->assertSame('111122223333', $payroll->fresh()->payout_account_number);

        $workflow->approve($payroll->fresh(), $director);
        $workflow->confirm($payroll->fresh(), $alice);
        $paid = $workflow->markPaid($payroll->fresh(), ['payment_method' => 'bank_transfer'], $accountant);
        $payment = SalaryPayment::where('payroll_id', $paid->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('111122223333', $payment->account_number);
    }

    public function test_leave_cancel_pending_and_approved_before_date_not_delete(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp] = $this->seedPeople();
        $pending = LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->actingAs($alice)->post(route('me.leave_requests.cancel', $pending))->assertRedirect();
        $this->assertSame('cancelled', $pending->fresh()->status);
        $this->assertSame($alice->id, (int) $pending->fresh()->cancelled_by);
        $this->assertNotNull($pending->fresh()->cancelled_at);

        $approved = LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'approved',
        ]);
        $this->actingAs($alice)->post(route('me.leave_requests.cancel', $approved), [
            'cancel_reason' => 'Đổi lịch công tác',
        ])->assertRedirect();
        $this->assertSame('cancelled', $approved->fresh()->status);
        $this->assertSame('Đổi lịch công tác', $approved->fresh()->cancel_reason);

        $started = LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'approved',
        ]);
        $this->actingAs($alice)->post(route('me.leave_requests.cancel', $started), [
            'cancel_reason' => 'Muốn hủy giữa chừng',
        ])->assertRedirect();
        $this->assertSame('approved', $started->fresh()->status);

        $response = $this->actingAs($alice)->delete('/me/leave-requests/'.$pending->id);
        $this->assertTrue(in_array($response->status(), [403, 404, 405], true));
        $this->assertDatabaseHas('leave_requests', ['id' => $pending->id]);
    }

    public function test_contract_sign_requires_owner_and_status_and_rejects_second_sign(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'bob' => $bob] = $this->seedPeople();
        $mine = Contract::create([
            'employee_id' => $emp->id,
            'title' => 'HĐ A',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);
        $theirs = Contract::create([
            'employee_id' => $bob->id,
            'title' => 'HĐ B',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);

        $this->actingAs($alice)->post(route('me.contracts.sign', $theirs))->assertForbidden();
        $this->actingAs($alice)->post(route('me.contracts.sign', $mine))->assertRedirect();
        $this->assertNotNull($mine->fresh()->employee_signed_at);
        $this->assertSame(Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, $mine->fresh()->status);

        $signedAt = $mine->fresh()->employee_signed_at;
        $this->actingAs($alice)->post(route('me.contracts.sign', $mine->fresh()))->assertRedirect();
        $this->assertEquals($signedAt?->timestamp, $mine->fresh()->employee_signed_at?->timestamp);
        $this->assertSame(Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, $mine->fresh()->status);
    }

    public function test_support_employee_cannot_resolve_or_see_others(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'bob' => $bob] = $this->seedPeople();
        $mine = SupportRequest::create([
            'employee_id' => $emp->id,
            'subject' => 'Lỗi công',
            'message' => 'Sai giờ',
            'type' => 'attendance',
            'status' => SupportRequest::PENDING,
        ]);
        $theirs = SupportRequest::create([
            'employee_id' => $bob->id,
            'subject' => 'Bob ticket',
            'message' => 'x',
            'type' => 'other',
            'status' => SupportRequest::PENDING,
        ]);

        $this->actingAs($alice)->get(route('me.support_requests.show', $theirs))->assertForbidden();
        $this->actingAs($alice)->get(route('me.support_requests.show', $mine))->assertOk();
        $this->actingAs($alice)->post(route('me.support_requests.follow_up', $mine), [
            'follow_up' => 'Bổ sung',
            'status' => SupportRequest::RESOLVED,
        ])->assertRedirect();
        $this->assertSame(SupportRequest::PENDING, $mine->fresh()->status);
        $this->assertStringContainsString('Bổ sung', (string) $mine->fresh()->follow_up);

        $this->actingAs($alice)->put('/me/support-requests/'.$mine->id, ['status' => 'resolved'])->assertForbidden();
        $this->actingAs($alice)->patch('/me/support-requests/'.$mine->id, ['status' => 'resolved'])->assertForbidden();
        $this->actingAs($alice)->delete('/me/support-requests/'.$mine->id)->assertForbidden();
    }

    public function test_notification_read_is_scoped_to_visible_notifications(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'bob' => $bob, 'hr' => $hr] = $this->seedPeople();
        $forBob = Notification::create([
            'sender_id' => $hr->id,
            'target' => 'employee',
            'title' => 'Cho Bob',
            'message' => 'x',
            'data' => ['employee_id' => $bob->id],
        ]);
        $forHr = Notification::create([
            'sender_id' => $hr->id,
            'target' => 'hr',
            'title' => 'Nội bộ HR',
            'message' => 'x',
        ]);
        $broadcast = Notification::create([
            'sender_id' => $hr->id,
            'target' => 'employee',
            'title' => 'Chung',
            'message' => 'x',
        ]);

        $this->actingAs($alice)->post(route('me.notifications.read', $forBob))->assertForbidden();
        $this->actingAs($alice)->post(route('me.notifications.read', $forHr))->assertForbidden();
        $this->actingAs($alice)->post(route('me.notifications.read', $broadcast))->assertRedirect();
        $this->assertFalse(NotificationRead::where('notification_id', $forBob->id)->where('user_id', $alice->id)->exists());
        $this->assertTrue(NotificationRead::where('notification_id', $broadcast->id)->where('user_id', $alice->id)->exists());
    }

    public function test_employee_cannot_call_hr_accounting_or_director_actions(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp] = $this->seedPeople();
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::CALCULATED);

        $this->actingAs($alice)->post(route('payroll.review', $payroll))->assertForbidden();
        $this->actingAs($alice)->post(route('payroll.approve', $payroll))->assertForbidden();
        $this->actingAs($alice)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($alice)->getJson('/api/employees')->assertForbidden();
        $this->actingAs($alice)->putJson('/api/attendance/1', ['check_in' => '08:00:00'])->assertForbidden();
    }

    public function test_dashboard_leave_remaining_matches_leave_page(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp] = $this->seedPeople();
        LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'approved',
        ]);

        $dash = $this->actingAs($alice)->get(route('me.dashboard'));
        $leave = $this->actingAs($alice)->get(route('me.leave_requests'));
        $dash->assertOk();
        $leave->assertOk();
        $this->assertStringContainsString('1/2', $dash->getContent());
        $this->assertStringContainsString('1/2', $leave->getContent());
    }

    public function test_me_get_routes_are_authenticated_linked_and_owner_scoped(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'bobUser' => $bobUser, 'bob' => $bob, 'hr' => $hr] = $this->seedPeople();
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::CALCULATED);
        $bobsPayroll = $this->payroll($bob, PayrollPaymentWorkflowService::CALCULATED, ['month' => 7]);
        $contract = Contract::create([
            'employee_id' => $emp->id,
            'title' => 'HĐ A',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);
        $bobsContract = Contract::create([
            'employee_id' => $bob->id,
            'title' => 'HĐ B',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);
        $attendance = Attendance::create(['employee_id' => $emp->id, 'date' => '2026-08-27', 'status' => 'present']);
        $bobsAttendance = Attendance::create(['employee_id' => $bob->id, 'date' => '2026-08-27', 'status' => 'present']);
        $bobsPaid = $this->payroll($bob, PayrollPaymentWorkflowService::PAID, ['month' => 1]);
        $history = SalaryHistory::create([
            'employee_id' => $bob->id,
            'payroll_id' => $bobsPaid->id,
            'change_type' => SalaryHistory::CHANGE_PAYMENT,
            'old_salary' => 0,
            'new_salary' => 10000000,
            'effective_date' => '2026-08-01',
            'status' => SalaryHistory::STATUS_APPLIED,
        ]);

        $this->get('/me')->assertRedirect();

        $this->actingAs($hr)->get('/me')->assertRedirect(route('dashboard'));

        $unlinked = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $this->actingAs($unlinked)->get('/me')->assertRedirect(route('me.unlinked'));

        $ok = [
            '/me',
            '/me/profile',
            '/me/profile/edit',
            '/me/attendance',
            '/me/leave-requests',
            '/me/overtime-requests',
            '/me/payroll',
            '/me/salary-histories',
            '/me/contracts',
            '/me/evaluations',
            '/me/benefits',
            '/me/notifications',
            '/me/schedule',
            '/me/support-requests',
            '/me/change-password',
            '/me/activity-logs',
        ];
        foreach ($ok as $path) {
            $this->actingAs($alice)->get($path)->assertOk();
        }

        $this->actingAs($alice)->get('/me/payroll/'.$payroll->id)->assertOk();
        $this->actingAs($alice)->get('/me/payroll/'.$bobsPayroll->id)->assertForbidden();
        $this->actingAs($alice)->get('/me/contracts/'.$contract->id)->assertOk();
        $this->actingAs($alice)->get('/me/contracts/'.$bobsContract->id)->assertForbidden();
        $this->actingAs($alice)->get('/me/attendance/'.$attendance->id)->assertRedirect(route('me.attendance'));
        $this->actingAs($alice)->get('/me/attendance/'.$bobsAttendance->id)->assertForbidden();
        $this->actingAs($alice)->get(route('me.salary_histories.show', $history))->assertForbidden();
        $this->actingAs($bobUser)->get(route('me.salary_histories.show', $history))->assertOk();
    }

    public function test_employee_post_ignores_system_fields_on_leave_overtime_and_support(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'bob' => $bob, 'hr' => $hr] = $this->seedPeople();

        $this->actingAs($alice)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => 'annual',
            'reason' => 'Nghỉ',
            'employee_id' => $bob->id,
            'status' => 'approved',
            'approved_by' => $hr->id,
            'approved_at' => now()->toDateTimeString(),
            'id' => 999,
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $emp->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertSame($emp->id, (int) $leave->employee_id);
        $this->assertSame('pending', $leave->status);
        $this->assertNull($leave->approved_by);

        $this->actingAs($alice)->post(route('me.overtime_requests.store'), [
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'OT',
            'employee_id' => $bob->id,
            'status' => 'approved',
            'approved_by' => $hr->id,
            'approved_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('me.overtime_requests'));

        $ot = OvertimeRequest::where('employee_id', $emp->id)->latest('id')->first();
        $this->assertNotNull($ot);
        $this->assertSame('pending', $ot->status);
        $this->assertNull($ot->approved_by);

        $this->actingAs($alice)->post(route('me.support_requests.store'), [
            'subject' => 'Hỗ trợ',
            'message' => 'Chi tiết',
            'type' => 'other',
            'employee_id' => $bob->id,
            'status' => SupportRequest::RESOLVED,
        ])->assertRedirect(route('me.support_requests'));

        $ticket = SupportRequest::where('employee_id', $emp->id)->latest('id')->first();
        $this->assertNotNull($ticket);
        $this->assertSame(SupportRequest::PENDING, $ticket->status);
    }

    public function test_confirm_is_idempotent_and_does_not_create_salary_history_before_paid(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'accountant' => $accountant] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);
        $payroll = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED);

        $this->actingAs($alice)->post(route('me.payroll.confirm', $payroll))->assertRedirect();
        $this->actingAs($alice)->post(route('me.payroll.confirm', $payroll))->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);
        $this->assertSame(1, Payroll::where('employee_id', $emp->id)->count());
        $this->assertSame(0, SalaryHistory::where('payroll_id', $payroll->id)->count());

        $workflow->markPaid($payroll->fresh(), ['payment_method' => 'cash'], $accountant);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $payroll->fresh()->status);
        $this->assertSame(1, SalaryHistory::where('payroll_id', $payroll->id)->count());
        $this->assertSame(1, SalaryPayment::where('payroll_id', $payroll->id)->count());
    }

    public function test_paid_cancelled_and_confirmed_cannot_revert(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'hr' => $hr, 'accountant' => $accountant] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $paid = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED, ['month' => 4]);
        $workflow->confirm($paid->fresh(), $alice);
        $workflow->markPaid($paid->fresh(), ['payment_method' => 'cash'], $accountant);

        $this->actingAs($alice)->post(route('me.payroll.confirm', $paid->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $paid->fresh()->status);

        try {
            $workflow->reviewByHr($paid->fresh(), $hr);
            $this->fail('PAID không được quay về HR_CHECKED.');
        } catch (\RuntimeException) {
        }
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $paid->fresh()->status);

        $confirmed = $this->payroll($emp, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, ['month' => 5]);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $confirmed), ['status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $confirmed->fresh()->status);

        $cancelled = LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->addDays(8)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'cancelled',
        ]);
        $this->actingAs($alice)->post(route('me.leave_requests.cancel', $cancelled))->assertRedirect();
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    public function test_legacy_statuses_remain_readable_and_follow_current_rules(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp, 'hr' => $hr] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $waiting = $this->payroll($emp, 'waiting_confirmation', ['month' => 6]);
        $this->actingAs($alice)->get(route('me.payrolls'))
            ->assertOk()
            ->assertSee('Xác nhận bảng lương');
        $this->actingAs($alice)->post(route('me.payroll.confirm', $waiting))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $waiting->fresh()->status);

        $oldCalculated = $this->payroll($emp, 'pending', ['month' => 7]);
        $page = $this->actingAs($alice)->get(route('me.payrolls'));
        $page->assertOk()->assertSee('chỉ xem');
        $this->actingAs($alice)->post(route('me.payroll.confirm', $oldCalculated))->assertRedirect();
        $this->assertSame('pending', $oldCalculated->fresh()->status);

        $oldHr = $this->payroll($emp, 'hr_approved', ['month' => 8]);
        $this->actingAs($alice)->post(route('me.payroll.confirm', $oldHr))->assertRedirect();
        $this->assertSame('hr_approved', $oldHr->fresh()->status);
        $this->assertTrue($workflow->isHrChecked($oldHr->fresh()->status));

        $legacyLeave = LeaveRequest::create([
            'employee_id' => $emp->id,
            'start_date' => now()->addDays(12)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->actingAs($alice)->get(route('me.leave_requests'))->assertOk()->assertSee('Chờ duyệt');
        $this->actingAs($alice)->post(route('me.leave_requests.cancel', $legacyLeave))->assertRedirect();
        $this->assertSame('cancelled', $legacyLeave->fresh()->status);

        $dash = $this->actingAs($alice)->get(route('me.dashboard'));
        $dash->assertOk()->assertSee('0/2');
    }

    public function test_payroll_ui_matches_backend_state(): void
    {
        ['aliceUser' => $alice, 'alice' => $emp] = $this->seedPeople();

        $calculated = $this->payroll($emp, PayrollPaymentWorkflowService::CALCULATED, ['month' => 1]);
        $this->actingAs($alice)->get(route('me.payroll.show', $calculated))
            ->assertOk()
            ->assertSee('chỉ xem')
            ->assertDontSee('Xác nhận bảng lương');

        $checked = $this->payroll($emp, PayrollPaymentWorkflowService::HR_CHECKED, ['month' => 2]);
        $this->actingAs($alice)->get(route('me.payroll.show', $checked))
            ->assertOk()
            ->assertSee('chỉ xem')
            ->assertDontSee('Xác nhận bảng lương');

        $approved = $this->payroll($emp, PayrollPaymentWorkflowService::DIRECTOR_APPROVED, ['month' => 3]);
        $this->actingAs($alice)->get(route('me.payroll.show', $approved))
            ->assertOk()
            ->assertSee('Xác nhận bảng lương');

        $confirmed = $this->payroll($emp, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, ['month' => 4]);
        $this->actingAs($alice)->get(route('me.payroll.show', $confirmed))
            ->assertOk()
            ->assertSee('đang chờ kế toán thanh toán')
            ->assertDontSee('Xác nhận bảng lương');

        $paid = $this->payroll($emp, PayrollPaymentWorkflowService::PAID, ['month' => 5, 'paid_at' => now()]);
        $this->actingAs($alice)->get(route('me.payroll.show', $paid))
            ->assertOk()
            ->assertSee('Đã thanh toán');
    }
}
