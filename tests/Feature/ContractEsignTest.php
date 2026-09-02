<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractDocumentService;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractEsignTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;

    private User $director;

    private User $employeeUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false, 'is_director' => false]);
        $this->director = User::factory()->create(['name' => 'Director User', 'is_hr' => false, 'is_admin' => false, 'is_director' => true]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'Manager']);
        $this->employeeUser = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'esign-nv@example.com',
            'is_hr' => false,
            'is_admin' => false,
            'is_director' => false,
        ]);
        $this->employee = Employee::create([
            'name' => 'Nguyen Van Esign',
            'email' => 'esign-nv@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-ESIGN-01',
            'user_id' => $this->employeeUser->id,
        ]);
    }

    public function test_hr_send_locks_hash_and_director_sign_verifies(): void
    {
        $service = app(ContractService::class);
        $contract = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng ký số',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_content' => 'Điều khoản gốc không được sửa sau khi khóa.',
        ]);

        $this->actingAs($this->hr)
            ->post(route('contracts.send_for_signature', $contract))
            ->assertRedirect();

        $contract->refresh();
        $this->assertNotNull($contract->content_locked_at);
        $this->assertNotEmpty($contract->document_hash);
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $contract->status);
        $this->assertTrue($contract->isContentLocked());

        $lockedContent = $contract->fresh()->contract_content;

        $this->actingAs($this->hr)
            ->put(route('contracts.update', $contract), [
                'employee_id' => $this->employee->id,
                'title' => 'Đổi nội dung sau khóa',
                'contract_type' => 'fixed_term',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'contract_content' => 'Nội dung bị sửa sau khi khóa',
            ])
            ->assertSessionHas('error');

        $contract->refresh();
        $this->assertSame($lockedContent, $contract->contract_content);

        $this->actingAs($this->director)
            ->post(route('contracts.sign', $contract), ['party' => 'director'])
            ->assertRedirect();

        $contract->refresh()->load('directorSignature', 'employeeSignature', 'logs');
        $this->assertNotNull($contract->director_signed_at);
        $this->assertNull($contract->employee_signed_at);
        $this->assertSame(Contract::STATUS_DIRECTOR_SIGNED, $contract->status);
        $this->assertNotNull($contract->directorSignature);
        $this->assertTrue($service->verifyDirectorSignature($contract));

        $this->actingAs($this->employeeUser)
            ->post(route('me.contracts.sign', $contract))
            ->assertRedirect();

        $contract->refresh()->load('directorSignature', 'employeeSignature', 'logs');
        $this->assertNotNull($contract->employee_signed_at);
        $this->assertNotNull($contract->employeeSignature);
        $this->assertSame(ContractSignature::STATUS_SIGNED, $contract->employeeSignature->status);
        $this->assertTrue($service->verifyAllSignatures($contract));
        $this->assertTrue($contract->logs->contains(fn ($log) => $log->action === 'signature_verified'));
        $this->assertContains($contract->status, [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE]);
    }

    public function test_tampered_terms_make_signature_invalid(): void
    {
        $service = app(ContractService::class);
        $contract = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng hash',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_content' => 'Nội dung gốc.',
        ]);
        $service->sendForDirectorSignature($this->hr, $contract);
        $service->signContract($this->director, $contract->fresh(), 'director');
        $service->signContract($this->employeeUser, $contract->fresh(), 'employee');

        $contract->refresh();
        $this->assertTrue($service->verifyAllSignatures($contract));

        $contract->forceFill(['contract_content' => 'Nội dung bị giả mạo sau khi ký.'])->save();
        $contract->refresh()->load('directorSignature', 'employeeSignature');

        $this->assertFalse(app(ContractDocumentService::class)->matchesFrozenHash($contract));
        $this->assertFalse($service->verifyDirectorSignature($contract));
        $this->assertFalse($service->verifyEmployeeSignature($contract));
        $this->assertFalse($service->verifyAllSignatures($contract));
    }

    public function test_hr_and_admin_cannot_director_sign(): void
    {
        $service = app(ContractService::class);
        $contract = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng quyền',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $service->sendForDirectorSignature($this->hr, $contract);

        $this->actingAs($this->hr)
            ->post(route('contracts.sign', $contract), ['party' => 'director'])
            ->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true, 'is_hr' => false, 'is_director' => false, 'is_accountant' => false]);
        $this->actingAs($admin)
            ->post(route('contracts.sign', $contract), ['party' => 'director'])
            ->assertForbidden();

        $contract->refresh();
        $this->assertNull($contract->director_signed_at);
    }

    public function test_director_reject_returns_editable_draft_then_can_resend(): void
    {
        $service = app(ContractService::class);
        $contract = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng từ chối',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_content' => 'Bản 1',
        ]);
        $service->sendForDirectorSignature($this->hr, $contract);

        $this->actingAs($this->director)
            ->post(route('contracts.reject_signature', $contract), ['reason' => 'Sai mức lương thỏa thuận'])
            ->assertRedirect();

        $contract->refresh();
        $this->assertNull($contract->content_locked_at);
        $this->assertFalse($contract->isContentLocked());
        $this->assertNull($contract->director_signed_at);

        $service->updateContract($this->hr, $contract, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng từ chối',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_content' => 'Bản 2 đã sửa lương',
        ]);

        $service->sendForDirectorSignature($this->hr, $contract->fresh());
        $contract->refresh();
        $this->assertSame(Contract::STATUS_PENDING_SIGNATURE, $contract->status);
        $this->assertNotNull($contract->content_locked_at);
    }

    public function test_renewal_clears_lock_and_signatures(): void
    {
        $service = app(ContractService::class);
        $parent = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng gốc',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'contract_content' => 'Điều khoản gốc gia hạn.',
        ]);
        $service->sendForDirectorSignature($this->hr, $parent);
        $service->signContract($this->director, $parent->fresh(), 'director');
        $service->signContract($this->employeeUser, $parent->fresh(), 'employee');

        $parent->refresh();
        $this->assertNotNull($parent->director_signed_at);
        $this->assertTrue($parent->isContentLocked());
        $parentContent = $parent->contract_content;

        $renewed = $service->renewContract($this->hr, $parent, [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

        $this->assertSame($parentContent, $renewed->contract_content);
        $this->assertNull($renewed->employee_signed_at);
        $this->assertNull($renewed->director_signed_at);
        $this->assertNull($renewed->content_locked_at);
        $this->assertNull($renewed->document_hash);
        $this->assertSame(0, $renewed->signatures()->count());
        $this->assertFalse($renewed->isContentLocked());
    }

    public function test_document_page_is_visible_to_employee_owner(): void
    {
        $service = app(ContractService::class);
        $contract = $service->createContract($this->hr, [
            'employee_id' => $this->employee->id,
            'title' => 'Hợp đồng xem PDF',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($this->employeeUser)
            ->get(route('me.contracts.document', $contract))
            ->assertOk()
            ->assertSee($contract->contract_code)
            ->assertSee('mô phỏng', false);
    }
}
