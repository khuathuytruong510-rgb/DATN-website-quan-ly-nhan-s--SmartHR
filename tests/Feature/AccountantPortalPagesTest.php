<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriodLock;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Carbon\Carbon;
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
        $this->get(route('accountant.payroll.generate', ['month' => 8, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Tính lương')
            ->assertSee('NV A')
            ->assertSee('Lương CB')
            ->assertSee('Thực nhận')
            ->assertSee('Ngày công')
            ->assertSee('Phụ cấp')
            ->assertSee('Lễ hưởng lương')
            ->assertSee('Lương lễ')
            ->assertSee('Bảng số liệu kỳ 08/2026')
            ->assertSee('01/08/2026')
            ->assertSee('31/08/2026');
        $this->get(route('accountant.payroll.show', $payroll))
            ->assertOk()
            ->assertDontSee('Gửi email xác nhận')
            ->assertSee('Chi tiết tính lương')
            ->assertSee('Lương cơ bản')
            ->assertSee('Tổng thu nhập')
            ->assertSee('Tổng khấu trừ')
            ->assertSee('Phụ cấp')
            ->assertSee('Ngày công')
            ->assertSee('Thực nhận');
        $this->get(route('accountant.payroll.feedback'))->assertOk();
        $this->get(route('accountant.leave_requests'))->assertOk()->assertDontSee('Tạo đơn nghỉ');
        $this->get(route('accountant.activity_logs'))->assertOk();
        $this->get(route('accountant.profile'))->assertRedirect();
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

    public function test_generate_defaults_to_locked_period_that_already_has_slips(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02'));
        try {
            $user = $this->accountant();
            $hr = User::factory()->create([
                'is_hr' => true,
                'is_admin' => false,
                'is_accountant' => false,
                'is_director' => false,
            ]);
            $department = Department::create(['name' => 'KT', 'code' => 'KT2', 'manager' => 'M']);
            $employee = Employee::create([
                'name' => 'NV Demo 08',
                'email' => 'nv-demo08@example.com',
                'position' => 'Dev',
                'department_id' => $department->id,
                'status' => 'active',
                'employee_code' => 'EMP0801',
            ]);
            Payroll::create([
                'employee_id' => $employee->id,
                'month' => 8,
                'year' => 2026,
                'base_salary' => 10000000,
                'total_salary' => 10000000,
                'status' => PayrollPaymentWorkflowService::CALCULATED,
            ]);
            PayrollPeriodLock::create([
                'month' => 9,
                'year' => 2026,
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $hr->id,
            ]);
            PayrollPeriodLock::create([
                'month' => 8,
                'year' => 2026,
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $hr->id,
            ]);

            $this->actingAs($user)
                ->get(route('accountant.payroll.generate'))
                ->assertOk()
                ->assertSee('Bảng số liệu kỳ 08/2026')
                ->assertSee('NV Demo 08')
                ->assertSee('acct-row-write')
                ->assertSee('Dòng nền xanh sẽ được ghi');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_accountant_activity_log_uses_vietnamese_labels(): void
    {
        $user = $this->accountant();
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payroll_calculated',
            'meta' => 'period:08/2026;calculated:11;skipped:0',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payroll_period_unlocked',
            'meta' => 'period:08/2026;reason:Reset demo: trả toàn bộ kỳ lương về bước HR kiểm tra dữ liệu công.',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payroll_paid',
            'meta' => 'payroll:16;method:cash;by:'.$user->id.';ref:cash',
        ]);

        $this->actingAs($user)
            ->get(route('accountant.activity_logs'))
            ->assertOk()
            ->assertDontSee('payroll_calculated')
            ->assertDontSee('payroll_period_unlocked')
            ->assertSee('Kế toán tính lương')
            ->assertSee('Kỳ lương tháng 08/2026')
            ->assertSee('Đã tính 11 phiếu')
            ->assertSee('Không bỏ qua phiếu nào')
            ->assertSee('HR mở khóa kỳ lương')
            ->assertSee('Lý do: Reset demo')
            ->assertSee('Kế toán thanh toán lương')
            ->assertSee('Hình thức: tiền mặt');
    }
}
