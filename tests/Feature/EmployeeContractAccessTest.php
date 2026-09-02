<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeContractAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_is_redirected_away_from_employee_portal(): void
    {
        $user = User::factory()->create([
            'name' => 'HR User',
            'email' => 'hr@example.com',
            'is_hr' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('me.contracts'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_staff_can_access_their_own_contract_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
        ]);

        $department = \App\Models\Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Dev Manager',
        ]);

        Employee::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'position' => 'Backend Developer',
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('me.contracts'));

        $response->assertOk();
        $response->assertSee('Hợp đồng của tôi');
    }
}
