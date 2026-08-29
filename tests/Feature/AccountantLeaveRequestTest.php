<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_view_but_cannot_approve_leave_requests(): void
    {
        $accountant = User::factory()->create(['is_accountant' => true]);
        $department = \App\Models\Department::create([
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
        ]);
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-12',
            'days' => 3,
            'type' => 'annual',
            'reason' => 'Family trip',
            'status' => 'pending',
        ]);

        $this->actingAs($accountant)
            ->post(route('leave_requests.approve', $leaveRequest))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('employees.store'), [
                'name' => 'Hacker',
                'email' => 'hacker@example.com',
                'department_id' => $department->id,
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'date' => '2025-01-10',
                'status' => 'present',
            ])
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('contracts.store'), [
                'employee_id' => $employee->id,
                'contract_type' => 'fixed_term',
                'start_date' => '2026-01-01',
            ])
            ->assertForbidden();

        $response = $this->get(route('accountant.leave_requests'));
        $response->assertOk();
        $response->assertDontSee('Tạo đơn nghỉ');
        $response->assertDontSee('>Duyệt<', false);

        $this->get(route('employees.index'))->assertOk();
        $this->get(route('leave_requests.index'))->assertOk();
        $this->get(route('attendance.index'))->assertOk();
        $this->get(route('contracts.index'))->assertOk();

        $this->post(route('accountant.leave_requests.approve', $leaveRequest))->assertForbidden();
        $this->post(route('accountant.leave_requests.reject', $leaveRequest), [
            'rejection_reason' => 'Không đủ điều kiện',
        ])->assertForbidden();
        $this->get(route('accountant.leave_requests.create'))->assertForbidden();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
        ]);
    }

    public function test_hr_user_can_approve_leave_from_hr_portal(): void
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false]);
        $employee = Employee::create([
            'name' => 'Tran Van B',
            'email' => 'employee2@example.com',
            'position' => 'Analyst',
            'department_id' => \App\Models\Department::create(['name' => 'HR', 'code' => 'HR', 'manager' => 'Manager'])->id,
            'status' => 'active',
        ]);
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-12',
            'days' => 3,
            'type' => 'annual',
            'reason' => 'Family trip',
            'status' => 'pending',
        ]);

        $this->actingAs($hr);

        $response = $this->post(route('leave_requests.approve', $leaveRequest));
        $response->assertRedirect(route('leave_requests.index'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $hr->id,
        ]);
    }

    public function test_admin_and_director_cannot_approve_leave_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_hr' => false, 'is_director' => false, 'is_accountant' => false]);
        $director = User::factory()->create(['is_admin' => false, 'is_hr' => false, 'is_director' => true, 'is_accountant' => false]);
        $department = \App\Models\Department::create([
            'name' => 'Sales',
            'code' => 'SAL',
            'manager' => 'Manager',
        ]);
        $employee = Employee::create([
            'name' => 'Le Van C',
            'email' => 'employee3@example.com',
            'position' => 'Staff',
            'department_id' => $department->id,
            'status' => 'active',
        ]);
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-02-10',
            'end_date' => '2025-02-11',
            'days' => 2,
            'type' => 'unpaid',
            'reason' => 'Personal',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('leave_requests.approve', $leaveRequest))
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('leave_requests.approve', $leaveRequest))
            ->assertForbidden();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
        ]);
    }
}
