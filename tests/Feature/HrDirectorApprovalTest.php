<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FaceProfile;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrDirectorApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $user = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'HCNS', 'code' => 'HCNS', 'manager' => 'M']);
        $hrEmployee = Employee::create([
            'name' => 'Trần Thị Bích',
            'email' => $hr->email,
            'user_id' => $hr->id,
            'position' => 'HR',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'HR01',
            'leave_balance' => 12,
            'gender' => 'female',
        ]);
        $employee = Employee::create([
            'name' => 'Nam',
            'email' => $user->email,
            'user_id' => $user->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'NV01',
            'leave_balance' => 12,
            'gender' => 'male',
        ]);
        foreach ([$hrEmployee, $employee] as $row) {
            Contract::create([
                'employee_id' => $row->id,
                'title' => 'HĐ',
                'contract_type' => 'fixed_term',
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'salary' => 10000000,
                'base_salary' => 10000000,
                'status' => Contract::STATUS_ACTIVE,
                'allowed_unpaid_leave_days_per_month' => 1,
                'allowed_maternity_leave_days' => 180,
            ]);
        }

        return compact('hr', 'director', 'user', 'hrEmployee', 'employee');
    }

    public function test_hr_leave_goes_to_director_not_hr(): void
    {
        ['hr' => $hr, 'director' => $director, 'hrEmployee' => $hrEmployee] = $this->people();

        $this->actingAs($hr)->get(route('me.leave_requests.create'))->assertOk();

        $this->actingAs($hr)->post(route('me.leave_requests.store'), [
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'type' => 'annual',
            'reason' => 'Nghỉ của HR',
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $hrEmployee->id)->first();
        $this->assertNotNull($leave);
        $this->assertSame('pending', $leave->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'director',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'target' => 'hr',
            'title' => 'Đơn nghỉ phép cần duyệt',
        ]);

        $this->actingAs($hr)->post(route('leave_requests.approve', $leave))->assertForbidden();
        $this->assertSame('pending', $leave->fresh()->status);

        $this->actingAs($director)->post(route('leave_requests.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đơn nghỉ phép đã được duyệt',
        ]);
    }

    public function test_employee_leave_still_requires_hr_not_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.leave_requests.store'), [
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-08',
            'type' => 'annual',
            'reason' => 'Nghỉ NV',
        ])->assertRedirect(route('me.leave_requests'));

        $leave = LeaveRequest::where('employee_id', $employee->id)->first();
        $this->actingAs($director)->post(route('leave_requests.approve', $leave))->assertForbidden();
        $this->actingAs($hr)->post(route('leave_requests.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_hr_overtime_is_approved_by_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'hrEmployee' => $hrEmployee] = $this->people();

        $this->actingAs($hr)->post(route('me.overtime_requests.store'), [
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'OT HR',
        ])->assertRedirect(route('me.overtime_requests'));

        $ot = OvertimeRequest::where('employee_id', $hrEmployee->id)->first();
        $this->assertSame('pending', $ot->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'director',
            'title' => 'Đăng ký tăng ca cần duyệt',
        ]);

        $this->actingAs($hr)->post(route('overtime_requests.approve', $ot))->assertForbidden();
        $this->assertSame('pending', $ot->fresh()->status);

        $this->actingAs($director)->post(route('overtime_requests.approve', $ot))->assertRedirect();
        $this->assertSame('approved', $ot->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đăng ký tăng ca đã được duyệt',
        ]);
    }

    public function test_cannot_register_overtime_in_the_past(): void
    {
        ['user' => $user] = $this->people();

        $this->actingAs($user)->get(route('me.overtime_requests.create'))
            ->assertOk()
            ->assertSee('min="'.now()->toDateString().'"', false)
            ->assertSee('max="'.now()->addDay()->toDateString().'"', false);

        $this->actingAs($user)
            ->from(route('me.overtime_requests.create'))
            ->post(route('me.overtime_requests.store'), [
                'date' => now()->subDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'reason' => 'OT quá khứ',
            ])
            ->assertRedirect(route('me.overtime_requests.create'))
            ->assertSessionHasErrors('date');

        $this->assertSame(0, OvertimeRequest::count());
    }

    public function test_employee_overtime_is_approved_by_hr(): void
    {
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->post(route('me.overtime_requests.store'), [
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'OT NV',
        ])->assertRedirect();

        $ot = OvertimeRequest::where('employee_id', $employee->id)->first();
        $this->actingAs($hr)->post(route('overtime_requests.approve', $ot))->assertRedirect();
        $this->assertSame('approved', $ot->fresh()->status);
        $this->assertSame('18:00', substr((string) $ot->fresh()->approved_start, 0, 5));
        $this->assertSame('20:00', substr((string) $ot->fresh()->approved_end, 0, 5));
    }

    public function test_employee_cannot_register_overtime_two_days_ahead(): void
    {
        ['user' => $user] = $this->people();

        $this->actingAs($user)
            ->from(route('me.overtime_requests.create'))
            ->post(route('me.overtime_requests.store'), [
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'reason' => 'OT xa',
            ])
            ->assertRedirect(route('me.overtime_requests.create'))
            ->assertSessionHasErrors('date');

        $this->assertSame(0, OvertimeRequest::count());
    }

    public function test_hr_can_assign_future_overtime_without_employee_request(): void
    {
        ['hr' => $hr, 'director' => $director, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->get(route('overtime_requests.index'))
            ->assertOk()
            ->assertSee('Chỉ định tăng ca', false)
            ->assertSee('Chờ xác nhận giờ thực tế', false);

        $date = now()->addDays(3)->toDateString();
        $this->actingAs($director)->post(route('overtime_requests.assign'), [
            'employee_id' => $employee->id,
            'date' => $date,
            'start_time' => '17:30',
            'end_time' => '20:00',
            'reason' => 'Kế hoạch tháng',
        ])->assertForbidden();

        $this->actingAs($hr)->post(route('overtime_requests.assign'), [
            'employee_id' => $employee->id,
            'date' => $date,
            'start_time' => '17:30',
            'end_time' => '20:00',
            'reason' => 'Kế hoạch tháng',
        ])->assertRedirect(route('overtime_requests.index'));

        $ot = OvertimeRequest::where('employee_id', $employee->id)->first();
        $this->assertNotNull($ot);
        $this->assertSame(OvertimeRequest::SOURCE_ASSIGNED, $ot->source);
        $this->assertSame(OvertimeRequest::STATUS_APPROVED, $ot->status);
        $this->assertSame($hr->id, $ot->assigned_by);
        $this->assertSame($hr->id, $ot->approved_by);
    }

    public function test_hr_face_registration_goes_to_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'hrEmployee' => $hrEmployee] = $this->people();
        $embedding = json_encode(array_fill(0, FaceRecognitionService::DESCRIPTOR_SIZE, 0.08));
        $image = 'data:image/jpeg;base64,'.base64_encode('fake-face');

        $this->actingAs($hr)->postJson('/api/employee/attendance/register-face', [
            'face_embedding' => $embedding,
            'face_image' => $image,
        ])->assertOk()->assertJson(['pending' => true, 'registered' => false]);

        $this->assertDatabaseHas('notifications', [
            'target' => 'director',
            'title' => 'Đăng ký khuôn mặt cần duyệt',
        ]);

        $profile = FaceProfile::where('employee_id', $hrEmployee->id)->first();
        $this->actingAs($hr)->post(route('face_profiles.approve', $profile))->assertForbidden();
        $this->assertSame(FaceProfile::PENDING, $profile->fresh()->status);

        $this->actingAs($director)->post(route('face_profiles.approve', $profile))->assertRedirect();
        $this->assertSame(FaceProfile::APPROVED, $profile->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đăng ký khuôn mặt đã được duyệt',
        ]);
    }
}
