<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_hr_reviews_then_director_final_approves(): void
    {
        ['hr' => $hr, 'director' => $director, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($hr)
            ->post(route('payroll.review', $payroll))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $payroll->fresh()->status);

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);
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

    public function test_accountant_cannot_review_or_final_approve(): void
    {
        ['accountant' => $accountant, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($accountant)
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
        ['hr' => $hr, 'employee' => $employee] = $this->seedPayroll();

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
            'action' => 'payroll_period_unlocked',
        ]);
    }

    public function test_payroll_issue_returns_to_calculated_then_hr_check_then_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'accountant' => $accountant, 'payroll' => $payroll] = $this->seedPayroll();

        $this->actingAs($hr)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll))->assertRedirect();

        $workflow = app(PayrollPaymentWorkflowService::class);
        $workflow->reportIssue($payroll->fresh(), 'Sai phụ cấp');

        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $payroll->fresh()->status);

        $workflow->remediateIssue($payroll->fresh(), [
            'base_salary' => 10000000,
            'working_salary' => 10000000,
            'overtime_salary' => 0,
            'allowance' => 0,
            'bonus' => 0,
            'insurance' => 0,
            'tax' => 0,
            'deduction' => 0,
        ], $accountant);

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertNull($payroll->fresh()->issue_report);

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll->fresh()))
            ->assertForbidden();

        $this->actingAs($hr)
            ->post(route('payroll.review', $payroll->fresh()))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $payroll->fresh()->status);

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll->fresh()))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);
    }
}
