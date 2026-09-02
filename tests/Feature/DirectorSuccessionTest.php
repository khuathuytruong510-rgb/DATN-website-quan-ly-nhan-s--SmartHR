<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorSuccessionTest extends TestCase
{
    use RefreshDatabase;

    private function seedActors(): array
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'is_admin' => true,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => false,
        ]);
        $outgoing = User::factory()->create([
            'name' => 'Nguyen Van A',
            'email' => 'nva@example.com',
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => true,
        ]);
        $incoming = User::factory()->create([
            'name' => 'Tran Van B',
            'email' => 'tvb@example.com',
            'is_admin' => false,
            'is_hr' => false,
            'is_accountant' => false,
            'is_director' => false,
        ]);
        $hr = User::factory()->create([
            'is_hr' => true,
            'is_admin' => false,
            'is_accountant' => false,
            'is_director' => false,
        ]);

        $board = Department::create([
            'name' => 'Ban Giám đốc',
            'code' => 'BGD',
            'manager' => 'Nguyen Van A',
        ]);
        $it = Department::create([
            'name' => 'Cong nghe',
            'code' => 'CNTT',
            'manager' => 'Lead',
        ]);

        $outgoingEmployee = Employee::create([
            'user_id' => $outgoing->id,
            'name' => 'Nguyen Van A',
            'email' => 'nva@example.com',
            'position' => 'Giám đốc',
            'department_id' => $board->id,
            'status' => 'active',
            'employee_code' => 'BGD0001',
            'start_date' => '2025-01-01',
        ]);
        $incomingEmployee = Employee::create([
            'user_id' => $incoming->id,
            'name' => 'Tran Van B',
            'email' => 'tvb@example.com',
            'position' => 'Nhân viên',
            'department_id' => $it->id,
            'status' => 'active',
            'employee_code' => 'CNTT0001',
        ]);
        $staff = Employee::create([
            'name' => 'Nhan vien C',
            'email' => 'nvc@example.com',
            'position' => 'Developer',
            'department_id' => $it->id,
            'status' => 'active',
            'employee_code' => 'CNTT0002',
        ]);

        return compact('admin', 'outgoing', 'incoming', 'hr', 'outgoingEmployee', 'incomingEmployee', 'staff');
    }

    public function test_admin_transfers_director_role_without_renaming_old_account(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming] = $this->seedActors();

        $this->actingAs($admin)
            ->post(route('director_succession.store'), [
                'incoming_user_id' => $incoming->id,
                'effective_on' => '2026-09-01',
                'outgoing_role' => 'employee',
                'outgoing_status' => 'active',
                'outgoing_position' => 'Nhân viên',
                'decision_ref' => 'QD-12/2026',
            ])
            ->assertRedirect(route('director_succession.index'));

        $this->assertFalse($outgoing->fresh()->is_director);
        $this->assertTrue($incoming->fresh()->is_director);
        $this->assertSame('Nguyen Van A', $outgoing->fresh()->name);
        $this->assertSame('nva@example.com', $outgoing->fresh()->email);
        $this->assertSame('Tran Van B', $incoming->fresh()->name);

        $this->assertSame('Nhân viên', $outgoing->employee->fresh()->position);
        $this->assertSame('Giám đốc', $incoming->employee->fresh()->position);

        $oldTenure = EmployeePositionHistory::query()
            ->where('user_id', $outgoing->id)
            ->where('is_director_role', true)
            ->first();
        $this->assertNotNull($oldTenure);
        $this->assertSame(EmployeePositionHistory::STATUS_ENDED, $oldTenure->status);
        $this->assertSame('2026-08-31', optional($oldTenure->ended_at)->toDateString());

        $newTenure = EmployeePositionHistory::query()
            ->where('user_id', $incoming->id)
            ->where('is_director_role', true)
            ->whereNull('ended_at')
            ->first();
        $this->assertNotNull($newTenure);
        $this->assertSame('2026-09-01', optional($newTenure->started_at)->toDateString());

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'director_succession',
        ]);
    }

    public function test_account_and_permission_screens_cannot_hand_out_director_role(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming] = $this->seedActors();

        $this->actingAs($admin)
            ->put(route('accounts.update', $incoming), [
                'name' => $incoming->name,
                'email' => $incoming->email,
                'role' => 'director',
                'department_id' => $incoming->employee->department_id,
            ])
            ->assertRedirect(route('director_succession.index'));

        $this->assertFalse($incoming->fresh()->is_director);
        $this->assertTrue($outgoing->fresh()->is_director);

        $this->actingAs($admin)
            ->put(route('permissions.update', $incoming), ['is_director' => 1])
            ->assertRedirect(route('director_succession.index'));

        $this->assertFalse($incoming->fresh()->is_director);
    }

    public function test_old_director_account_cannot_be_deleted(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming] = $this->seedActors();

        $this->actingAs($admin)->post(route('director_succession.store'), [
            'incoming_user_id' => $incoming->id,
            'effective_on' => '2026-09-01',
            'outgoing_role' => 'employee',
            'outgoing_status' => 'active',
            'outgoing_position' => 'Nhân viên',
        ]);

        $this->actingAs($admin)
            ->delete(route('accounts.destroy', $outgoing))
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('users', ['id' => $outgoing->id]);
    }

    public function test_approved_payroll_keeps_old_director_after_succession(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming, 'hr' => $hr, 'staff' => $staff] = $this->seedActors();

        $payroll = Payroll::create([
            'employee_id' => $staff->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        $this->actingAs($hr)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($outgoing)->post(route('payroll.approve', $payroll))->assertRedirect();

        $approved = $payroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $approved->status);
        $this->assertSame($outgoing->id, $approved->director_approved_by);
        $this->assertSame('Nguyen Van A', $approved->director_approved_name);

        $this->actingAs($admin)->post(route('director_succession.store'), [
            'incoming_user_id' => $incoming->id,
            'effective_on' => '2026-09-01',
            'outgoing_role' => 'employee',
            'outgoing_status' => 'active',
            'outgoing_position' => 'Nhân viên',
        ])->assertRedirect();

        $after = $payroll->fresh();
        $this->assertSame($outgoing->id, $after->director_approved_by);
        $this->assertSame('Nguyen Van A', $after->director_approved_name);
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $after->status);

        $this->assertTrue(
            ActivityLog::query()
                ->where('user_id', $outgoing->id)
                ->where('action', 'payroll_final_approved')
                ->where('meta', 'payroll:'.$payroll->id)
                ->exists()
        );

        $this->actingAs($outgoing->fresh())
            ->post(route('payroll.approve', $payroll))
            ->assertForbidden();
    }

    public function test_new_director_approves_later_payroll_old_director_cannot(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming, 'hr' => $hr, 'staff' => $staff] = $this->seedActors();

        $this->actingAs($admin)->post(route('director_succession.store'), [
            'incoming_user_id' => $incoming->id,
            'effective_on' => '2026-09-01',
            'outgoing_role' => 'employee',
            'outgoing_status' => 'active',
            'outgoing_position' => 'Nhân viên',
        ]);

        $september = Payroll::create([
            'employee_id' => $staff->id,
            'month' => 9,
            'year' => 2026,
            'base_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        $this->actingAs($hr)->post(route('payroll.review', $september))->assertRedirect();

        $this->actingAs($outgoing->fresh())
            ->post(route('payroll.approve', $september))
            ->assertForbidden();

        $this->actingAs($incoming->fresh())
            ->post(route('payroll.approve', $september))
            ->assertRedirect();

        $this->assertSame('Tran Van B', $september->fresh()->director_approved_name);
        $this->assertSame($incoming->id, $september->fresh()->director_approved_by);
    }

    public function test_admin_succession_page_shows_current_director_and_scope_rules(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing] = $this->seedActors();

        $this->actingAs($admin)
            ->get(route('director_succession.index'))
            ->assertOk()
            ->assertSee('Nguyen Van A')
            ->assertSee('nva@example.com')
            ->assertSee('Ngoài SmartHR')
            ->assertSee('Không xóa dữ liệu Giám đốc cũ')
            ->assertSee('Cấp quyền Director cho người mới')
            ->assertSee('Vai trò hệ thống sau khi chuyển giao')
            ->assertSee('Còn làm việc')
            ->assertSee('Nghỉ việc')
            ->assertSee('Tạm nghỉ')
            ->assertSee('Lịch sử nhiệm kỳ Giám đốc')
            ->assertSee('Chưa có trong danh sách')
            ->assertSee('Tạo hồ sơ nhân sự mới')
            ->assertSee('Trường hợp B');
    }

    public function test_external_appointee_needs_hr_profile_then_admin_account(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing] = $this->seedActors();
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_director' => false, 'is_accountant' => false]);
        $board = Department::query()->where('code', 'BGD')->first();

        $this->actingAs($admin)
            ->get(route('director_succession.prepare_new'))
            ->assertOk()
            ->assertSee('HR')
            ->assertSee('tạo hồ sơ')
            ->assertSee(route('employees.create', ['for_director' => 1], false));

        $this->actingAs($admin)
            ->get(route('employees.create', ['for_director' => 1]))
            ->assertForbidden();

        $this->actingAs($hr)
            ->post(route('employees.store'), [
                'name' => 'Nguyen Van B',
                'email' => 'nvb@example.com',
                'department_id' => $board->id,
                'position' => 'Giám đốc',
                'status' => 'active',
                'for_director' => 1,
            ])
            ->assertRedirect(route('employees.index'));

        $profile = Employee::query()->where('email', 'nvb@example.com')->first();
        $this->assertNotNull($profile);
        $this->assertNull($profile->user_id);

        $this->actingAs($admin)
            ->get(route('director_succession.index'))
            ->assertOk()
            ->assertSee('Nguyen Van B')
            ->assertSee('Hồ sơ đã có, chưa có tài khoản');

        $this->actingAs($admin)
            ->post(route('accounts.store'), [
                'name' => 'Nguyen Van B',
                'email' => 'nvb@example.com',
                'password' => 'secret1',
                'password_confirmation' => 'secret1',
                'role' => 'employee',
                'employee_id' => $profile->id,
            ])
            ->assertRedirect(route('director_succession.index'));

        $linked = $profile->fresh();
        $this->assertNotNull($linked->user_id);
        $this->assertSame('nvb@example.com', User::query()->find($linked->user_id)?->email);
        $this->assertFalse(User::query()->where('email', 'nvb@example.com')->first()->is_director);
        $this->assertTrue($outgoing->fresh()->is_director);
    }

    public function test_effective_date_cannot_precede_current_tenure(): void
    {
        ['admin' => $admin, 'incoming' => $incoming] = $this->seedActors();

        $this->actingAs($admin)
            ->from(route('director_succession.index'))
            ->post(route('director_succession.store'), [
                'incoming_user_id' => $incoming->id,
                'effective_on' => '2024-12-01',
                'outgoing_role' => 'employee',
                'outgoing_status' => 'active',
                'outgoing_position' => 'Nhân viên',
            ])
            ->assertRedirect(route('director_succession.index'))
            ->assertSessionHasErrors('effective_on');

        $this->assertTrue($this->seededOutgoingStillDirector());
    }

    public function test_new_position_required_only_when_outgoing_still_works(): void
    {
        ['admin' => $admin, 'outgoing' => $outgoing, 'incoming' => $incoming] = $this->seedActors();

        $this->actingAs($admin)
            ->from(route('director_succession.index'))
            ->post(route('director_succession.store'), [
                'incoming_user_id' => $incoming->id,
                'effective_on' => '2026-09-01',
                'outgoing_role' => 'employee',
                'outgoing_status' => 'active',
            ])
            ->assertRedirect(route('director_succession.index'))
            ->assertSessionHasErrors('outgoing_position');

        $this->actingAs($admin)->post(route('director_succession.store'), [
            'incoming_user_id' => $incoming->id,
            'effective_on' => '2026-09-01',
            'outgoing_role' => 'employee',
            'outgoing_status' => 'on_leave',
        ])->assertRedirect(route('director_succession.index'));

        $this->assertFalse($outgoing->fresh()->is_director);
        $this->assertTrue($incoming->fresh()->is_director);
        $this->assertSame('on_leave', $outgoing->employee->fresh()->status);
        $this->assertSame(1, User::query()->where('is_director', true)->count());
    }

    private function seededOutgoingStillDirector(): bool
    {
        return (bool) User::query()->where('email', 'nva@example.com')->where('is_director', true)->exists();
    }
}
