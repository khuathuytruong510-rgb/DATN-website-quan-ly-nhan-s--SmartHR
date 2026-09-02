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
use Tests\TestCase;

class AccountantPortalGuardTest extends TestCase
{
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
        ['accountant' => $kt, 'alice' => $alice, 'department' => $department] = $this->seedPeople();
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

        $this->actingAs($kt)->post(route('payroll.review', $payroll))->assertForbidden();
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

    public function test_recalculate_allowed_only_for_draft_calculated_and_issue(): void
    {
        ['hr' => $hr, 'accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();
        $this->lockPeriod($hr);

        $draft = $this->payroll($alice, PayrollPaymentWorkflowService::DRAFT, [
            'month' => 8,
            'total_salary' => 1,
        ]);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $draft))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $draft->fresh()->status);
        $this->assertNotEquals(1, (float) $draft->fresh()->total_salary);

        $calculated = $this->payroll($bob, PayrollPaymentWorkflowService::CALCULATED, [
            'month' => 8,
            'year' => 2026,
            'total_salary' => 1,
        ]);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $calculated))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);

        $issue = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 1,
            'status' => PayrollPaymentWorkflowService::PAYROLL_ISSUE,
        ]);
        $this->lockPeriod($hr, 7, 2026);
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $issue))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $issue->fresh()->status);

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

        $checked = $this->payroll($alice, PayrollPaymentWorkflowService::HR_CHECKED, ['total_salary' => 12345]);
        $approved = $this->payroll($bob, PayrollPaymentWorkflowService::DIRECTOR_APPROVED, ['total_salary' => 54321]);

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

    public function test_pay_only_when_employee_confirmed_and_ignores_mass_assignment(): void
    {
        ['accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();

        $tooEarly = $this->payroll($alice, PayrollPaymentWorkflowService::DIRECTOR_APPROVED);
        $this->actingAs($kt)->post(route('payroll.payment.confirm', $tooEarly), [
            'payment_method' => 'cash',
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
        ])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $tooEarly->fresh()->status);
        $this->assertNull($tooEarly->fresh()->paid_at);

        $payable = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8800000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
            'payout_bank_name' => 'MB Bank',
            'payout_account_number' => '111122223333',
            'payout_account_holder' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($kt)->post(route('payroll.payment.confirm', $payable), [
            'payment_method' => 'cash',
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
            'transaction_code' => 'HACKEDREF999',
        ])->assertRedirect(route('payroll.show', $payable));

        $fresh = $payable->fresh('salaryPayment');
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $fresh->status);
        $this->assertEquals(8800000, (float) $fresh->total_salary);
        $this->assertSame($alice->id, (int) $fresh->employee_id);
        $this->assertSame($kt->id, (int) $fresh->paid_by);
        $this->assertNotNull($fresh->paid_at);
        $this->assertNotNull($fresh->salaryPayment);
        $this->assertSame($kt->id, (int) $fresh->salaryPayment->paid_by);
        $this->assertNotNull($fresh->salaryPayment->paid_at);
        $this->assertNull($fresh->salaryPayment->transaction_code);

        $this->actingAs($kt)->post(route('payroll.payment.confirm', $fresh), [
            'payment_method' => 'cash',
        ])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $fresh->fresh()->status);
        $this->assertSame(1, SalaryPayment::where('payroll_id', $payable->id)->count());
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
        ['hr' => $hr, 'director' => $director, 'alice' => $alice] = $this->seedPeople();
        $alice->update(['email' => 'not-an-email']);
        $payroll = $this->payroll($alice, PayrollPaymentWorkflowService::CALCULATED);

        $this->actingAs($hr)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($director)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();

        $fresh = $payroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $fresh->status);
        $this->assertSame('failed', $fresh->email_status);
    }

    public function test_accountant_cannot_fix_issue_amounts_or_edit_employee_bank(): void
    {
        ['accountant' => $kt, 'alice' => $alice] = $this->seedPeople();
        $issue = $this->payroll($alice, PayrollPaymentWorkflowService::PAYROLL_ISSUE, [
            'issue_report' => 'Sai ngày công',
        ]);
        $payable = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 6,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8000000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        $this->actingAs($kt)->get(route('payroll.issues.fix_form', $issue))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.issues.fix', $issue), [
            'base_salary' => 1,
            'working_salary' => 1,
        ])->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.payment.bank', $payable), [
            'bank_name' => 'Hack Bank',
            'account_number' => '000000000001',
            'account_holder' => 'HACKER',
        ])->assertForbidden();

        $this->assertSame('MB Bank', $alice->fresh()->bank_name);
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $issue->fresh()->status);
    }

    public function test_payment_screen_lists_only_employee_confirmed(): void
    {
        ['accountant' => $kt, 'alice' => $alice, 'bob' => $bob] = $this->seedPeople();
        $this->payroll($alice, PayrollPaymentWorkflowService::CALCULATED);
        $this->payroll($bob, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED);

        $this->actingAs($kt)
            ->get(route('payroll.index', ['month' => 8, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Thanh toán lương')
            ->assertSee('Bob KT')
            ->assertDontSee('Alice KT');
    }
}
