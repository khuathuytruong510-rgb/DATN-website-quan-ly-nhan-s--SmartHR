<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollPaymentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorPortalGuardTest extends TestCase
{
    use RefreshDatabase;

    private function seedPeople(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $accountant = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => true, 'is_director' => false]);
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nguyen Van GD',
            'email' => 'nvgd@example.com',
            'position' => 'Developer',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPGD',
        ]);

        return compact('hr', 'director', 'accountant', 'department', 'employee');
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

    public function test_director_lands_on_director_dashboard_not_hr_accountant_or_employee(): void
    {
        ['director' => $gd] = $this->seedPeople();

        $this->actingAs($gd)->get(route('dashboard'))->assertOk()->assertSee('Dashboard Giám đốc');
        $this->actingAs($gd)->get(route('accountant.dashboard'))->assertForbidden();
        $this->actingAs($gd)->get(route('me.dashboard'))->assertRedirect(route('dashboard'));
        $this->actingAs($gd)->get(route('payroll.index'))->assertOk()->assertSee('Bảng lương nhân viên');
        $this->actingAs($gd)->get(route('employees.index'))->assertOk();
        $this->actingAs($gd)->get(route('contracts.index'))->assertOk();
        $this->actingAs($gd)->get(route('hr-dashboard.index'))->assertOk();
    }

    public function test_director_cannot_open_hr_or_accountant_write_forms(): void
    {
        ['director' => $gd, 'employee' => $employee] = $this->seedPeople();
        $payable = $this->payroll($employee, PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED);

        $this->actingAs($gd)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($gd)->get(route('employees.edit', $employee))->assertForbidden();
        $this->actingAs($gd)->get(route('contracts.create'))->assertForbidden();
        $this->actingAs($gd)->get(route('attendance.create'))->assertForbidden();
        $this->actingAs($gd)->get(route('leave_requests.create'))->assertForbidden();
        $this->actingAs($gd)->get(route('payroll.payment.show', $payable))->assertForbidden();
        $this->actingAs($gd)->get(route('payroll.issues.fix_form', $payable))->assertForbidden();
    }

    public function test_director_cannot_generate_review_pay_or_lock_period(): void
    {
        ['director' => $gd, 'hr' => $hr, 'employee' => $employee] = $this->seedPeople();
        $calculated = $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED);
        $payable = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 10000000,
            'status' => PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED,
        ]);
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'days' => 1,
            'type' => 'annual',
            'reason' => 'Family',
            'status' => 'pending',
        ]);
        $row = Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '08:00:00',
        ]);

        $this->actingAs($gd)->post(route('payroll.generate'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.review', $calculated))->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.payment.confirm', $payable), [
            'payment_method' => 'cash',
        ])->assertForbidden();
        $this->actingAs($gd)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertForbidden();
        $this->actingAs($hr)->post(route('payroll.period.lock'), ['month' => 8, 'year' => 2026])->assertRedirect();
        $this->actingAs($gd)->post(route('payroll.period.unlock'), [
            'month' => 8,
            'year' => 2026,
            'unlock_reason' => 'Giám đốc không được mở khóa kỳ lương',
        ])->assertForbidden();
        $this->actingAs($gd)->post(route('leave_requests.approve', $leave))->assertForbidden();
        $this->actingAs($gd)->put(route('attendance.update', $row), [
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'status' => 'present',
            'check_in' => '07:00',
        ])->assertForbidden();
        $this->actingAs($gd)->put(route('employees.update', $employee), [
            'name' => 'Hacked',
            'email' => $employee->email,
            'department_id' => $employee->department_id,
            'status' => 'active',
        ])->assertForbidden();

        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);
        $this->assertSame(PayrollPaymentWorkflowService::EMPLOYEE_CONFIRMED, $payable->fresh()->status);
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('Nguyen Van GD', $employee->fresh()->name);
    }

    public function test_director_approves_only_hr_checked_and_ignores_client_status(): void
    {
        ['director' => $gd, 'employee' => $employee] = $this->seedPeople();
        $calculated = $this->payroll($employee, PayrollPaymentWorkflowService::CALCULATED);
        $checked = Payroll::create([
            'employee_id' => $employee->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 10000000,
            'total_salary' => 8800000,
            'status' => PayrollPaymentWorkflowService::HR_CHECKED,
        ]);

        $this->actingAs($gd)->post(route('payroll.approve', $calculated))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::CALCULATED, $calculated->fresh()->status);

        $this->actingAs($gd)->post(route('payroll.approve', $checked), [
            'status' => PayrollPaymentWorkflowService::PAID,
            'total_salary' => 999999999,
            'employee_id' => 999,
        ])->assertRedirect();

        $fresh = $checked->fresh();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $fresh->status);
        $this->assertEquals(8800000, (float) $fresh->total_salary);
        $this->assertSame($employee->id, (int) $fresh->employee_id);
        $this->assertNull($fresh->paid_at);

        $this->actingAs($gd)->post(route('payroll.approve', $fresh))->assertForbidden();
        $this->assertSame(PayrollPaymentWorkflowService::DIRECTOR_APPROVED, $checked->fresh()->status);
    }

    public function test_director_signs_contract_only_after_employee_and_cannot_sign_twice(): void
    {
        ['director' => $gd, 'employee' => $employee] = $this->seedPeople();
        $waitingEmployee = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chờ NV',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]);
        $waitingDirector = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chờ GĐ',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_WAITING_DIRECTOR_SIGNATURE,
            'employee_signed_at' => now(),
        ]);

        $this->actingAs($gd)->post(route('contracts.sign', $waitingEmployee), ['party' => 'director'])->assertForbidden();
        $this->assertNull($waitingEmployee->fresh()->director_signed_at);

        $this->actingAs($gd)->post(route('contracts.sign', $waitingDirector), ['party' => 'director'])->assertRedirect();
        $this->assertNotNull($waitingDirector->fresh()->director_signed_at);
        $this->assertSame('active', $waitingDirector->fresh()->status);

        $this->actingAs($gd)->post(route('contracts.sign', $waitingDirector->fresh()), ['party' => 'director'])->assertForbidden();
    }

    public function test_director_view_pages_do_not_offer_hr_write_actions(): void
    {
        ['director' => $gd, 'employee' => $employee] = $this->seedPeople();
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'days' => 1,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->payroll($employee, PayrollPaymentWorkflowService::HR_CHECKED);

        $this->actingAs($gd);
        $this->get(route('leave_requests.index'))->assertOk()->assertDontSee('+ Tạo Đơn Xin Nghỉ', false);
        $this->get(route('employees.index'))->assertOk()->assertDontSee('+ Tạo nhân viên', false);
        $this->get(route('payroll.index'))->assertOk()->assertSee('Phê duyệt cuối')->assertDontSee('Chốt dữ liệu kỳ');
        $this->get(route('payroll.issues.index'))->assertOk()->assertDontSee('Khắc phục');
    }
}
