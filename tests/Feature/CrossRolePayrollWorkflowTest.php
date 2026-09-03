<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Audit xuyên 4 cổng: quyền theo vai trò VÀ theo trạng thái nghiệp vụ.
 * Không sửa cổng đã khóa trừ khi vòng này phát hiện lỗ hổng thật.
 */
class CrossRolePayrollWorkflowTest extends TestCase
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

    private function pay(User $actor, Payroll $payroll, array $extra = [])
    {
        return $this->actingAs($actor)->post(route('payroll.payment.confirm', $payroll), array_merge([
            'payment_method' => 'bank_transfer',
            'transaction_code' => 'TXN-AUDIT-001',
        ], $extra));
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

        // 1. KT không tính khi kỳ còn mở
        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), [
            'month' => '2026-08',
            'total_salary' => 999999999,
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('payrolls', ['month' => 8, 'year' => 2026]);

        // Chỉ HR chốt kỳ
        $this->actingAs($aliceUser)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();
        $this->assertDatabaseHas('payroll_period_locks', [
            'month' => 8,
            'year' => 2026,
            'is_locked' => true,
            'locked_by' => $hr->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $hr->id,
            'action' => 'payroll_period_locked',
        ]);

        // Chưa HR xác nhận → KT chưa tính được
        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), ['month' => '2026-08'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($hr)->post(route('payroll.period.verify'), ['month' => 8, 'year' => 2026])->assertRedirect();

        // 2. Chỉ KT tính; client không được gửi total_salary / status
        $this->actingAs($aliceUser)->post(route('accountant.payroll.generate_post'), ['month' => '2026-08'])->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();

        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), [
            'month' => '2026-08',
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect()->assertSessionHas('success');

        $alicePayroll = $this->aliceSlip($alice);
        $bobPayroll = $this->bobSlip($bob);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $alicePayroll->status);
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobPayroll->status);
        $this->assertNotEquals(999999999, (float) $alicePayroll->total_salary);
        $this->assertSame($alice->id, (int) $alicePayroll->employee_id);
        $this->assertNull($alicePayroll->paid_at);

        // Phá workflow lúc CALCULATED
        $this->actingAs($gd)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $alicePayroll), ['status' => PayrollPaymentWorkflowService::PAID])->assertRedirect();
        $this->pay($kt, $alicePayroll)->assertRedirect();
        $this->pay($hr, $alicePayroll)->assertForbidden();
        $this->pay($gd, $alicePayroll)->assertForbidden();
        $this->pay($aliceUser, $alicePayroll)->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $alicePayroll->fresh()->status);

        // 3. Chỉ HR kiểm tra; mass-assign bị bỏ
        $this->actingAs($kt)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('payroll.review', $alicePayroll))->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.review', $alicePayroll), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();
        $alicePayroll = $alicePayroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $alicePayroll->status);
        $this->assertNotEquals(999999999, (float) $alicePayroll->total_salary);
        $this->assertSame($alice->id, (int) $alicePayroll->employee_id);

        // Phá workflow lúc HR_CHECKED
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $alicePayroll))->assertRedirect();
        $this->pay($kt, $alicePayroll)->assertRedirect();
        $this->pay($hr, $alicePayroll)->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $alicePayroll->fresh()->status);

        // 4. Chỉ GĐ duyệt; payload giả mạo không được tin
        $this->actingAs($hr)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('payroll.approve', $alicePayroll))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.approve', $alicePayroll), [
            'status' => PayrollPaymentWorkflowService::DIRECTOR_APPROVED,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
        ])->assertRedirect();
        $alicePayroll = $alicePayroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->status);
        $this->assertNotEquals(999999999, (float) $alicePayroll->total_salary);
        $this->assertSame($alice->id, (int) $alicePayroll->employee_id);
        $this->assertNull($alicePayroll->paid_at);

        // KT thanh toán trước NV
        $this->pay($kt, $alicePayroll)->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $alicePayroll->fresh()->status);
        $this->assertDatabaseMissing('salary_payments', ['payroll_id' => $alicePayroll->id]);

        // 5. NV A xác nhận phiếu A; phiếu B (của Bob) → 403. Không nhảy PAID.
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $bobPayroll))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobPayroll->fresh()->status);

        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $alicePayroll), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 1,
            'paid_by' => $aliceUser->id,
        ])->assertRedirect();
        $alicePayroll = $alicePayroll->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $alicePayroll->status);
        $this->assertNull($alicePayroll->paid_at);
        $this->assertNotEquals(1, (float) $alicePayroll->total_salary);

        // 6. Chỉ KT thanh toán; một salary_payment duy nhất
        $this->pay($hr, $alicePayroll)->assertForbidden();
        $this->pay($gd, $alicePayroll)->assertForbidden();
        $this->pay($aliceUser, $alicePayroll)->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $alicePayroll->fresh()->status);

        $this->pay($kt, $alicePayroll, [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => $bob->id,
            'transaction_code' => 'TXN-AUDIT-001',
        ])->assertRedirect(route('payroll.show', $alicePayroll));

        $alicePayroll = $alicePayroll->fresh('salaryPayment');
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $alicePayroll->status);
        $this->assertNotEquals(999999999, (float) $alicePayroll->total_salary);
        $this->assertSame($alice->id, (int) $alicePayroll->employee_id);
        $this->assertSame($kt->id, (int) $alicePayroll->paid_by);
        $this->assertNotNull($alicePayroll->paid_at);
        $this->assertNotNull($alicePayroll->salaryPayment);
        $this->assertSame($alicePayroll->id, (int) $alicePayroll->salaryPayment->payroll_id);
        $this->assertSame($kt->id, (int) $alicePayroll->salaryPayment->paid_by);
        $this->assertNotNull($alicePayroll->salaryPayment->paid_at);
        $this->assertSame('TXN-AUDIT-001', $alicePayroll->salaryPayment->transaction_code);
        $this->assertSame(1, SalaryPayment::where('payroll_id', $alicePayroll->id)->count());

        $this->actingAs($gd)->post(route('payroll.approve', $alicePayroll->fresh()))->assertForbidden();
        $this->pay($kt, $alicePayroll->fresh())->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $alicePayroll->fresh()->status);
        $this->assertSame(1, SalaryPayment::where('payroll_id', $alicePayroll->id)->count());

        // Bob vẫn CALCULATED — không bị kéo theo phiếu Alice
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $bobPayroll->fresh()->status);
        $this->actingAs($bobUser)->post(route('me.payroll.confirm', $bobPayroll->fresh()))->assertRedirect();
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
        $issue = Payroll::create([
            'employee_id' => $alice->id,
            'month' => 4,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::PAYROLL_ISSUE,
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
        $this->actingAs($gd)->post(route('payroll.approve', $issue))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.approve', $paid))->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $issue->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $paid->fresh()->status);
    }

    public function test_issue_loop_must_reenter_hr_and_director_before_pay(): void
    {
        Mail::fake();
        [
            'hr' => $hr,
            'director' => $gd,
            'accountant' => $kt,
            'aliceUser' => $aliceUser,
            'alice' => $alice,
        ] = $this->seedPeople();

        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 7, 'year' => 2026])->assertRedirect();
        $this->actingAs($hr)->post(route('payroll.period.verify'), ['month' => 7, 'year' => 2026])->assertRedirect();
        $this->actingAs($kt)->post(route('accountant.payroll.generate_post'), ['month' => '2026-07'])->assertRedirect();

        $payroll = $this->aliceSlip($alice, 7);
        $this->actingAs($hr)->post(route('payroll.review', $payroll))->assertRedirect();
        $this->actingAs($gd)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);

        $this->actingAs($aliceUser)->post(route('me.payroll.report_issue', $payroll->fresh()), [
            'issue_report' => 'Sai ngày công trên phiếu tháng 7',
        ])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $payroll->fresh()->status);

        // Không có đường tắt: sự cố → duyệt / thanh toán
        $this->actingAs($gd)->post(route('payroll.approve', $payroll->fresh()))->assertForbidden();
        $this->pay($kt, $payroll->fresh())->assertRedirect();
        $this->pay($hr, $payroll->fresh())->assertForbidden();
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAYROLL_ISSUE, $payroll->fresh()->status);

        // KT tính lại từ nguồn → CALCULATED, vẫn không được PAID
        $this->actingAs($kt)->post(route('accountant.payroll.recalculate', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->pay($kt, $payroll->fresh())->assertRedirect();
        $this->actingAs($gd)->post(route('payroll.approve', $payroll->fresh()))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $payroll->fresh()->status);
        $this->assertDatabaseMissing('salary_payments', ['payroll_id' => $payroll->id]);

        // Phải đi lại: HR kiểm tra → GĐ duyệt → NV xác nhận → KT thanh toán
        $this->actingAs($hr)->post(route('payroll.review', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $payroll->fresh()->status);
        $this->pay($kt, $payroll->fresh())->assertRedirect();

        $this->actingAs($gd)->post(route('payroll.approve', $payroll->fresh()))->assertRedirect();
        $this->pay($kt, $payroll->fresh())->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $payroll->fresh()->status);

        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $payroll->fresh()))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payroll->fresh()->status);

        $this->pay($kt, $payroll->fresh(), ['transaction_code' => 'TXN-ISSUE-007'])->assertRedirect();
        $fresh = $payroll->fresh('salaryPayment');
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $fresh->status);
        $this->assertSame(1, SalaryPayment::where('payroll_id', $fresh->id)->count());
        $this->assertSame('TXN-ISSUE-007', $fresh->salaryPayment->transaction_code);
        $this->assertSame($kt->id, (int) $fresh->salaryPayment->paid_by);
        $this->assertNotNull($fresh->salaryPayment->paid_at);
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
        $bobConfirmed = Payroll::create([
            'employee_id' => $bob->id,
            'month' => 6,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 7700000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);

        // NV A không thao tác phiếu B
        $this->actingAs($aliceUser)->post(route('me.payroll.confirm', $bobConfirmed))->assertForbidden();
        $this->actingAs($aliceUser)->post(route('me.payroll.report_issue', $bobConfirmed), [
            'issue_report' => 'Không phải phiếu của tôi',
        ])->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $bobConfirmed->fresh()->status);

        // KT không chiếm bước HR / GĐ trên cùng ID
        $this->actingAs($kt)->post(route('payroll.review', $aliceChecked))->assertForbidden();
        $this->actingAs($kt)->post(route('payroll.approve', $aliceChecked))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $aliceChecked->fresh()->status);

        // HR không chiếm bước GĐ / KT
        $this->actingAs($hr)->post(route('payroll.approve', $aliceChecked))->assertForbidden();
        $this->pay($hr, $bobConfirmed)->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::HR_CHECKED, $aliceChecked->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $bobConfirmed->fresh()->status);

        // GĐ không chiếm bước KT
        $this->pay($gd, $bobConfirmed)->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.review', $aliceChecked))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $bobConfirmed->fresh()->status);

        // Đúng role đúng trạng thái vẫn chạy trên cùng ID
        $this->actingAs($gd)->post(route('payroll.approve', $aliceChecked))->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $aliceChecked->fresh()->status);
        $this->pay($kt, $bobConfirmed, ['transaction_code' => 'TXN-IDOR-BOB'])->assertRedirect();
        $this->assertSame(PayrollPaymentWorkflowService::PAID, $bobConfirmed->fresh()->status);
    }
}
