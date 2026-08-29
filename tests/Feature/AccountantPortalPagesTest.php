<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantPortalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function accountant(): User
    {
        return User::factory()->create([
            'is_accountant' => true,
            'is_hr' => false,
            'is_admin' => false,
            'is_director' => false,
        ]);
    }

    public function test_accountant_login_and_dashboard_land_on_accountant_portal(): void
    {
        $user = $this->accountant();
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('accountant.dashboard'));

        $this->actingAs($user)->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Kế toán')
            ->assertSee('Chờ thanh toán')
            ->assertDontSee('Quản lý phụ cấp');
    }

    public function test_accountant_core_pages_render(): void
    {
        $user = $this->accountant();
        $department = Department::create(['name' => 'KT', 'code' => 'KT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'NV A',
            'email' => 'nva@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPKT',
        ]);
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'approved',
        ]);

        $this->actingAs($user);
        $this->get(route('accountant.payroll.index'))->assertOk()->assertSee('NV A');
        $this->get(route('accountant.payroll.generate'))->assertOk()->assertSee('Tính lương');
        $this->get(route('accountant.payroll.show', $payroll))->assertOk()->assertDontSee('Gửi email xác nhận');
        $this->get(route('accountant.payroll.feedback'))->assertOk();
        $this->get(route('accountant.leave_requests'))->assertOk()->assertDontSee('Tạo đơn nghỉ');
        $this->get(route('accountant.activity_logs'))->assertOk();
        $this->get(route('accountant.profile'))->assertOk();
        $this->get(route('accountant.password.change'))->assertOk();
        $this->get(route('payroll.index'))->assertOk();
    }

    public function test_accountant_cannot_email_or_pay_before_workflow_allows(): void
    {
        $user = $this->accountant();
        $department = Department::create(['name' => 'KT', 'code' => 'KT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'NV A',
            'email' => 'nva@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPKT2',
        ]);
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('accountant.payroll.send_email', $payroll))
            ->assertRedirect(route('accountant.payroll.show', $payroll));
        $this->assertNull($payroll->fresh()->sent_at);

        $this->actingAs($user)->post(route('accountant.leave_requests.approve', $leave))->assertForbidden();
    }

    public function test_employee_cannot_open_accountant_pages(): void
    {
        $user = User::factory()->create([
            'is_accountant' => false,
            'is_hr' => false,
            'is_admin' => false,
            'is_director' => false,
        ]);
        $this->actingAs($user)->get(route('accountant.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('accountant.payroll.generate'))->assertForbidden();
    }
}
