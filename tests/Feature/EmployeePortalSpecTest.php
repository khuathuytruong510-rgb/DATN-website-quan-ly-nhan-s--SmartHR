<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalSpecTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): array
    {
        $user = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Alice',
            'email' => $user->email,
            'user_id' => $user->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPA',
            'education' => 'Đại học',
        ]);

        return compact('user', 'employee');
    }

    public function test_profile_update_ignores_hr_managed_fields(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->employeeUser();

        $this->actingAs($user)->put(route('me.profile.update'), [
            'name' => 'Alice Updated',
            'phone' => '0900000000',
            'education' => 'Hacked',
            'position' => 'CEO',
            'employee_code' => 'HACK',
            'department_id' => 999,
            'position_id' => 999,
            'user_id' => 999,
            'status' => 'inactive',
        ])->assertRedirect(route('me.profile'));

        $fresh = $employee->fresh();
        $this->assertSame('Alice Updated', $fresh->name);
        $this->assertSame('0900000000', $fresh->phone);
        $this->assertSame('Đại học', $fresh->education);
        $this->assertSame('Developer', $fresh->position);
        $this->assertSame('EMPA', $fresh->employee_code);
        $this->assertSame($employee->department_id, $fresh->department_id);
        $this->assertSame($employee->user_id, $fresh->user_id);
        $this->assertSame('active', $fresh->status);
    }

    public function test_employee_can_cancel_pending_leave_but_not_delete(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->employeeUser();
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('me.leave_requests.cancel', $leave))->assertRedirect();
        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);

        $bobUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $bob = Employee::create([
            'name' => 'Bob',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Dev',
            'department_id' => $employee->department_id,
            'status' => 'active',
            'employee_code' => 'EMPB2',
        ]);
        $bobsLeave = LeaveRequest::create([
            'employee_id' => $bob->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->actingAs($user)->post(route('me.leave_requests.cancel', $bobsLeave))->assertForbidden();
        $this->assertSame('pending', $bobsLeave->fresh()->status);
    }

    public function test_attendance_adjustment_is_idor_safe_and_does_not_edit_record(): void
    {
        ['user' => $alice, 'employee' => $aliceEmp] = $this->employeeUser();
        $bobUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $bob = Employee::create([
            'name' => 'Bob',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Dev',
            'department_id' => $aliceEmp->department_id,
            'status' => 'active',
            'employee_code' => 'EMPB',
        ]);

        $mine = Attendance::create(['employee_id' => $aliceEmp->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:32:00']);
        $theirs = Attendance::create(['employee_id' => $bob->id, 'date' => '2026-08-28', 'status' => 'present', 'check_in' => '08:00:00']);

        $this->actingAs($alice)->post(route('me.attendance.adjust', $theirs), [
            'requested_check_in' => '08:00',
            'reason' => 'Quên chấm',
        ])->assertForbidden();

        $this->actingAs($alice)->post(route('me.attendance.adjust', $mine), [
            'requested_check_in' => '08:00',
            'reason' => 'Quên chấm công',
        ])->assertRedirect();

        $this->assertSame('08:32:00', $mine->fresh()->getRawOriginal('check_in'));
        $this->assertDatabaseHas('attendance_adjustment_requests', [
            'attendance_id' => $mine->id,
            'status' => AttendanceAdjustmentRequest::PENDING,
        ]);
    }

    public function test_notification_mark_read_is_per_user(): void
    {
        ['user' => $user] = $this->employeeUser();
        $sender = User::factory()->create(['is_hr' => true]);
        $notification = Notification::create([
            'sender_id' => $sender->id,
            'target' => 'employee',
            'title' => 'Hello',
            'message' => 'Body',
        ]);

        $this->actingAs($user)->post(route('me.notifications.read', $notification))->assertRedirect();
        $this->assertDatabaseHas('notification_reads', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_salary_advances_redirect_to_payroll(): void
    {
        ['user' => $user] = $this->employeeUser();
        $this->actingAs($user)->get(route('me.salary_advances'))->assertRedirect(route('me.payrolls'));
    }
}
