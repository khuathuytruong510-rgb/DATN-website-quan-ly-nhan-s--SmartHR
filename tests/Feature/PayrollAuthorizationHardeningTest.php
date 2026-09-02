<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryHistory;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function seedPeople(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'Manager']);

        $aliceUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $bobUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);

        $alice = Employee::create([
            'name' => 'Alice',
            'email' => $aliceUser->email,
            'user_id' => $aliceUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPA',
        ]);
        $bob = Employee::create([
            'name' => 'Bob',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPB',
        ]);

        $alicePayroll = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED,
        ]);
        $bobPayroll = Payroll::create([
            'employee_id' => $bob->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 12000000,
            'total_salary' => 12000000,
            'status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED,
        ]);

        return compact('hr', 'director', 'accountant', 'aliceUser', 'bobUser', 'alice', 'bob', 'alicePayroll', 'bobPayroll', 'department');
    }

    public function test_accountant_and_employee_cannot_write_hr_via_api_or_web(): void
    {
        ['accountant' => $accountant, 'aliceUser' => $aliceUser, 'department' => $department] = $this->seedPeople();

        $this->actingAs($accountant)->postJson('/api/employees', [
            'name' => 'Hacker',
            'email' => 'hacker-api@example.com',
            'position' => 'Staff',
            'department_id' => $department->id,
        ])->assertForbidden();

        $this->actingAs($accountant)->postJson('/api/departments', [
            'name' => 'Fake',
            'code' => 'FAKE',
        ])->assertForbidden();

        $this->actingAs($aliceUser)->getJson('/api/employees')->assertForbidden();
        $this->actingAs($aliceUser)->deleteJson('/api/payroll/1')->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('departments.store'), ['name' => 'X', 'code' => 'XX', 'manager' => 'M'])
            ->assertForbidden();
    }

    public function test_director_cannot_generate_or_update_employees(): void
    {
        ['director' => $director, 'alice' => $alice] = $this->seedPeople();

        $this->actingAs($director)
            ->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])
            ->assertForbidden();

        $this->actingAs($director)
            ->put(route('employees.update', $alice), [
                'name' => 'Hacked',
                'email' => $alice->email,
                'department_id' => $alice->department_id,
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_confirm_or_report_another_persons_payroll(): void
    {
        ['aliceUser' => $aliceUser, 'bobPayroll' => $bobPayroll, 'alicePayroll' => $alicePayroll] = $this->seedPeople();

        $this->actingAs($aliceUser)
            ->post(route('me.payroll.confirm', $bobPayroll))
            ->assertForbidden();

        $this->actingAs($aliceUser)
            ->post(route('me.payroll.report_issue', $bobPayroll), ['issue_report' => 'Sai phụ cấp'])
            ->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $bobPayroll->fresh()->status);

        $workflow = app(PayrollPaymentWorkflowService::class);
        $workflow->reportIssue($alicePayroll->fresh(), 'Thiếu phụ cấp', $aliceUser);

        $this->actingAs($aliceUser)
            ->post(route('me.payroll.confirm', $alicePayroll->fresh()))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $alicePayroll->fresh()->status);
    }

    public function test_accountant_cannot_recalculate_after_hr_checked_or_lock_individual_slip(): void
    {
        ['accountant' => $accountant, 'alicePayroll' => $payroll] = $this->seedPeople();
        $payroll->update(['status' => PayrollPaymentWorkflowService::HR_CHECKED]);

        $this->actingAs($accountant)
            ->post(route('accountant.payroll.recalculate', $payroll))
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $payroll->fresh()->status);

        $this->actingAs($accountant)
            ->post(route('accountant.payroll.lock', $payroll))
            ->assertForbidden();
    }

    public function test_cannot_pay_twice_or_pay_before_employee_confirmed(): void
    {
        ['accountant' => $accountant, 'alicePayroll' => $payroll] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $this->expectException(\RuntimeException::class);
        $workflow->markPaid($payroll->fresh(), ['payment_method' => 'cash'], $accountant);
    }

    public function test_issue_loop_requires_hr_then_director_before_confirm(): void
    {
        ['hr' => $hr, 'director' => $director, 'aliceUser' => $aliceUser, 'alicePayroll' => $payroll] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $workflow->reportIssue($payroll->fresh(), 'Sai số', $aliceUser);
        $workflow->remediateIssue($payroll->fresh(), [
            'base_salary' => 10000000,
            'working_salary' => 10000000,
            'overtime_salary' => 0,
            'allowance' => 0,
            'bonus' => 0,
            'insurance' => 0,
            'tax' => 0,
            'deduction' => 0,
        ], $hr);

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);

        $this->actingAs($aliceUser)
            ->post(route('me.payroll.confirm', $payroll->fresh()))
            ->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);

        $this->actingAs($director)
            ->post(route('payroll.approve', $payroll->fresh()))
            ->assertForbidden();

        $this->actingAs($hr)->post(route('payroll.review', $payroll->fresh()))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);
    }

    public function test_get_idor_hides_other_employees_payroll_contract_attendance(): void
    {
        ['aliceUser' => $aliceUser, 'alicePayroll' => $mine, 'bobPayroll' => $theirs, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();

        $myContract = Contract::create([
            'employee_id' => $alice->id,
            'title' => 'HĐ Alice',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => 'waiting_employee_signature',
        ]);
        $theirContract = Contract::create([
            'employee_id' => $bob->id,
            'title' => 'HĐ Bob',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => 'waiting_employee_signature',
        ]);
        $myAtt = Attendance::create(['employee_id' => $alice->id, 'date' => '2026-08-01', 'status' => 'present']);
        $theirAtt = Attendance::create(['employee_id' => $bob->id, 'date' => '2026-08-01', 'status' => 'present']);
        $theirHistory = SalaryHistory::create([
            'employee_id' => $bob->id,
            'payroll_id' => $theirs->id,
            'period' => '08/2026',
            'effective_date' => '2026-08-01',
            'change_type' => SalaryHistory::CHANGE_PAYMENT,
            'old_salary' => 12000000,
            'new_salary' => 12000000,
            'status' => SalaryHistory::STATUS_APPLIED,
        ]);

        $this->actingAs($aliceUser)->get(route('me.payroll.show', $theirs))->assertForbidden();
        $this->actingAs($aliceUser)->get(route('me.payroll.show', $mine))->assertOk();
        $this->actingAs($aliceUser)->get(route('me.payroll.history', $theirs))->assertForbidden();
        $this->actingAs($aliceUser)->get(route('me.salary_histories.show', $theirHistory))->assertForbidden();
        $this->actingAs($aliceUser)->get(route('me.contracts.show', $theirContract))->assertForbidden();
        $this->actingAs($aliceUser)->get(route('me.contracts.show', $myContract))->assertOk();
        $this->actingAs($aliceUser)->get(route('me.attendance.show', $theirAtt))->assertForbidden();
        $this->actingAs($aliceUser)->get(route('me.attendance.show', $myAtt))->assertRedirect();

        $this->actingAs($aliceUser)->get(route('payroll.show', $theirs))->assertForbidden();
        $this->actingAs($aliceUser)->getJson('/api/payroll/'.$theirs->id)->assertForbidden();
        $this->actingAs($aliceUser)->patch('/me/payroll/'.$theirs->id, ['status' => 'paid'])->assertForbidden();
        $this->actingAs($aliceUser)->putJson('/api/payroll/'.$theirs->id, ['status' => 'paid'])->assertStatus(405);
    }

    public function test_client_cannot_mass_assign_payroll_status_or_identity(): void
    {
        ['director' => $director, 'aliceUser' => $aliceUser, 'bob' => $bob, 'alicePayroll' => $payroll] = $this->seedPeople();

        $this->actingAs($aliceUser)
            ->post(route('me.payroll.confirm', $payroll), [
                'status' => PayrollPaymentWorkflowService::PAID,
                'employee_id' => $bob->id,
                'paid_at' => now()->toDateTimeString(),
                'confirmation_token' => 'hacked-token',
                'hr_checked' => true,
                'director_approved' => true,
                'employee_confirmed' => true,
            ])
            ->assertRedirect();

        $fresh = $payroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $fresh->status);
        $this->assertNull($fresh->paid_at);
        $this->assertNotEquals($bob->id, $fresh->employee_id);

        $other = Payroll::create([
            'employee_id' => $fresh->employee_id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);

        $this->actingAs($director)
            ->post(route('payroll.approve', $other), [
                'status' => PayrollPaymentWorkflowService::PAID,
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $other->fresh()->status);
        $this->assertNull($other->fresh()->paid_at);
    }

    public function test_second_confirm_is_idempotent_and_second_pay_is_rejected(): void
    {
        ['accountant' => $accountant, 'aliceUser' => $aliceUser, 'alicePayroll' => $payroll] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $payroll))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);

        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);

        $workflow->markPaid($payroll->fresh(), ['payment_method' => 'cash'], $accountant);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $payroll->fresh()->status);

        $this->expectException(\RuntimeException::class);
        $workflow->markPaid($payroll->fresh(), ['payment_method' => 'cash'], $accountant);
    }

    public function test_second_approve_issue_and_sign_do_not_repeat(): void
    {
        ['hr' => $hr, 'director' => $director, 'aliceUser' => $aliceUser, 'alice' => $alice] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);
        $contracts = app(\App\Services\ContractService::class);

        $checked = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 5,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);

        $workflow->reviewByHr($checked, $hr);
        $workflow->approve($checked->fresh(), $director);

        try {
            $workflow->approve($checked->fresh(), $director);
            $this->fail('Duyệt lần hai phải bị chặn.');
        } catch (\RuntimeException) {
        }
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $checked->fresh()->status);

        $workflow->reportIssue($checked->fresh(), 'Sai số', $aliceUser);
        try {
            $workflow->reportIssue($checked->fresh(), 'Sai số lần 2', $aliceUser);
            $this->fail('Báo sự cố lần hai phải bị chặn.');
        } catch (\RuntimeException) {
        }
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $checked->fresh()->status);
        $this->assertSame('Sai số', $checked->fresh()->issue_report);

        $contract = Contract::create([
            'employee_id' => $alice->id,
            'title' => 'HĐ Alice ký',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_DIRECTOR_SIGNED,
            'director_signed_at' => now(),
        ]);

        $contracts->signContract($aliceUser, $contract, 'employee');
        $signedAt = $contract->fresh()->employee_signed_at;
        $this->assertNotNull($signedAt);

        try {
            $contracts->signContract($aliceUser, $contract->fresh(), 'employee');
            $this->fail('Ký hợp đồng lần hai phải bị chặn.');
        } catch (\RuntimeException) {
        }
        $this->assertTrue($signedAt->equalTo($contract->fresh()->employee_signed_at));
    }

    public function test_generate_routes_forbid_non_accountant_and_reject_put_patch_delete(): void
    {
        ['hr' => $hr, 'director' => $gd, 'accountant' => $kt, 'aliceUser' => $nv] = $this->seedPeople();

        foreach ([$hr, $gd, $nv] as $user) {
            $this->actingAs($user)->get(route('accountant.payroll.generate'))->assertForbidden();
            $this->actingAs($user)->post(route('accountant.payroll.generate_post'), ['month' => '2026-08'])->assertForbidden();
            $this->actingAs($user)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();
        }

        $this->actingAs($kt)->get(route('accountant.payroll.generate'))->assertOk();
        $this->actingAs($kt)->put('/accountant/payroll/generate', ['month' => '2026-08'])->assertStatus(405);
        $this->actingAs($kt)->patch('/accountant/payroll/generate', ['month' => '2026-08'])->assertStatus(405);
        $this->actingAs($kt)->delete('/accountant/payroll/generate')->assertStatus(405);
    }

    public function test_recalculate_blocked_for_every_post_calculated_status(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $blocked = [
            1 => PayrollPaymentWorkflowService::HR_CHECKED,
            2 => PayrollPaymentWorkflowService::DIRECTOR_APPROVED,
            3 => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
            4 => PayrollPaymentWorkflowService::PAID,
        ];

        foreach ($blocked as $month => $status) {
            $payroll = Payroll::create([
                'employee_id' => $alice->id,
                'month' => $month,
                'year' => 2026,
                'base_salary' => 10000000,
                'total_salary' => 10000000,
                'status' => $status,
            ]);

            $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $payroll))->assertRedirect();
            $this->assertSame($status, $payroll->fresh()->status);
        }
    }

    public function test_service_confirm_and_report_issue_bind_employee_from_logged_in_user(): void
    {
        ['alicePayroll' => $payroll, 'bobUser' => $bobUser] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        try {
            $workflow->confirm($payroll, $bobUser);
            $this->fail('Xác nhận phiếu người khác phải bị chặn ở service.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('chính mình', $e->getMessage());
        }

        try {
            $workflow->reportIssue($payroll, 'Sai số công', $bobUser);
            $this->fail('Báo sự cố phiếu người khác phải bị chặn ở service.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('chính mình', $e->getMessage());
        }

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);
        $this->assertSame($payroll->employee_id, $payroll->fresh()->employee_id);
    }
}
