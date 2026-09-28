<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AssertsRemovedPayrollPaymentFeatures;
use Tests\TestCase;

class AccountantPortalGuardTest extends TestCase
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
            'name' => 'Alice KT',
            'email' => $aliceUser->email,
            'user_id' => $aliceUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPKTA',
            'bank_name' => 'MB Bank',
            'account_number' => '111122223333',
            'account_holder' => 'NGUYEN VAN A',
        ]);
        $bob = Employee::create([
            'name' => 'Bob KT',
            'email' => $bobUser->email,
            'user_id' => $bobUser->id,
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPKTB',
        ]);

        return compact('hr', 'director', 'accountant', 'department', 'aliceUser', 'bobUser', 'alice', 'bob');
    }

    private function lockPeriod(User $hr, int $month = 8, int $year = 2026): void
    {
        $this->actingAs($hr)->post(route('payroll.period.lock'), [
            'month' => $month,
            'year' => $year,
        ])->assertRedirect();

        $this->actingAs($hr)->post(route('payroll.period.verify'), [
            'month' => $month,
            'year' => $year,
        ])->assertRedirect();
    }

    private function payroll(Employee $employee, string $status, array $extra = []): Payroll
    {
        return Payroll::create(array_merge([
            'employee_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => $status,
        ], $extra));
    }

    public function test_accountant_lands_on_accountant_portal_not_hr_or_director(): void
    {
        ['accountant' => $accountant] = $this->seedPeople();

        $this->actingAs($accountant)->get(route('accountant.dashboard'))->assertOk();
        $this->actingAs($accountant)->get(route('dashboard'))->assertRedirect(route('accountant.dashboard'));
        $this->actingAs($accountant)->get(route('hr-dashboard.index'))->assertForbidden();
    }

    public function test_accountant_cannot_review_approve_leave_attendance_or_sign(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'alice' => $alice, 'department' => $department] = $this->seedPeople();
        $payroll = $this->payroll($alice, PayrollPaymentWorkflowService::CALCULATED);

        $leave = LeaveRequest::create([
            'employee_id' => $alice->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'days' => 1,
            'type' => 'annual',
            'reason' => 'Family',
            'status' => 'pending',
        ]);
        $row = Attendance::create([
            'employee_id' => $alice->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '08:32:00',
        ]);
        $contract = Contract::create([
            'employee_id' => $alice->id,
            'title' => 'HĐ Alice',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
        ]);

        $this->actingAs($hr)->post(route('payroll.review', $payroll))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.approve', $payroll))->assertForbidden();
        $this->actingAs($kt)->post(route('leave_requests.approve', $leave))->assertForbidden();
        $this->actingAs($kt)->put(route('attendance.update', $row), [
            'employee_id' => $alice->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '08:00:00',
        ])->assertForbidden();
        $this->actingAs($kt)->post(route('contracts.sign', $contract))->assertForbidden();
        $this->actingAs($kt)->put(route('employees.update', $alice), [
            'name' => 'Hacked',
            'email' => $alice->email,
            'department_id' => $department->id,
            'status' => 'inactive',
        ])->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('08:32:00', $row->fresh()->getRawOriginal('check_in'));
        $this->assertSame(Contract::STATUS_WAITING_DIRECTOR_SIGNATURE, $contract->fresh()->status);
        $this->assertSame('Alice KT', $alice->fresh()->name);
        $this->assertSame('active', $alice->fresh()->status);
    }

    public function test_calculate_ignores_client_status_total_and_foreign_employee(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();
        $this->lockPeriod($hr);

        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), [
            'month' => '2026-08',
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();

        $aliceSlip = Payroll::where('employee_id', $alice->id)->where('month', 8)->where('year', 2026)->first();
        $this->assertNotNull($aliceSlip);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $aliceSlip->status);
        $this->assertNotEquals(999999999, (float) $aliceSlip->total_salary);
        $this->assertSame($alice->id, (int) $aliceSlip->employee_id);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $kt->id,
            'action' => 'payroll_calculated',
        ]);
    }

    public function test_recalculate_allowed_only_for_draft_and_calculated(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();
        $this->lockPeriod($hr);

        // HR xác nhận đã tự tính → dùng phiếu sẵn có (không tạo trùng unique employee/month/year).
        $aliceSlip = Payroll::query()->where('employee_id', $alice->id)->where('month', 8)->where('year', 2026)->firstOrFail();
        $bobSlip = Payroll::query()->where('employee_id', $bob->id)->where('month', 8)->where('year', 2026)->firstOrFail();

        $aliceSlip->update(['status' => PayrollPaymentWorkflowService::DRAFT, 'total_salary' => 1]);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $aliceSlip))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $aliceSlip->fresh()->status);
        $this->assertNotEquals(1, (float) $aliceSlip->fresh()->total_salary);

        $bobSlip->update(['total_salary' => 1]);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $bobSlip))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobSlip->fresh()->status);

        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 7, 'year' => 2026])->assertRedirect();
        app(\App\Services\PayrollPeriodLockService::class)->markHrVerified(7, 2026, $hr);

        $checked = Payroll::create([
            'employee_id' => $bob->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 12345,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $checked))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $checked->fresh()->status);
        $this->assertEquals(12345, (float) $checked->fresh()->total_salary);
    }

    public function test_generate_does_not_rewrite_hr_checked_or_later_slips(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();
        $this->lockPeriod($hr);

        $checked = Payroll::query()->where('employee_id', $alice->id)->where('month', 8)->where('year', 2026)->firstOrFail();
        $approved = Payroll::query()->where('employee_id', $bob->id)->where('month', 8)->where('year', 2026)->firstOrFail();
        $checked->update(['status' => PayrollPaymentWorkflowService::HR_CHECKED, 'total_salary' => 12345]);
        $approved->update(['status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED, 'total_salary' => 54321]);

        $this->actingAs($kt)->post(route('payroll.generate'), [
            'month' => 8,
            'year' => 2026,
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $checked->fresh()->status);
        $this->assertEquals(12345, (float) $checked->fresh()->total_salary);
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $approved->fresh()->status);
        $this->assertEquals(54321, (float) $approved->fresh()->total_salary);
    }

    public function test_accountant_cannot_pay_after_director_approval(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $workflow = app(PayrollPaymentWorkflowService::class);

        $approved = $this->payroll($alice, PayrollPaymentWorkflowService::DIRECTOR_APPROVED);
        $legacyConfirmed = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8800000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        $this->assertNamedRouteRemoved('payroll.payment.confirm', $approved);
        $this->actingAs($kt)->postPayrollPaymentConfirm($approved, [
            'payment_method' => 'cash',
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
        ])->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $approved->fresh()->status);
        $this->assertNull($approved->fresh()->paid_at);

        $this->actingAs($kt)->postPayrollPaymentConfirm($legacyConfirmed, ['payment_method' => 'cash'])->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $legacyConfirmed->fresh()->status);

        $this->expectException(\RuntimeException::class);
        $workflow->markPaid($approved->fresh(), ['payment_method' => 'cash'], $kt);
    }

    public function test_duplicate_salary_payment_is_rejected_by_unique_payroll_id(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $payroll = $this->payroll($alice, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED);

        SalaryPayment::create([
            'employee_id' => $alice->id,
            'payroll_id' => $payroll->id,
            'code' => 'PAY-A',
            'month' => 8,
            'year' => 2026,
            'total' => 10000000,
            'net' => 10000000,
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);
        SalaryPayment::create([
            'employee_id' => $alice->id,
            'payroll_id' => $payroll->id,
            'code' => 'PAY-B',
            'month' => 8,
            'year' => 2026,
            'total' => 10000000,
            'net' => 10000000,
            'status' => 'pending',
        ]);
    }

    public function test_approve_keeps_status_when_email_cannot_send(): void
    {
        ['director' => $director, 'accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $alice->update(['email' => 'not-an-email']);
        $payroll = $this->payroll($alice, PayrollPaymentWorkflowService::CALCULATED);

        $this->actingAs($kt)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();

        $fresh = $payroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $fresh->status);
        $this->assertSame('failed', $fresh->email_status);
    }

    public function test_accountant_cannot_edit_employee_bank_on_payment(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $payable = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 6,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8000000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        $this->actingAs($kt)->post('/payroll/'.$payable->id.'/fix-issue', [
            'base_salary' => 1,
            'working_salary' => 1,
        ])->assertNotFound();
        $this->assertNamedRouteRemoved('payroll.payment.bank', $payable);
        $this->actingAs($kt)->post('/payroll/'.$payable->id.'/payment/bank', [
            'bank_name' => 'Hack Bank',
            'account_number' => '000000000001',
            'account_holder' => 'HACKER',
        ])->assertNotFound();

        $this->assertSame('MB Bank', $alice->fresh()->bank_name);
    }

    public function test_payroll_payment_screen_route_is_removed(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $confirmed = $this->payroll($alice, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED);

        $this->assertNamedRouteRemoved('payroll.payment.show', $confirmed);
        $this->actingAs($kt)->getPayrollPaymentScreen($confirmed)->assertNotFound();
    }

    public function test_accountant_bulk_submit_period_ends_at_director_approval(): void
    {
        Mail::fake();
        ['hr' => $hr, 'director' => $director, 'accountant' => $kt, 'alice' => $alice, 'bob' => $bob, 'aliceUser' => $aliceUser, 'bobUser' => $bobUser] = $this->seedPeople();

        $aliceSlip = $this->payroll($alice, PayrollPaymentWorkflowService::CALCULATED);
        $bobSlip = $this->payroll($bob, PayrollPaymentWorkflowService::CALCULATED);

        $this->actingAs($kt)
            ->get(route('accountant.payroll.index', ['month' => 8, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Gửi duyệt tất cả');

        $this->actingAs($hr)->post(route('accountant.payroll.submit_all'), [
            'month' => 8,
            'year' => 2026,
        ])->assertForbidden();

        $this->actingAs($kt)->post(route('accountant.payroll.submit_all'), [
            'month' => 8,
            'year' => 2026,
        ])->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $aliceSlip->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $bobSlip->fresh()->status);

        $this->actingAs($director)->post(route('payroll.approve_all'), [
            'month' => 8,
            'year' => 2026,
        ])->assertRedirect();

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $aliceSlip->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $bobSlip->fresh()->status);
        $this->assertSame('notified', $aliceSlip->fresh()->confirmation_status);
        $this->assertSame('notified', $bobSlip->fresh()->confirmation_status);

        $this->assertNamedRouteRemoved('me.payroll.confirm', $aliceSlip);
        $this->actingAs($aliceUser)->postEmployeePayrollConfirm($aliceSlip->fresh())->assertNotFound();
        $this->actingAs($bobUser)->postEmployeePayrollConfirm($bobSlip->fresh())->assertNotFound();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $aliceSlip->fresh()->status);

        $this->assertNamedRouteRemoved('accountant.payroll.pay_all');
        $payAllResponse = $this->actingAs($kt)->post('/accountant/payroll/pay-all', [
            'month' => 8,
            'year' => 2026,
            'payment_method' => 'cash',
        ]);
        $this->assertTrue(in_array($payAllResponse->status(), [404, 405], true));

        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $aliceSlip->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $bobSlip->fresh()->status);
        $this->assertFalse(app(PayrollPaymentWorkflowService::class)->canPay($aliceSlip->fresh()));
    }
}
