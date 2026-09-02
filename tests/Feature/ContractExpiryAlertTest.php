<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractExpiryAction;
use App\Models\ContractExpiryAlert;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use App\Services\ContractExpiryAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_computed_alert_level_uses_end_date_without_persisting_warning_status(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');

        Carbon::setTestNow('2026-09-02');
        $this->assertSame(28, $contract->daysUntilExpiry());
        $this->assertSame(Contract::ALERT_EXPIRING, $contract->alertLevel());
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);

        Carbon::setTestNow('2026-09-23');
        $this->assertSame(7, $contract->fresh()->daysUntilExpiry());
        $this->assertSame(Contract::ALERT_URGENT, $contract->fresh()->alertLevel());
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);

        Carbon::setTestNow('2026-09-30');
        $this->assertSame(0, $contract->fresh()->daysUntilExpiry());
        $this->assertSame(Contract::ALERT_EXPIRED, $contract->fresh()->alertLevel());

        Carbon::setTestNow('2026-10-05');
        $this->assertSame(-5, $contract->fresh()->daysUntilExpiry());
        $this->assertSame(Contract::ALERT_OVERDUE, $contract->fresh()->alertLevel());
    }

    public function test_scheduler_sends_30_day_alert_to_hr_only_and_does_not_duplicate(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');

        Carbon::setTestNow('2026-09-02');
        $service = app(ContractExpiryAlertService::class);

        $this->assertSame(1, $service->dispatch(now()));
        $this->assertSame(0, $service->dispatch(now()));

        $this->assertEquals(1, Notification::query()->where('target', 'hr')->where('data->milestone', '30')->count());
        $this->assertEquals(0, Notification::query()->where('target', 'director')->count());
        $this->assertEquals(0, Notification::query()->where('target', 'employee')->count());
        $this->assertEquals(0, Notification::query()->where('target', 'admin')->count());

        $this->actingAs($people['hr'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Hợp đồng sắp hết hạn')
            ->assertSee('Nguyễn Văn A')
            ->assertSee('28 ngày');

        $this->actingAs($people['director'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Còn 28 ngày');
    }

    public function test_seven_day_milestone_notifies_hr_director_and_employee(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');
        $service = app(ContractExpiryAlertService::class);

        Carbon::setTestNow('2026-09-02');
        $service->dispatch(now());

        Carbon::setTestNow('2026-09-23');
        $service->dispatch(now());

        $this->assertDatabaseHas('contract_expiry_alerts', [
            'contract_id' => $contract->id,
            'milestone' => ContractExpiryAlert::MILESTONE_7,
            'target' => 'hr',
        ]);
        $this->assertEquals(1, Notification::query()->where('target', 'hr')->where('data->milestone', '7')->count());
        $this->assertEquals(1, Notification::query()->where('target', 'director')->where('data->milestone', '7')->count());
        $this->assertEquals(1, Notification::query()->where('target', 'employee')->where('data->milestone', '7')->count());

        $this->actingAs($people['employeeUser'])
            ->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Hợp đồng sắp hết hạn khẩn cấp');
    }

    public function test_expired_and_overdue_milestones_and_skip_overdue_when_successor_exists(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');
        $service = app(ContractExpiryAlertService::class);

        Carbon::setTestNow('2026-09-30');
        $service->dispatch(now());
        $this->assertEquals(1, Notification::query()->where('data->milestone', 'expired')->where('target', 'hr')->count());
        $this->assertEquals(1, Notification::query()->where('data->milestone', 'expired')->where('target', 'employee')->count());

        $otherEmployee = Employee::create([
            'name' => 'Nguyễn Văn B',
            'email' => 'nvb@example.com',
            'position' => 'Developer',
            'department_id' => $people['employee']->department_id,
            'status' => 'active',
            'employee_code' => 'CNTT-B-01',
        ]);
        $expired = Contract::create([
            'employee_id' => $otherEmployee->id,
            'title' => 'Hợp đồng lao động xác định thời hạn',
            'contract_code' => 'HD-B-2026',
            'contract_type' => 'fixed_term',
            'start_date' => '2025-10-01',
            'end_date' => '2026-09-30',
            'salary' => 15000000,
            'base_salary' => 15000000,
            'status' => Contract::STATUS_ACTIVE,
            'created_by' => $people['hr']->id,
            'employee_signed_at' => '2025-10-01 09:00:00',
            'director_signed_at' => '2025-10-01 11:00:00',
        ]);

        Carbon::setTestNow('2026-10-05');
        $service->dispatch(now());
        $this->assertEquals(1, Notification::query()
            ->where('data->contract_id', $contract->id)
            ->where('data->milestone', 'overdue')
            ->where('target', 'hr')
            ->count());
        $this->assertEquals(0, Notification::query()
            ->where('data->milestone', 'overdue')
            ->where('target', 'employee')
            ->count());

        Contract::create([
            'employee_id' => $otherEmployee->id,
            'parent_contract_id' => $expired->id,
            'title' => 'HĐ gia hạn',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'salary' => 16000000,
            'base_salary' => 16000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);

        Notification::query()->where('data->contract_id', $expired->id)->where('data->milestone', 'overdue')->delete();
        ContractExpiryAlert::query()->where('contract_id', $expired->id)->where('milestone', 'overdue')->delete();

        $service->dispatch(now());
        $this->assertEquals(0, Notification::query()
            ->where('data->contract_id', $expired->id)
            ->where('data->milestone', 'overdue')
            ->count());
    }

    public function test_hr_handle_renew_does_not_create_a_new_contract(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');
        Carbon::setTestNow('2026-09-02');

        $before = Contract::count();

        $this->actingAs($people['hr'])
            ->post(route('contracts.handle_expiry', $contract), [
                'decision' => 'renew',
                'reason' => 'Nhân viên hoàn thành KPI, đề xuất gia hạn 12 tháng.',
            ])
            ->assertRedirect(route('contracts.renew', $contract));

        $this->assertSame($before, Contract::count());
        $this->assertDatabaseHas('contract_expiry_actions', [
            'contract_id' => $contract->id,
            'decision' => ContractExpiryAction::RENEW,
        ]);
        $this->assertEquals(1, Notification::query()->where('target', 'director')->where('data->type', 'contract_expiry_decision')->count());
    }

    public function test_hr_handle_not_renew_redirects_to_termination_flow(): void
    {
        $people = $this->people();
        $contract = $this->activeContract($people, '2025-10-01', '2026-09-30');
        Carbon::setTestNow('2026-09-02');

        $this->actingAs($people['hr'])
            ->post(route('contracts.handle_expiry', $contract), [
                'decision' => 'not_renew',
                'reason' => 'Không nhu cầu nhân sự.',
            ])
            ->assertRedirect(route('deletion_requests.create_employee', $people['employee']));

        $this->assertEquals(0, Contract::query()->where('parent_contract_id', $contract->id)->count());
    }

    public function test_artisan_command_accepts_simulated_date(): void
    {
        $people = $this->people();
        $this->activeContract($people, '2025-10-01', '2026-09-30');

        $this->artisan('contracts:send-expiry-alerts', ['--date' => '2026-09-02'])
            ->assertSuccessful();

        $this->assertEquals(1, Notification::query()->where('data->milestone', '30')->count());
    }

    /**
     * @return array{hr: User, director: User, employee: Employee, employeeUser: User}
     */
    private function people(): array
    {
        $hr = User::factory()->create(['name' => 'HR User', 'is_hr' => true, 'is_admin' => false, 'is_director' => false]);
        $director = User::factory()->create(['name' => 'Director User', 'is_hr' => false, 'is_admin' => false, 'is_director' => true]);
        $employeeUser = User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'email' => 'nva@example.com',
            'is_hr' => false,
            'is_admin' => false,
            'is_director' => false,
            'is_accountant' => false,
        ]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nguyễn Văn A',
            'email' => $employeeUser->email,
            'user_id' => $employeeUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'CNTT-A-01',
        ]);

        return compact('hr', 'director', 'employee', 'employeeUser');
    }

    private function activeContract(array $people, string $start, string $end): Contract
    {
        return Contract::create([
            'employee_id' => $people['employee']->id,
            'title' => 'Hợp đồng lao động xác định thời hạn',
            'contract_code' => 'HD-A-2026',
            'contract_type' => 'fixed_term',
            'start_date' => $start,
            'end_date' => $end,
            'salary' => 15000000,
            'base_salary' => 15000000,
            'status' => Contract::STATUS_ACTIVE,
            'created_by' => $people['hr']->id,
            'employee_signed_at' => $start.' 09:00:00',
            'director_signed_at' => $start.' 11:00:00',
        ]);
    }
}
