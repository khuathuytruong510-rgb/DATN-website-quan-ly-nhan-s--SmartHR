<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPortalGuardTest extends TestCase
{
    use RefreshDatabase;

    private function seedPeople(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nguyen Van HR',
            'email' => 'nvhr@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPHR',
        ]);

        return compact('hr', 'director', 'accountant', 'department', 'employee');
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

    public function test_hr_lands_on_hr_dashboard_not_accountant_or_employee_portal(): void
    {
        ['hr' => $hr] = $this->seedPeople();

        $this->actingAs($hr)->get(route('dashboard'))->assertOk();
        $this->actingAs($hr)->get(route('payroll.index'))->assertOk()->assertSee('Bảng lương nhân viên');
        $this->actingAs($hr)->get(route('accountant.dashboard'))->assertForbidden();
        $this->actingAs($hr)->get(route('me.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_hr_cannot_generate_approve_or_pay(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $calculated = $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED);
        $payable = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        $this->actingAs($hr)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.approve', $calculated))->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.payment.confirm', $payable), [
            'payment_method' => 'cash',
            'status' => PayrollPaymentWorkflowService::PAID,
        ])->assertForbidden();
        $this->actingAs($hr)->get(route('payroll.payment.show', $payable))->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payable->fresh()->status);
    }

    public function test_hr_review_ignores_client_status_and_totals(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $payroll = $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED, ['total_salary' => 8800000]);

        $this->actingAs($hr)->post(route('payroll.review', $payroll), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => 999,
        ])->assertRedirect();

        $fresh = $payroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $fresh->status);
        $this->assertEquals(8800000, (float) $fresh->total_salary);
        $this->assertSame($employee->id, (int) $fresh->employee_id);
        $this->assertNull($fresh->paid_at);
    }

    public function test_hr_cannot_delete_payroll_after_hr_checked(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $checked = $this->payroll($employee, PayrollPaymentWorkflowService::HR_CHECKED);
        $approved = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED,
        ]);

        $this->actingAs($hr)->delete(route('payroll.destroy', $checked))->assertRedirect();
        $this->actingAs($hr)->delete(route('payroll.destroy', $approved))->assertRedirect();

        $this->assertDatabaseHas('payrolls', ['id' => $checked->id, 'status' => PayrollPaymentWorkflowService::HR_CHECKED]);
        $this->assertDatabaseHas('payrolls', ['id' => $approved->id, 'status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED]);
    }

    public function test_hr_cannot_sign_contract_or_mutate_payments(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $contract = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ NV',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
        ]);
        $payroll = $this->payroll($employee, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED);
        $payment = SalaryPayment::create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'code' => 'PAY-HR',
            'month' => 8,
            'year' => 2026,
            'total' => 10000000,
            'net' => 10000000,
            'status' => 'pending',
        ]);

        $this->actingAs($hr)->post(route('contracts.sign', $contract))->assertForbidden();
        $this->actingAs($hr)->put(route('salary_payments.update', $payment), [
            'payment_method' => 'cash',
            'status' => 'paid',
        ])->assertForbidden();
        $this->actingAs($hr)->post(route('salary_payments.action', $payment))->assertForbidden();

        $this->assertSame(Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, $contract->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_locked_period_blocks_hr_attendance_write(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();

        $row = Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '08:00:00',
        ]);

        $this->actingAs($hr)->put(route('attendance.update', $row), [
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '07:00',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($hr)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-11',
            'status' => 'present',
            'check_in' => '08:00',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('08:00:00', $row->fresh()->getRawOriginal('check_in'));
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-08-11',
        ]);
    }

    public function test_hr_cannot_email_before_director_approval(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $payroll = $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED);

        $this->actingAs($hr)->post(route('payroll.email.send', $payroll))->assertRedirect();
        $this->assertNull($payroll->fresh()->sent_at);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
    }

    public function test_hr_pages_render(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);

        $this->actingAs($hr);
        $this->get(route('employees.index'))->assertOk();
        $this->get(route('leave_requests.index'))->assertOk()->assertSee('Quản Lý Đơn Nghỉ Phép');
        $this->get(route('attendance.index'))->assertOk();
        $this->get(route('contracts.index'))->assertOk();
        $this->get(route('payroll.issues.index'))->assertOk();
        $this->get(route('hr-dashboard.index'))->assertOk();
    }

    public function test_hr_unlock_requires_reason_audit_and_blocks_after_hr_check(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'director' => $gd, 'employee' => $employee] = $this->seedPeople();

        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();

        $this->actingAs($kt)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'Kế toán muốn mở khóa kỳ này',
        ])->assertForbidden();

        $this->actingAs($gd)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'Giám đốc muốn mở khóa kỳ này',
        ])->assertForbidden();

        $this->actingAs($hr)
            ->post(route('payroll.period.unlock'), ['month' => 8, 'year' => 2026])
            ->assertSessionHasErrors('unlock_reason');

        $this->actingAs($hr)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'ngắn',
        ])->assertSessionHasErrors('unlock_reason');

        $this->actingAs($hr)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => '   ',
        ])->assertSessionHasErrors('unlock_reason');

        $this->actingAs($hr)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'Bổ sung chấm công thiếu',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payroll_period_unlocked',
            'user_id' => $hr->id,
        ]);
        $this->assertDatabaseHas('payroll_period_locks', [
            'month' => 8,
            'year' => 2026,
            'is_locked' => false,
        ]);

        $this->actingAs($hr)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-20',
            'status' => 'present',
            'check_in' => '08:00',
        ])->assertRedirect();

        $this->assertTrue(
            Attendance::query()->where('employee_id', $employee->id)->whereDate('date', '2026-08-20')->exists()
        );

        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();
        $this->payroll($employee, PayrollPaymentWorkflowService::HR_CHECKED);

        $this->actingAs($hr)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'Muốn sửa chấm công sau khi HR đã kiểm tra lương',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_period_locks', [
            'month' => 8,
            'year' => 2026,
            'is_locked' => true,
        ]);
    }
}
