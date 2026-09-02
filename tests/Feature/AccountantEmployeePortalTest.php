<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantEmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $kt = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'KTTC', 'code' => 'KTTC', 'manager' => 'M']);
        $ktEmployee = Employee::create([
            'name' => 'Lê Thị Mai',
            'email' => $kt->email,
            'user_id' => $kt->id,
            'position' => 'Kế toán',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'KT01',
            'leave_balance' => 12,
            'gender' => 'female',
        ]);
        Contract::create([
            'employee_id' => $ktEmployee->id,
            'title' => 'HĐ',
            'contract_type' => 'fixed_term',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_ACTIVE,
            'allowed_unpaid_leave_days_per_month' => 1,
            'allowed_maternity_leave_days' => 180,
        ]);

        return compact('hr', 'kt', 'ktEmployee');
    }

    public function test_accountant_without_employee_stays_on_accountant_portal(): void
    {
        $kt = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);

        $this->actingAs($kt)->get(route('me.leave_requests'))->assertRedirect(route('accountant.dashboard'));
        $this->actingAs($kt)->get(route('me.dashboard'))->assertRedirect(route('accountant.dashboard'));
        $this->actingAs($kt)->get(route('accountant.dashboard'))->assertOk();
    }

    public function test_accountant_can_use_employee_self_service_pages(): void
    {
        ['kt' => $kt] = $this->people();

        $this->actingAs($kt);
        $this->get(route('me.profile'))->assertOk();
        $this->get(route('me.attendance'))->assertOk();
        $this->get(route('me.leave_requests'))->assertOk()->assertSee('Cá nhân');
        $this->get(route('me.leave_requests.create'))->assertOk()->assertSee('chuyển HR duyệt');
        $this->get(route('me.overtime_requests'))->assertOk();
        $this->get(route('me.payrolls'))->assertOk();
        $this->get(route('me.contracts'))->assertOk();
        $this->get(route('me.evaluations'))->assertOk();
        $this->get(route('me.benefits'))->assertOk();
        $this->get(route('me.schedule'))->assertOk();
        $this->get(route('me.support_requests'))->assertOk();
        $this->get(route('me.notifications'))->assertOk();
    }

    public function test_accountant_leave_goes_to_hr(): void
    {
        ['hr' => $hr, 'kt' => $kt, 'ktEmployee' => $ktEmployee] = $this->people();

        $this->actingAs($kt)->post(route('me.leave_requests.store'), [
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'type' => 'annual',
            'reason' => 'Nghỉ của kế toán',
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $ktEmployee->id)->first();
        $this->assertNotNull($leave);
        $this->assertSame('pending', $leave->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'target' => 'director',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);

        $this->actingAs($kt)->post(route('leave_requests.approve', $leave))->assertForbidden();
        $this->assertSame('pending', $leave->fresh()->status);

        $this->actingAs($hr)->post(route('leave_requests.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertTrue(Notification::where('target', 'employee')->where('data->leave_request_id', $leave->id)->exists());
    }

    public function test_accountant_overtime_is_approved_by_hr(): void
    {
        ['hr' => $hr, 'kt' => $kt, 'ktEmployee' => $ktEmployee] = $this->people();

        $this->actingAs($kt)->post(route('me.overtime_requests.store'), [
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'OT KT',
        ])->assertRedirect(route('me.overtime_requests'));

        $ot = OvertimeRequest::where('employee_id', $ktEmployee->id)->first();
        $this->assertSame('pending', $ot->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Đăng ký tăng ca cần duyệt',
        ]);

        $this->actingAs($kt)->post(route('overtime_requests.approve', $ot))->assertForbidden();
        $this->actingAs($hr)->post(route('overtime_requests.approve', $ot))->assertRedirect();
        $this->assertSame('approved', $ot->fresh()->status);
    }
}
