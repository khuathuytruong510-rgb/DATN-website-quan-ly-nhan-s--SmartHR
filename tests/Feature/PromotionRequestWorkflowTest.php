<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\PromotionRequest;
use App\Models\SalaryHistory;
use App\Models\User;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionRequestWorkflowTest extends TestCase
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

    protected function makeEmployeeWithContract(array $overrides = []): array
    {
        $hr = $this->makeHr();
        $director = $this->makeDirector();
        $department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager' => 'Manager',
            'description' => 'Engineering department',
        ]);
        $position = Position::create([
            'name' => 'Dev Team Lead',
            'level' => 'lead',
            'salary_range_min' => 20000000,
            'salary_range_max' => 30000000,
            'allowance' => 1500000,
            'base_salary' => 22000000,
        ]);
        $employee = Employee::create(array_merge([
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'position' => 'Backend Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMP-PROMO-001',
        ], $overrides));

        $contract = app(ContractService::class)->createContract($hr, [
            'employee_id' => $employee->id,
            'title' => 'Hợp đồng lao động',
            'contract_type' => 'fixed_term',
            'base_salary' => 18000000,
            'allowance' => 1000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        return compact('hr', 'director', 'department', 'position', 'employee', 'contract');
    }

    protected function promotionPayload(int $employeeId, array $data = []): array
    {
        return array_merge([
            'employee_id' => $employeeId,
            'change_type' => 'both',
            'new_position_id' => 1,
            'new_position' => 'Dev Team Lead',
            'department_id' => 1,
            'old_base_salary' => 18000000,
            'new_base_salary' => 22000000,
            'old_allowance' => 1000000,
            'new_allowance' => 1500000,
            'effective_date' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'reason' => 'Hoàn thành xuất sắc KPI 3 tháng liên tiếp.',
            'document_number' => 'QĐ-TC-2026/01',
        ], $data);
    }

    public function test_full_promotion_flow_updates_contract_and_records_salary_history(): void
    {
        [
            'hr' => $hr,
            'director' => $director,
            'position' => $position,
            'employee' => $employee,
            'contract' => $contract,
        ] = $this->makeEmployeeWithContract();

        $payload = $this->promotionPayload($employee->id, ['new_position_id' => $position->id]);

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('promotion_requests.show', PromotionRequest::query()->latest('id')->first()));

        $promotion = PromotionRequest::query()->latest('id')->first();
        $this->assertNotNull($promotion);
        $this->assertSame('pending', $promotion->status);
        $this->assertSame('Backend Developer', $promotion->old_position);
        $this->assertSame('Dev Team Lead', $promotion->new_position);

        $this->actingAs($director)
            ->post(route('promotion_requests.approve', $promotion), ['review_note' => 'Đồng ý'])
            ->assertRedirect();

        $promotion->refresh();
        $this->assertSame('approved', $promotion->status);
        $this->assertSame($director->id, $promotion->reviewed_by);

        $this->actingAs($hr)
            ->post(route('promotion_requests.apply', $promotion))
            ->assertRedirect();

        $promotion->refresh();
        $this->assertSame('applied', $promotion->status);
        $this->assertSame($hr->id, $promotion->applied_by);

        $employee->refresh();
        $this->assertSame('Dev Team Lead', $employee->position);
        $this->assertSame($position->id, $employee->position_id);

        $contract->refresh();
        $this->assertEquals(22000000, $contract->base_salary);
        $this->assertEquals(1500000, $contract->allowance);

        $history = SalaryHistory::query()->where('employee_id', $employee->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('Thăng chức & tăng lương', $history->change_type);
        $this->assertEquals(18000000, $history->old_salary);
        $this->assertEquals(22000000, $history->new_salary);
        $this->assertSame('Dev Team Lead', $history->position);
        $this->assertSame('applied', $history->status);

        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
        ]);
        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_hr_cannot_approve_and_director_cannot_apply(): void
    {
        [
            'hr' => $hr,
            'director' => $director,
            'position' => $position,
            'employee' => $employee,
        ] = $this->makeEmployeeWithContract();

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $this->promotionPayload($employee->id, ['new_position_id' => $position->id]));

        $promotion = PromotionRequest::query()->latest('id')->first();

        $this->actingAs($hr)
            ->post(route('promotion_requests.approve', $promotion))
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('promotion_requests.apply', $promotion))
            ->assertForbidden();
    }

    public function test_second_pending_promotion_request_is_blocked(): void
    {
        [
            'hr' => $hr,
            'position' => $position,
            'employee' => $employee,
        ] = $this->makeEmployeeWithContract();

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $this->promotionPayload($employee->id, ['new_position_id' => $position->id]))
            ->assertSessionHasNoErrors();

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $this->promotionPayload($employee->id, ['new_position_id' => $position->id, 'document_number' => 'QĐ-TC-2026/02']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(
            1,
            PromotionRequest::query()->where('employee_id', $employee->id)->where('status', 'pending')->count()
        );
    }

    public function test_reject_flow_requires_reason_and_keeps_data_unchanged(): void
    {
        [
            'hr' => $hr,
            'director' => $director,
            'position' => $position,
            'employee' => $employee,
            'contract' => $contract,
        ] = $this->makeEmployeeWithContract();

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $this->promotionPayload($employee->id, ['new_position_id' => $position->id]));

        $promotion = PromotionRequest::query()->latest('id')->first();

        $this->actingAs($director)
            ->post(route('promotion_requests.reject', $promotion))
            ->assertSessionHasErrors('review_note');

        $this->actingAs($director)
            ->post(route('promotion_requests.reject', $promotion), ['review_note' => 'Chưa phù hợp thời điểm hiện tại'])
            ->assertRedirect();

        $promotion->refresh();
        $this->assertSame('rejected', $promotion->status);

        $employee->refresh();
        $this->assertSame('Backend Developer', $employee->position);

        $this->assertDatabaseMissing('salary_histories', ['employee_id' => $employee->id]);
    }

    public function test_apply_is_blocked_when_not_approved(): void
    {
        [
            'hr' => $hr,
            'director' => $director,
            'position' => $position,
            'employee' => $employee,
        ] = $this->makeEmployeeWithContract();

        $this->actingAs($hr)
            ->post(route('promotion_requests.store'), $this->promotionPayload($employee->id, ['new_position_id' => $position->id]));

        $promotion = PromotionRequest::query()->latest('id')->first();

        $this->actingAs($hr)
            ->post(route('promotion_requests.apply', $promotion))
            ->assertRedirect();

        $promotion->refresh();
        $this->assertSame('pending', $promotion->status);
        $this->assertNull($promotion->applied_at);
    }
}