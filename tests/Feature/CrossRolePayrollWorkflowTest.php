<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AssertsRemovedPayrollPaymentFeatures;
use Tests\TestCase;

/**
 * Audit xuyên 4 cổng: quyền theo vai trò VÀ theo trạng thái nghiệp vụ.
 * Quy trình kết thúc ở Giám đốc duyệt + thông báo nhân viên.
 */
class CrossRolePayrollWorkflowTest extends TestCase
{
    use AssertsRemovedPayrollPaymentFeatures;
    use RefreshDatabase;

    private function seedPeople(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'M']);

        $aliceUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $bobUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);

        $alice = Employee::create([
            'name' => 'Alice Audit',
            'email' => $aliceUser->email,
            'user_id' => $aliceUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPAUDA',
            'bank_name' => 'MB Bank',
            'account_number' => '111122223333',
            'account_holder' => 'NGUYEN VAN A',
        ]);
        $bob = Employee::create([
            'name' => 'Bob Audit',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPAUDB',
            'bank_name' => 'Vietcombank',
            'account_number' => '999988887777',
            'account_holder' => 'NGUYEN VAN B',
        ]);

        return compact('hr', 'director', 'accountant', 'department', 'aliceUser', 'bobUser', 'alice', 'bob');
    }

    private function aliceSlip(Employee $alice, int $month = 8, int $year = 2026): Payroll
    {
        return Payroll::query()
            ->where('employee_id', $alice->id)
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();
    }

    private function bobSlip(Employee $bob, int $month = 8, int $year = 2026): Payroll
    {
        return Payroll::query()
            ->where('employee_id', $bob->id)
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();
    }

    public function test_full_chain_enforces_role_and_status_on_every_edge(): void
    {
        Mail::fake();
        [
            'hr' => $hr,
            'director' => $gd,
            'accountant' => $kt,
            'aliceUser' => $aliceUser,
            'bobUser' => $bobUser,
            'alice' => $alice,
            'bob' => $bob,
        ] = $this->seedPeople();

        $workflow = app(PayrollPaymentWorkflowService::class);

        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), [
            'month' => '2026-08',
            'total_salary' => 999999999,
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('payrolls', ['month' => 8, 'year' => 2026]);

        $this->actingAs($aliceUser)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();

        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), ['month' => '2026-08'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($hr)->post(route('payroll.period.verify'), ['month' => 8, 'year' => 2026])->assertRedirect();

        $alicePayroll = $this->aliceSlip($alice);
        $bobPayroll = $this->bobSlip($bob);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $alicePayroll->status);
        $this->assertNull($alicePayroll->paid_at);

        $this->actingAs($gd)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($alicePayroll)->assertNotFound();
        $this->actingAs($kt)->postPayrollPaymentConfirm($alicePayroll)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $alicePayroll->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.review', $alicePayroll), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();
        $alicePayroll = $alicePayroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $alicePayroll->status);

        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($alicePayroll)->assertNotFound();
        $this->actingAs($kt)->postPayrollPaymentConfirm($alicePayroll)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $alicePayroll->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.approve', $alicePayroll), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();
        $alicePayroll = $alicePayroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->status);
        $this->assertSame('notified', $alicePayroll->confirmation_status);
        $this->assertNull($alicePayroll->paid_at);
        $this->assertNull($alicePayroll->confirmation_token);

        $this->actingAs($kt)->postPayrollPaymentConfirm($alicePayroll)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->fresh()->status);
        $this->assertDatabaseMissing('salary_payments', ['payroll_id' => $alicePayroll->id]);

        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($bobPayroll)->assertNotFound();
        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($alicePayroll)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->fresh()->status);
        $this->assertFalse($workflow->canConfirm($alicePayroll));
        $this->assertFalse($workflow->canPay($alicePayroll));

        try {
            $workflow->confirm($alicePayroll, $aliceUser);
            $this->fail('confirm() must be removed.');
        } catch (\RuntimeException) {
        }
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->fresh()->status);

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobPayroll->fresh()->status);
        $this->actingAs($bobUser)->postEmployeePayrollConfirm($bobPayroll->fresh())->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobPayroll->fresh()->status);
    }

    public function test_director_cannot_approve_wrong_statuses(): void
    {
        Mail::fake();
        ['director' => $gd, 'alice' => $alice] = $this->seedPeople();

        $calculated = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 5,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::CALCULATED,
        ]);
        $checked = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 4,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);
        $paid = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::PAID,
            'paid_at' => now(),
        ]);

        $this->actingAs($gd)->post(route('payroll.approve', $calculated))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.approve', $checked))->assertRedirect();
        $this->actingAs($gd)->post(route('payroll.approve', $paid))->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $checked->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $paid->fresh()->status);
    }

    public function test_cross_role_idor_uses_business_permission_not_just_auth(): void
    {
        Mail::fake();
        [
            'hr' => $hr,
            'director' => $gd,
            'accountant' => $kt,
            'aliceUser' => $aliceUser,
            'alice' => $alice,
            'bob' => $bob,
        ] = $this->seedPeople();

        $aliceChecked = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 6,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8800000,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);
        $bobLegacyConfirmed = Payroll::create([
            'employee_id' => $bob->id,
            'month' => 6,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 7700000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($bobLegacyConfirmed)->assertNotFound();
        $this->actingAs($aliceUser)
            ->post('/me/payroll/'.$bobLegacyConfirmed->id.'/report-issue', ['issue_report' => 'Không phải phiếu của tôi'])
            ->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $bobLegacyConfirmed->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.review', $aliceChecked))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.approve', $aliceChecked))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $aliceChecked->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.approve', $aliceChecked))->assertForbidden();
        $this->actingAs($hr)->postPayrollPaymentConfirm($bobLegacyConfirmed)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $aliceChecked->fresh()->status);

        $this->actingAs($gd)->postPayrollPaymentConfirm($bobLegacyConfirmed)->assertNotFound();
        $this->actingAs($gd)->post(route('payroll.review', $aliceChecked))->assertForbidden();

        $this->actingAs($gd)->post(route('payroll.approve', $aliceChecked))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $aliceChecked->fresh()->status);

        $this->actingAs($kt)->postPayrollPaymentConfirm($bobLegacyConfirmed)->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $bobLegacyConfirmed->fresh()->status);
    }
}
