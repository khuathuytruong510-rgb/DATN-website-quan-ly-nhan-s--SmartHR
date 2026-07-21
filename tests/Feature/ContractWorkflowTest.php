<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_uses_default_template_for_selected_contract_type(): void
    {
        ContractTemplate::create([
            'title' => 'Mẫu thử việc',
            'content' => 'Điều khoản thử việc',
            'contract_type' => 'probation',
            'is_default' => true,
            'status' => 'active',
        ]);

        ContractTemplate::create([
            'title' => 'Mẫu thực tập',
            'content' => 'Điều khoản thực tập',
            'contract_type' => 'internship',
            'is_default' => true,
            'status' => 'active',
        ]);

        $hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false]);
        $department = Department::create([
            'name' => 'Engineering',
            'manager' => 'Manager',
            'description' => 'Engineering department',
        ]);
        $employee = Employee::create([
            'name' => 'Nguyen Van B',
            'email' => 'nguyenvanb@example.com',
            'position' => 'Backend Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP002',
        ]);

        $service = app(ContractService::class);
        $contract = $service->createContract($hr, [
            'employee_id' => $employee->id,
            'title' => 'Hợp đồng thực tập',
            'contract_type' => 'internship',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'notes' => 'Internship contract',
        ]);

        $this->assertSame('Điều khoản thực tập', $contract->contract_content);
        $this->assertSame('Điều khoản thực tập', $contract->terms);
    }

    public function test_contract_creation_renewal_and_signing_flow(): void
    {
        $hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false]);
        $director = User::factory()->create(['name' => 'Director User', 'is_hr' => false, 'is_admin' => true]);

        $department = Department::create([
            'name' => 'Engineering',
            'manager' => 'Manager',
            'description' => 'Engineering department',
        ]);

        $employee = Employee::create([
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'position' => 'Backend Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP001',
        ]);

        $employeeUser = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'is_hr' => false,
            'is_admin' => false,
        ]);
        $employee->user_id = $employeeUser->id;
        $employee->save();

        $service = app(ContractService::class);

        $contract = $service->createContract($hr, [
            'employee_id' => $employee->id,
            'title' => 'Hợp đồng thử việc',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'notes' => 'Initial contract',
        ]);

        $this->assertEquals('waiting_employee_signature', $contract->status);
        $this->assertEquals(18000000, $contract->salary);
        $this->assertEquals($hr->id, $contract->created_by);
        $this->assertNull($contract->employee_signed_at);
        $this->assertNull($contract->director_signed_at);

        $renewed = $service->renewContract($hr, $contract, [
            'start_date' => '2026-02-01',
            'end_date' => '2027-01-31',
            'contract_type' => 'indefinite',
            'notes' => 'Renewed contract',
        ]);

        $this->assertNotNull($renewed->parent_contract_id);
        $this->assertEquals($contract->id, $renewed->parent_contract_id);
        $this->assertEquals('waiting_employee_signature', $renewed->status);
        $this->assertEquals($employee->id, $renewed->employee_id);

        $service->signContract($employeeUser, $renewed, 'employee');
        $renewed->refresh();
        $this->assertEquals('waiting_director_signature', $renewed->status);
        $this->assertNotNull($renewed->employee_signed_at);

        $service->signContract($director, $renewed, 'director');
        $renewed->refresh();
        $this->assertEquals('active', $renewed->status);
        $this->assertNotNull($renewed->director_signed_at);
    }
}
