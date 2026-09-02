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
            'code' => 'ENG',
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
        $director = User::factory()->create(['name' => 'Director User', 'is_hr' => false, 'is_admin' => false, 'is_director' => true]);

        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
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

        $this->assertEquals('draft', $contract->status);
        $this->assertEquals(18000000, $contract->salary);
        $this->assertEquals($hr->id, $contract->created_by);
        $this->assertNull($contract->employee_signed_at);
        $this->assertNull($contract->director_signed_at);

        $renewed = $service->renewContract($hr, $contract, [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'contract_type' => 'indefinite',
            'notes' => 'Renewed contract',
        ]);

        $this->assertNotNull($renewed->parent_contract_id);
        $this->assertEquals($contract->id, $renewed->parent_contract_id);
        $this->assertEquals('draft', $renewed->status);
        $this->assertEquals($employee->id, $renewed->employee_id);
        $this->assertEquals($contract->contract_type, $renewed->contract_type);
        $this->assertEquals((float) $contract->salary, (float) $renewed->salary);

        $service->sendForDirectorSignature($hr, $renewed);
        $renewed->refresh();
        $this->assertEquals(Contract::STATUS_PENDING_SIGNATURE, $renewed->status);

        try {
            $service->signContract($employeeUser, $renewed, 'employee');
            $this->fail('Nhân viên không được ký trước Giám đốc.');
        } catch (\RuntimeException) {
        }

        $service->signContract($director, $renewed->fresh(), 'director');
        $renewed->refresh();
        $this->assertEquals(Contract::STATUS_DIRECTOR_SIGNED, $renewed->status);
        $this->assertNotNull($renewed->director_signed_at);
        $this->assertNull($renewed->employee_signed_at);

        $service->signContract($employeeUser, $renewed, 'employee');
        $renewed->refresh();
        $this->assertContains($renewed->status, [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE]);
        $this->assertNotNull($renewed->employee_signed_at);
        $this->assertTrue($renewed->isFullySigned());
    }

    public function test_rejects_overlapping_contract_periods_for_same_employee(): void
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false]);
        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Manager',
        ]);
        $employee = Employee::create([
            'name' => 'Overlap Test',
            'email' => 'overlap@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-OVR',
        ]);

        $service = app(ContractService::class);
        $service->createContract($hr, [
            'employee_id' => $employee->id,
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('trùng khoảng thời gian');

        $service->createContract($hr, [
            'employee_id' => $employee->id,
            'contract_type' => 'fixed_term',
            'start_date' => '2026-10-01',
            'end_date' => '2027-12-31',
        ]);
    }

    public function test_allows_renewal_when_new_period_starts_after_parent_ends(): void
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false]);
        $department = Department::create(['name' => 'ENG', 'code' => 'ENG', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Renewal OK',
            'email' => 'renewok@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-RNW',
        ]);

        $service = app(ContractService::class);
        $parent = $service->createContract($hr, [
            'employee_id' => $employee->id,
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $renewed = $service->renewContract($hr, $parent, [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

        $this->assertSame('2027-01-01', $renewed->start_date->toDateString());
        $this->assertSame('2027-12-31', $renewed->end_date->toDateString());
    }

    public function test_hr_cannot_sign_on_behalf_of_employee_and_director_signs_before_employee(): void
    {
        $hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false, 'is_director' => false]);
        $director = User::factory()->create(['name' => 'Director User', 'is_hr' => false, 'is_admin' => false, 'is_director' => true]);
        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Manager',
        ]);
        $employee = Employee::create([
            'name' => 'Nguyen Van C',
            'email' => 'nguyenvanc@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP003',
        ]);
        $employeeUser = User::factory()->create([
            'name' => 'Employee C',
            'email' => 'nguyenvanc@example.com',
            'is_hr' => false,
            'is_admin' => false,
            'is_director' => false,
            'is_accountant' => false,
        ]);
        $employee->user_id = $employeeUser->id;
        $employee->save();

        $this->actingAs($hr)
            ->post(route('contracts.store'), [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'title' => 'Hợp đồng chính thức',
                'contract_type' => 'fixed_term',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'sign_and_save' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $contract = Contract::query()->where('employee_id', $employee->id)->latest('id')->first();
        $this->assertNotNull($contract);
        $this->assertNull($contract->employee_signed_at);
        $this->assertNull($contract->director_signed_at);
        $this->assertContains($contract->status, ['draft', 'waiting_employee_signature', 'waiting_employee']);

        $this->actingAs($hr)
            ->post(route('contracts.sign', $contract), ['party' => 'employee'])
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('contracts.sign', $contract), ['party' => 'director'])
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->post(route('me.contracts.sign', $contract))
            ->assertRedirect();

        $contract->refresh();
        $this->assertNull($contract->employee_signed_at);
        $this->assertNull($contract->director_signed_at);

        $this->actingAs($hr)
            ->post(route('contracts.send_for_signature', $contract))
            ->assertRedirect();

        $contract->refresh();
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $contract->status);

        $this->actingAs($director)
            ->post(route('contracts.sign', $contract), ['party' => 'director'])
            ->assertRedirect();

        $contract->refresh();
        $this->assertNotNull($contract->director_signed_at);
        $this->assertNull($contract->employee_signed_at);
        $this->assertSame(Contract::STATUS_DIRECTOR_SIGNED, $contract->status);

        $this->actingAs($employeeUser)
            ->post(route('me.contracts.sign', $contract))
            ->assertRedirect();

        $contract->refresh();
        $this->assertNotNull($contract->employee_signed_at);
        $this->assertEquals('active', $contract->status);
    }

    public function test_renewal_only_extends_dates_and_keeps_parent_content(): void
    {
        $hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'Manager']);
        $employee = Employee::create([
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana-renew@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-RENEW-01',
        ]);
        $parent = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'Hợp đồng xác định thời hạn',
            'contract_code' => 'HD-OLD-2025',
            'contract_type' => 'fixed_term',
            'start_date' => '2025-10-01',
            'end_date' => '2026-09-30',
            'salary' => 15000000,
            'base_salary' => 15000000,
            'allowance' => 1500000,
            'terms' => 'Điều khoản gốc của hợp đồng cũ.',
            'contract_content' => 'Điều khoản gốc của hợp đồng cũ.',
            'workplace' => 'Hà Nội',
            'status' => Contract::STATUS_ACTIVE,
            'employee_signed_at' => '2025-10-01 09:00:00',
            'director_signed_at' => '2025-10-01 11:00:00',
        ]);

        $this->actingAs($hr)
            ->get(route('contracts.renew', $parent))
            ->assertOk()
            ->assertSee('Gia hạn không đổi nội dung')
            ->assertSee('Điều khoản gốc của hợp đồng cũ.')
            ->assertSee('15.000.000')
            ->assertDontSee('name="base_salary"', false)
            ->assertDontSee('name="contract_type"', false);

        $this->actingAs($hr)
            ->post(route('contracts.storeRenewal', $parent), [
                'start_date' => '2026-10-01',
                'end_date' => '2027-09-30',
                'contract_type' => 'indefinite',
                'base_salary' => 99999999,
                'contract_content' => 'Nội dung HR tự gõ — không được lưu',
            ])
            ->assertRedirect();

        $renewed = Contract::query()->where('parent_contract_id', $parent->id)->latest('id')->first();
        $this->assertNotNull($renewed);
        $this->assertSame('2026-10-01', $renewed->start_date?->toDateString());
        $this->assertSame('2027-09-30', $renewed->end_date?->toDateString());
        $this->assertSame('fixed_term', $renewed->contract_type);
        $this->assertEquals(15000000, (float) $renewed->base_salary);
        $this->assertEquals(1500000, (float) $renewed->allowance);
        $this->assertSame('Điều khoản gốc của hợp đồng cũ.', $renewed->contract_content);
        $this->assertSame('Hà Nội', $renewed->workplace);
        $this->assertNull($renewed->employee_signed_at);
        $this->assertNull($renewed->director_signed_at);
    }

    public function test_director_sign_notifies_admin_to_create_account_then_email_is_sent(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_director' => true]);
        $admin = User::factory()->create(['is_hr' => false, 'is_admin' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'Manager']);
        $employee = Employee::create([
            'name' => 'New Hire',
            'email' => 'newhire@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-NEW-01',
            'user_id' => null,
        ]);

        $service = app(ContractService::class);
        $contract = $service->createContract($hr, [
            'employee_id' => $employee->id,
            'employee_email' => 'newhire@example.com',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'base_salary' => 12000000,
        ]);

        $service->sendForDirectorSignature($hr, $contract);
        $service->signContract($director, $contract->fresh(), 'director');

        $this->assertDatabaseHas('notifications', [
            'target' => 'admin',
            'title' => 'Tạo tài khoản để nhân viên ký hợp đồng',
        ]);

        $this->actingAs($admin)
            ->post(route('accounts.store'), [
                'name' => $employee->name,
                'email' => 'newhire@example.com',
                'password' => '123456',
                'password_confirmation' => '123456',
                'role' => 'employee',
                'employee_id' => $employee->id,
                'contract_id' => $contract->id,
            ])
            ->assertRedirect(route('accounts.index'));

        $employee->refresh();
        $this->assertNotNull($employee->user_id);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContractAccountCredentialsMail::class, function ($mail) use ($employee) {
            return $mail->hasTo('newhire@example.com')
                && $mail->loginEmail === 'newhire@example.com'
                && $mail->plainPassword === '123456'
                && $mail->employee->is($employee);
        });

        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Hợp đồng cần bạn ký',
        ]);
    }
}
