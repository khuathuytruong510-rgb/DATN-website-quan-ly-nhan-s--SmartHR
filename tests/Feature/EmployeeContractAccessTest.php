<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeContractAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_access_their_own_contract_page(): void
    {
        $user = User::factory()->create([
            'name' => 'HR User',
            'email' => 'hr@example.com',
            'is_hr' => true,
        ]);

        $department = \App\Models\Department::create([
            'name' => 'HR Department',
            'code' => 'HRD',
            'manager' => 'HR Manager',
        ]);

        Employee::create([
            'name' => 'HR User',
            'email' => 'hr@example.com',
            'position' => 'HR Manager',
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('me.contracts'));

        $response->assertOk();
        $response->assertSee('Hợp đồng của tôi');
    }
}
