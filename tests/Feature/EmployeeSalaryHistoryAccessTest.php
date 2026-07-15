<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryHistoryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_access_their_salary_history_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'salary.employee@example.com',
        ]);

        $department = Department::create([
            'name' => 'Engineering',
            'manager' => 'Manager',
        ]);

        Employee::create([
            'name' => 'Employee User',
            'email' => 'salary.employee@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('me.salary_histories'));

        $response->assertOk();
        $response->assertSee('Lịch sử lương của tôi');
    }
}
