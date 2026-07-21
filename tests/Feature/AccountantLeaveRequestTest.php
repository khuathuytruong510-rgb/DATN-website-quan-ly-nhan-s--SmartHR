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

    public function test_accountant_can_view_and_approve_leave_requests(): void
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

        $this->actingAs($accountant);

        $response = $this->get(route('accountant.leave_requests'));
        $response->assertOk();
        $response->assertSee('Tạo đơn nghỉ');

        $approveResponse = $this->post(route('accountant.leave_requests.approve', $leaveRequest));
        $approveResponse->assertRedirect(route('accountant.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $accountant->id,
        ]);
    }

    public function test_hr_user_can_access_leave_request_approval_flow(): void
    {
        $hr = User::factory()->create(['is_hr' => true]);
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

        $response = $this->post(route('accountant.leave_requests.approve', $leaveRequest));
        $response->assertRedirect(route('accountant.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $hr->id,
        ]);
    }
}
