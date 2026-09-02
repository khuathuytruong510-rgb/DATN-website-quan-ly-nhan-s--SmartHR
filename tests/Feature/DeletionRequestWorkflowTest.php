<?php

namespace Tests\Feature;

use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeHr(): User
    {
        return User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false]);
    }

    protected function makeDirector(): User
    {
        return User::factory()->create(['name' => 'Director', 'is_director' => true, 'is_hr' => false, 'is_admin' => false]);
    }

    protected function makeEmployee(): array
    {
        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Manager',
            'description' => 'Engineering department',
        ]);

        $employee = Employee::create([
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana-delete@example.com',
            'position' => 'Backend Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-DEL-001',
        ]);

        return compact('department', 'employee');
    }

    protected function submitEmployeeDeletion(User $hr, int $employeeId): DeletionRequest
    {
        $this->actingAs($hr)
            ->post(route('deletion_requests.store'), [
                'kind' => 'employee',
                'target' => $employeeId,
                'reason' => 'Nhân viên nghỉ việc và không còn tham gia công việc.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return DeletionRequest::query()->latest('id')->firstOrFail();
    }

    public function test_hr_submits_employee_deletion_request_and_notifies_director(): void
    {
        $hr = $this->makeHr();
        ['employee' => $employee] = $this->makeEmployee();

        $request = $this->submitEmployeeDeletion($hr, $employee->id);

        $this->assertSame(DeletionRequest::STATUS_PENDING, $request->status);
        $this->assertSame('employee', $request->kind);
        $this->assertSame($employee->id, $request->requestable_id);
        $this->assertSame($hr->id, $request->submitted_by);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Yêu cầu xóa nhân viên — Nguyen Van A',
        ]);
    }

    public function test_reason_is_required_when_submitting(): void
    {
        $hr = $this->makeHr();
        ['employee' => $employee] = $this->makeEmployee();

        $this->actingAs($hr)
            ->post(route('deletion_requests.store'), [
                'kind' => 'employee',
                'target' => $employee->id,
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_second_pending_request_for_same_target_is_blocked(): void
    {
        $hr = $this->makeHr();
        ['employee' => $employee] = $this->makeEmployee();

        $this->submitEmployeeDeletion($hr, $employee->id);

        $this->actingAs($hr)
            ->post(route('deletion_requests.store'), [
                'kind' => 'employee',
                'target' => $employee->id,
                'reason' => 'Gửi trùng lần thứ hai.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(
            1,
            DeletionRequest::query()->where('requestable_id', $employee->id)->where('status', 'pending')->count()
        );
    }

    public function test_hr_cannot_approve_and_director_cannot_execute(): void
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        ['employee' => $employee] = $this->makeEmployee();

        $request = $this->submitEmployeeDeletion($hr, $employee->id);

        $this->actingAs($hr)
            ->post(route('deletion_requests.approve', $request))
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('deletion_requests.execute', $request))
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('deletion_requests.cancel', $request))
            ->assertForbidden();
    }

    public function test_director_reject_requires_reason_and_keeps_employee(): void
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        ['employee' => $employee] = $this->makeEmployee();

        $request = $this->submitEmployeeDeletion($hr, $employee->id);

        $this->actingAs($director)
            ->post(route('deletion_requests.reject', $request))
            ->assertSessionHasErrors('review_note');

        $this->actingAs($director)
            ->post(route('deletion_requests.reject', $request), ['review_note' => 'Không đồng ý xóa tại thời điểm này'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DeletionRequest::STATUS_REJECTED, $request->status);
        $this->assertSame($director->id, $request->reviewed_by);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_full_employee_deletion_flow(): void
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        ['department' => $department, 'employee' => $employee] = $this->makeEmployee();

        $request = $this->submitEmployeeDeletion($hr, $employee->id);

        $this->actingAs($director)
            ->post(route('deletion_requests.approve', $request), ['review_note' => 'Đồng ý'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DeletionRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($director->id, $request->reviewed_by);

        $this->actingAs($hr)
            ->post(route('deletion_requests.execute', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DeletionRequest::STATUS_APPLIED, $request->status);
        $this->assertSame($hr->id, $request->applied_by);
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);

        $department->refresh();
        $this->assertSame(0, $department->employee_count);
    }

    public function test_execute_is_blocked_when_not_approved(): void
    {
        $hr = $this->makeHr();
        ['employee' => $employee] = $this->makeEmployee();

        $request = $this->submitEmployeeDeletion($hr, $employee->id);

        $this->actingAs($hr)
            ->post(route('deletion_requests.execute', $request))
            ->assertRedirect()
            ->assertSessionHas('error');

        $request->refresh();
        $this->assertSame(DeletionRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_department_deletion_is_blocked_while_employees_remain(): void
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        $department = Department::create([
            'name' => 'HR Dept',
            'code' => 'HRD',
            'manager' => 'Manager',
            'description' => 'HR department',
        ]);
        Employee::create([
            'name' => 'Tran Thi B',
            'email' => 'tranb-delete@example.com',
            'position' => 'HR Staff',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-DEL-002',
        ]);

        $this->actingAs($hr)
            ->post(route('deletion_requests.store'), [
                'kind' => 'department',
                'target' => $department->id,
                'reason' => 'Sáp nhập phòng ban vào khối vận hành.',
            ])
            ->assertSessionHasNoErrors();

        $request = DeletionRequest::query()->where('kind', 'department')->firstOrFail();

        $this->actingAs($director)
            ->post(route('deletion_requests.approve', $request))
            ->assertRedirect();

        $this->actingAs($hr)
            ->post(route('deletion_requests.execute', $request))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_department_deletion_after_employees_moved(): void
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        $department = Department::create([
            'name' => 'Finish Dept',
            'code' => 'FIN',
            'manager' => 'Manager',
            'description' => 'Finish department',
        ]);
        $newDepartment = Department::create([
            'name' => 'Ops Dept',
            'code' => 'OPS',
            'manager' => 'Manager',
            'description' => 'Ops department',
        ]);

        $this->actingAs($hr)
            ->post(route('deletion_requests.store'), [
                'kind' => 'department',
                'target' => $department->id,
                'reason' => 'Không còn hoạt động.',
            ]);

        $request = DeletionRequest::query()->where('kind', 'department')->firstOrFail();

        $this->actingAs($director)
            ->post(route('deletion_requests.approve', $request), ['review_note' => 'Đồng ý'])
            ->assertRedirect();

        $this->actingAs($hr)
            ->post(route('deletion_requests.execute', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DeletionRequest::STATUS_APPLIED, $request->status);
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);

        $newDepartment->refresh();
        $this->assertSame(0, $newDepartment->employee_count);
    }

    public function test_direct_delete_employee_route_redirects_to_approval_flow(): void
    {
        $hr = $this->makeHr();
        ['employee' => $employee] = $this->makeEmployee();

        $this->actingAs($hr)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('deletion_requests.create', ['kind' => 'employee', 'target' => $employee->id]));

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_direct_delete_department_route_redirects_to_approval_flow(): void
    {
        $hr = $this->makeHr();
        ['department' => $department] = $this->makeEmployee();

        $this->actingAs($hr)
            ->delete(route('departments.destroy', $department))
            ->assertRedirect(route('deletion_requests.create', ['kind' => 'department', 'target' => $department->id]));

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
