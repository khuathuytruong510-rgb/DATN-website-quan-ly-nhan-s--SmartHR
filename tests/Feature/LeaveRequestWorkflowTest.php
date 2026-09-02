<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $user = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nam',
            'email' => $user->email,
            'user_id' => $user->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'IT01',
            'leave_balance' => 12,
            'gender' => 'male',
        ]);
        $contract = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chính thức',
            'contract_type' => 'fixed_term',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_ACTIVE,
            'allowed_unpaid_leave_days_per_month' => 1,
            'allowed_maternity_leave_days' => 180,
        ]);

        return compact('hr', 'user', 'employee', 'contract');
    }

    public function test_male_form_hides_maternity_leave_type(): void
    {
        ['user' => $user] = $this->people();

        $this->actingAs($user)
            ->get(route('me.leave_requests.create'))
            ->assertOk()
            ->assertDontSee('Nghỉ thai sản')
            ->assertSee('Nghỉ phép năm');
    }

    public function test_create_form_asks_type_before_dates_and_shows_legal_quota(): void
    {
        ['user' => $user] = $this->people();

        $html = $this->actingAs($user)
            ->get(route('me.leave_requests.create'))
            ->assertOk()
            ->getContent();

        $typePos = strpos($html, 'Loại nghỉ phép');
        $datePos = strpos($html, 'Ngày bắt đầu');
        $this->assertNotFalse($typePos);
        $this->assertNotFalse($datePos);
        $this->assertLessThan($datePos, $typePos);
        $this->assertStringContainsString('leave-quota-card', $html);
        $this->assertStringContainsString('113', $html);
    }

    public function test_annual_entitlement_uses_labor_law_seniority_minimum(): void
    {
        ['employee' => $employee] = $this->people();
        $employee->update([
            'start_date' => now()->subYears(6)->toDateString(),
            'leave_balance' => 12,
        ]);

        $quota = app(\App\Services\LeaveEligibilityService::class)->quotaSummary($employee);

        $this->assertSame(13, $quota['annual_legal']);
        $this->assertSame(13, $quota['annual_max']);
    }

    public function test_annual_entitlement_is_proportional_under_one_year(): void
    {
        ['employee' => $employee] = $this->people();
        $employee->update([
            'start_date' => now()->subMonths(4)->toDateString(),
            'leave_balance' => 0,
        ]);

        $quota = app(\App\Services\LeaveEligibilityService::class)->quotaSummary($employee);

        $this->assertSame(4, $quota['annual_legal']);
        $this->assertSame(4, $quota['annual_max']);
    }

    public function test_female_form_shows_maternity_as_default(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->update(['gender' => 'female']);

        $html = $this->actingAs($user)->get(route('me.leave_requests.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Nghỉ thai sản', $html);
        $this->assertMatchesRegularExpression('/<option value="maternity"\s+selected>/', $html);
    }

    public function test_male_cannot_submit_maternity_leave(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Không hợp lệ',
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_female_can_submit_maternity_leave(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->update(['gender' => 'female']);

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Thai sản',
        ])->assertRedirect(route('me.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'type' => 'maternity',
            'status' => 'pending',
        ]);
    }

    public function test_hr_cannot_create_maternity_for_male(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('leave_requests.store'), [
            'employee_id' => $employee->id,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Không hợp lệ',
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_submit_without_contract_is_blocked(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->contracts()->delete();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'type' => 'annual',
            'reason' => 'Nghỉ',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $employee->id]);
        $this->assertDatabaseMissing('notifications', ['target' => 'hr', 'title' => 'Đơn nghỉ phép cần duyệt']);
    }

    public function test_valid_submit_notifies_hr_and_stays_pending(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'type' => 'annual',
            'reason' => 'Nghỉ phép năm',
        ])->assertRedirect(route('me.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);
    }

    public function test_unpaid_over_contract_limit_is_rejected(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(8)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => 'unpaid',
            'reason' => 'Việc riêng dài',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_hr_approve_marks_attendance_for_payroll(): void
    {
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'type' => 'annual',
            'reason' => 'Nghỉ',
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $employee->id)->first();
        $this->actingAs($hr)->post(route('leave_requests.approve', $leave))->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'approved']);
        $this->assertTrue(
            \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '2026-09-07')
                ->where('status', 'leave')
                ->exists()
        );
        $this->assertTrue(Notification::where('target', 'employee')->where('data->leave_request_id', $leave->id)->exists());
        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đơn nghỉ phép đã được duyệt',
        ]);
    }
}
