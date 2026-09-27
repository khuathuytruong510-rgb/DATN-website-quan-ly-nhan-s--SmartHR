<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayrollTwoStepApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function seedPayroll(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $admin = User::factory()->create(['is_hr' => false, 'is_admin' => true, 'is_accountant' => false, 'is_director' => false]);

        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Manager',
        ]);
        $employee = Employee::create([
            'name' => 'Nguyen Van A',
            'email' => 'employee@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP001',
        ]);
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        return compact('hr', 'director', 'accountant', 'admin', 'employee', 'payroll');
    }

    public function test_accountant_submits_then_director_final_approves(): void
    {
        ['accountant' => $accountant, 'director' => $director, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($accountant)
            ->post(route('payroll.review', $payroll))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $payroll->fresh()->status);

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);
    }

    public function test_system_does_not_auto_submit_or_approve_payroll(): void
    {
        Mail::fake();
        ['employee' => $employee, 'payroll' => $calculated] = $this->seedPayroll();
        $submittedEmployee = Employee::create([
            'name' => 'Nguyen Van B',
            'email' => 'employee-b@example.com',
            'position' => 'Developer',
            'department_id' => $employee->department_id,
            'status' => 'active',
            'employee_code' => 'EMP002',
        ]);
        $submitted = Payroll::create([
            'employee_id' => $submittedEmployee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);

        $workflow = app(PayrollPaymentWorkflowService::class);
        $this->assertSame(['reviewed' => 0, 'approved' => 0], $workflow->autoFinalizePeriod(8, 2026));

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $submitted->fresh()->status);
        $this->assertSame(0, \App\Models\ActivityLog::where('action', 'payroll_auto_hr_checked')->count());
        $this->assertSame(0, \App\Models\ActivityLog::where('action', 'payroll_auto_final_approved')->count());
    }

    public function test_late_payroll_release_uses_the_next_close_date_as_confirmation_deadline(): void
    {
        ['payroll' => $payroll] = $this->seedPayroll();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-16 10:00:00'));

        $this->assertSame(
            '2026-10-15 23:59',
            PayrollPaymentWorkflowService::confirmationDeadlineFor($payroll)->format('Y-m-d H:i')
        );
    }

    public function test_monthly_payroll_commands_lock_without_auto_verify_or_auto_approve(): void
    {
        Mail::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-15 00:15:00'));
        ['hr' => $hr, 'accountant' => $accountant, 'director' => $director, 'employee' => $employee, 'payroll' => $payroll] = $this->seedPayroll();
        $paidEmployee = Employee::create([
            'name' => 'Nguyen Van C',
            'email' => 'employee-c@example.com',
            'position' => 'Developer',
            'department_id' => $employee->department_id,
            'status' => 'active',
            'employee_code' => 'EMP003',
        ]);
        $alreadyPaid = Payroll::create([
            'employee_id' => $paidEmployee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::PAID,
            'paid_at' => now(),
        ]);

        $this->artisan('payroll:auto-calculate-monthly', ['--date' => '2026-09-15'])
            ->assertSuccessful();

        $locks = app(\App\Services\PayrollPeriodLockService::class);
        $this->assertTrue($locks->isLocked(8, 2026));
        $this->assertFalse($locks->isHrVerified(8, 2026));
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $alreadyPaid->fresh()->status);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'payroll_period_auto_hr_verified']);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'payroll_auto_calculated']);

        $this->travelTo(\Carbon\Carbon::parse('2026-09-15 23:00:00'));
        $this->artisan('payroll:auto-finalize-monthly', ['--date' => '2026-09-15'])
            ->assertSuccessful();

        // Auto-finalize không còn gửi duyệt / phê duyệt hộ người.
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $alreadyPaid->fresh()->status);

        // HR xác nhận → hệ thống tự tính; KT gửi duyệt; GĐ duyệt; hết hạn → auto confirm.
        $this->actingAs($hr)->post(route('payroll.period.verify'), ['month' => 8, 'year' => 2026])->assertRedirect();
        $this->assertTrue($locks->isHrVerified(8, 2026));
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $alreadyPaid->fresh()->status);

        $this->actingAs($accountant)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);

        $deadline = $payroll->fresh()->confirmation_deadline;
        $this->assertNotNull($deadline);

        $this->travelTo($deadline->copy()->subSecond());
        $this->assertSame(0, app(PayrollPaymentWorkflowService::class)->autoMarkReady());
        $this->travelTo($deadline->copy());
        $this->assertSame(1, app(PayrollPaymentWorkflowService::class)->autoMarkReady());
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);
    }

    public function test_hr_and_admin_cannot_final_approve_and_director_cannot_skip_hr_review(): void
    {
        ['hr' => $hr, 'admin' => $admin, 'director' => $director, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($hr)
            ->post(route('payroll.approve', $payroll))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('payroll.approve', $payroll))
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll))
            ->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
    }

    public function test_hr_cannot_submit_and_accountant_cannot_final_approve(): void
    {
        ['hr' => $hr, 'accountant' => $accountant, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($hr)
            ->post(route('payroll.review', $payroll))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('payroll.approve', $payroll))
            ->assertForbidden();
    }

    public function test_accountant_calculates_and_hr_cannot_generate(): void
    {
        ['hr' => $hr, 'accountant' => $accountant] = $this->seedPayroll();

        $this->actingAs($hr)
            ->post(route('payroll.generate'), ['month' => 7, 'year' => 2026])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('payroll.period.lock'), ['month' => 7, 'year' => 2026])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('payroll.generate'), ['month' => 7, 'year' => 2026])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('payrolls', [
            'month' => 7,
            'year' => 2026,
        ]);

        $this->actingAs($hr)
            ->post(route('payroll.period.lock'), ['month' => 7, 'year' => 2026])
            ->assertRedirect();

        $this->actingAs($hr)
            ->post(route('payroll.period.unlock'), ['month' => 7, 'year' => 2026])
            ->assertSessionHasErrors('unlock_reason');

        // Đã chốt nhưng chưa HR xác nhận → chưa tính được
        $this->actingAs($accountant)
            ->post(route('payroll.generate'), ['month' => 7, 'year' => 2026])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($hr)
            ->post(route('payroll.period.verify'), ['month' => 7, 'year' => 2026])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('payroll.generate'), ['month' => 7, 'year' => 2026])
            ->assertRedirect();

        $this->assertDatabaseHas('payrolls', [
            'month' => 7,
            'year' => 2026,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);
    }

    public function test_locked_period_blocks_attendance_and_leave_writes(): void
    {
        ['hr' => $hr, 'director' => $director, 'employee' => $employee] = $this->seedPayroll();

        $this->actingAs($hr)
            ->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])
            ->assertRedirect();

        $this->actingAs($hr)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'date' => '2026-08-10',
                'status' => 'present',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
        ]);

        $leave = \App\Models\LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-13',
            'days' => 2,
            'type' => 'annual',
            'reason' => 'Family',
            'status' => 'pending',
        ]);

        $this->actingAs($hr)
            ->post(route('leave_requests.approve', $leave))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending', $leave->fresh()->status);

        $this->actingAs($hr)
            ->post(route('payroll.period.unlock'), [
                'month' => 8,
                'year' => 2026,
                'unlock_reason' => 'Bổ sung chấm công thiếu',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payroll_period_unlock_requested',
        ]);
        $this->assertTrue(app(\App\Services\PayrollPeriodLockService::class)->isLocked(8, 2026));

        $this->actingAs($director)
            ->post(route('payroll.period.unlock.approve'), ['month' => 8, 'year' => 2026])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payroll_period_unlocked',
        ]);
        $this->assertFalse(app(\App\Services\PayrollPeriodLockService::class)->isLocked(8, 2026));
    }

    public function test_employee_confirm_then_accountant_can_pay(): void
    {
        ['director' => $director, 'accountant' => $accountant, 'employee' => $employee, 'payroll' => $payroll] = $this->seedPayroll();
        $employeeUser = User::factory()->create([
            'is_hr' => false,
            'is_admin' => false,
            'is_accountant' => false,
            'is_director' => false,
        ]);
        $employee->update(['user_id' => $employeeUser->id]);

        $this->actingAs($accountant)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll))->assertRedirect();
        $this->actingAs($employeeUser)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);
        $this->assertTrue(app(PayrollPaymentWorkflowService::class)->canPay($payroll->fresh()));
    }
}
