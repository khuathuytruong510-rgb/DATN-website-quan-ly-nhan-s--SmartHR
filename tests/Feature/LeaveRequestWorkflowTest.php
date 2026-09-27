<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $user = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'IT', 'code' => 'IT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nam',
            'email' => $user->email,
            'user_id' => $user->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'IT01',
            'leave_balance' => 12,
            'gender' => 'male',
        ]);
        $contract = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chính thức',
            'contract_type' => 'fixed_term',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_ACTIVE,
            'allowed_unpaid_leave_days_per_month' => 1,
            'allowed_maternity_leave_days' => 180,
        ]);

        return compact('hr', 'user', 'employee', 'contract');
    }

    public function test_male_form_hides_maternity_leave_type(): void
    {
        ['user' => $user] = $this->people();

        $this->actingAs($user)
            ->get(route('me.leave_requests.create'))
            ->assertOk()
            ->assertDontSee('value="maternity"')
            ->assertSee('Nghỉ thai sản (vợ sinh con)')
            ->assertSee('Nghỉ phép năm');
    }

    public function test_create_form_asks_type_before_dates_and_shows_legal_quota(): void
    {
        ['user' => $user] = $this->people();

        $html = $this->actingAs($user)
            ->get(route('me.leave_requests.create'))
            ->assertOk()
            ->getContent();

        $typePos = strpos($html, 'Loại nghỉ phép');
        $datePos = strpos($html, 'Ngày bắt đầu');
        $this->assertNotFalse($typePos);
        $this->assertNotFalse($datePos);
        $this->assertLessThan($datePos, $typePos);
        $this->assertStringContainsString('leave-quota-card', $html);
        $this->assertStringContainsString('113', $html);
    }

    public function test_spouse_birth_leave_days_follow_birth_rules_and_working_calendar(): void
    {
        $eligibility = app(\App\Services\LeaveEligibilityService::class);

        $this->assertSame(['days' => 5, 'end_date' => '2026-09-08'], $eligibility->spouseBirthSchedule('2026-09-01', 1));
        $this->assertSame(['days' => 5, 'end_date' => '2026-09-12'], $eligibility->spouseBirthSchedule('2026-09-08', 1));
        $this->assertSame(['days' => 7, 'end_date' => '2026-09-15'], $eligibility->spouseBirthSchedule('2026-09-08', 1, true));
        $this->assertSame(['days' => 10, 'end_date' => '2026-09-18'], $eligibility->spouseBirthSchedule('2026-09-08', 2));
        $this->assertSame(['days' => 14, 'end_date' => '2026-09-23'], $eligibility->spouseBirthSchedule('2026-09-08', 2, true));
        $this->assertSame(['days' => 13, 'end_date' => '2026-09-22'], $eligibility->spouseBirthSchedule('2026-09-08', 3));
        $this->assertSame(['days' => 17, 'end_date' => '2026-09-26'], $eligibility->spouseBirthSchedule('2026-09-08', 3, true));
    }

    public function test_spouse_birth_schedule_uses_company_holidays_from_payroll_calendar(): void
    {
        \Illuminate\Support\Facades\DB::table('holidays')->insert([
            'date' => '2026-09-03',
            'name' => 'Ngày nghỉ công ty',
            'type' => 'company',
            'is_paid' => true,
            'work_rate' => 1,
            'source' => 'company',
            'is_substitute' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eligibility = app(\App\Services\LeaveEligibilityService::class);
        $this->assertSame(['days' => 5, 'end_date' => '2026-09-09'], $eligibility->spouseBirthSchedule('2026-09-01', 1));
        $this->assertContains('2026-09-03', $eligibility->spouseBirthCalendarOptions(2026, 2026)['holidayDates']);
    }

    public function test_spouse_birth_submission_recomputes_end_date_and_stores_private_proof(): void
    {
        Storage::fake('local');
        ['user' => $user, 'employee' => $employee] = $this->people();

        $leave = app(\App\Services\LeaveRequestService::class)->submit($employee, $user, [
            'type' => \App\Support\LeaveTypes::SPOUSE_BIRTH,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-09',
            'children_count' => 2,
            'birth_complication' => true,
            'document' => UploadedFile::fake()->create('giay-khai-sinh.pdf', 64, 'application/pdf'),
        ]);

        $this->assertSame('2026-09-23', $leave->end_date->toDateString());
        $this->assertSame(14.0, $leave->days);
        $this->assertSame('Vợ sinh con', $leave->reason);
        $this->assertSame(2, $leave->children_count);
        $this->assertTrue($leave->birth_complication);
        $this->assertSame('giay-khai-sinh.pdf', $leave->document_name);
        Storage::disk('local')->assertExists($leave->document_path);
    }

    public function test_employee_spouse_birth_form_uses_server_end_date_and_protects_proof_download(): void
    {
        Storage::fake('local');
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'type' => \App\Support\LeaveTypes::SPOUSE_BIRTH,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-09',
            'children_count' => 2,
            'birth_complication' => 1,
            'document' => UploadedFile::fake()->create('giay-khai-sinh.pdf', 64, 'application/pdf'),
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-09-23', $leave->end_date->toDateString());
        $this->assertSame(14.0, $leave->days);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'children_count' => 2,
            'birth_complication' => 1,
            'reason' => 'Vợ sinh con',
        ]);

        $this->actingAs($hr)->get(route('leave_requests.document', $leave))
            ->assertOk()
            ->assertDownload('giay-khai-sinh.pdf');
        $this->actingAs($user)->get(route('me.leave_requests.document', $leave))
            ->assertOk()
            ->assertDownload('giay-khai-sinh.pdf');

        $otherUser = User::factory()->create();
        Employee::create([
            'name' => 'Other Employee',
            'email' => $otherUser->email,
            'user_id' => $otherUser->id,
            'position' => 'Dev',
            'department_id' => $employee->department_id,
            'status' => 'active',
            'employee_code' => 'IT02',
        ]);
        $this->actingAs($otherUser)->get(route('me.leave_requests.document', $leave))->assertForbidden();
    }

    public function test_hr_can_create_spouse_birth_leave_with_server_computed_dates(): void
    {
        Storage::fake('local');
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->get(route('leave_requests.create'))
            ->assertOk()
            ->assertSee('children_count')
            ->assertSee('document');

        $this->actingAs($hr)->post(route('leave_requests.store'), [
            'employee_id' => $employee->id,
            'type' => \App\Support\LeaveTypes::SPOUSE_BIRTH,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-09',
            'children_count' => 1,
            'reason' => 'Vợ sinh con',
        ])->assertRedirect(route('leave_requests.index'));

        $leave = LeaveRequest::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-09-12', $leave->end_date->toDateString());
        $this->assertSame(5.0, $leave->days);
    }

    public function test_approved_spouse_birth_attendance_skips_public_holidays_and_weekly_rest_days(): void
    {
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();
        $leave = app(\App\Services\LeaveRequestService::class)->submit($employee, $user, [
            'type' => \App\Support\LeaveTypes::SPOUSE_BIRTH,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'children_count' => 1,
            'birth_complication' => false,
        ]);

        $this->assertSame('2026-09-08', $leave->end_date->toDateString());
        app(\App\Services\LeaveRequestService::class)->approve($leave, $hr);

        $attendance = \App\Models\Attendance::where('notes', 'leave:'.$leave->id);
        $this->assertSame(5, $attendance->count());
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-09-01',
            'notes' => 'leave:'.$leave->id,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-09-06',
            'notes' => 'leave:'.$leave->id,
        ]);
    }

    public function test_annual_entitlement_uses_labor_law_seniority_minimum(): void
    {
        ['employee' => $employee] = $this->people();
        $employee->update([
            'start_date' => now()->subYears(6)->toDateString(),
            'leave_balance' => 12,
        ]);

        $quota = app(\App\Services\LeaveEligibilityService::class)->quotaSummary($employee);

        $this->assertSame(13, $quota['annual_legal']);
        $this->assertSame(13, $quota['annual_max']);
    }

    public function test_annual_entitlement_is_proportional_under_one_year(): void
    {
        ['employee' => $employee] = $this->people();
        $employee->update([
            'start_date' => now()->subMonths(4)->toDateString(),
            'leave_balance' => 0,
        ]);

        $quota = app(\App\Services\LeaveEligibilityService::class)->quotaSummary($employee);

        $this->assertSame(4, $quota['annual_legal']);
        $this->assertSame(4, $quota['annual_max']);
    }

    public function test_female_form_shows_maternity_as_default(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->update(['gender' => 'female']);

        $html = $this->actingAs($user)->get(route('me.leave_requests.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Nghỉ thai sản', $html);
        $this->assertMatchesRegularExpression('/<option value="maternity"\s+selected>/', $html);
    }

    public function test_male_cannot_submit_maternity_leave(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Không hợp lệ',
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_female_can_submit_maternity_leave(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->update(['gender' => 'female']);

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Thai sản',
        ])->assertRedirect(route('me.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'type' => 'maternity',
            'status' => 'pending',
        ]);
    }

    public function test_hr_cannot_create_maternity_for_male(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('leave_requests.store'), [
            'employee_id' => $employee->id,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'maternity',
            'reason' => 'Không hợp lệ',
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_submit_without_contract_is_blocked(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();
        $employee->contracts()->delete();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'type' => 'annual',
            'reason' => 'Nghỉ',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $employee->id]);
        $this->assertDatabaseMissing('notifications', ['target' => 'hr', 'title' => 'Đơn nghỉ phép cần duyệt']);
    }

    public function test_valid_submit_notifies_hr_and_stays_pending(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'type' => 'annual',
            'reason' => 'Nghỉ phép năm',
        ])->assertRedirect(route('me.leave_requests'));

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'type' => 'annual',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);
    }

    public function test_unpaid_over_contract_limit_is_rejected(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => now()->addDays(8)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => 'unpaid',
            'reason' => 'Việc riêng dài',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    public function test_hr_approve_marks_attendance_for_payroll(): void
    {
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'type' => 'annual',
            'reason' => 'Nghỉ',
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $employee->id)->first();
        $this->actingAs($hr)->post(route('leave_requests.approve', $leave))->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'approved']);
        $this->assertTrue(
            \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '2026-09-07')
                ->where('status', 'leave')
                ->exists()
        );
        $this->assertTrue(Notification::where('target', 'employee')->where('data->leave_request_id', $leave->id)->exists());
        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đơn nghỉ phép đã được duyệt',
        ]);
    }
}
