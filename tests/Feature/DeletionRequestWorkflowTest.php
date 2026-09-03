<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\DeletionRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeletionRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $admin = User::factory()->create(['is_hr' => false, 'is_admin' => true, 'is_accountant' => false, 'is_director' => false]);
        $nvUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'Kỹ thuật', 'code' => 'KT', 'manager' => 'M']);
        $emptyDept = Department::create(['name' => 'Dự án tạm', 'code' => 'TMP', 'manager' => 'M', 'employee_count' => 0]);
        $employee = Employee::create([
            'name' => 'Nguyễn Văn A',
            'email' => $nvUser->email,
            'user_id' => $nvUser->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'KT-001',
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-03',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ]);
        $contract = Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chính thức',
            'contract_code' => 'HD-KT-001',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 12000000,
            'base_salary' => 12000000,
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return compact('hr', 'director', 'admin', 'nvUser', 'department', 'emptyDept', 'employee', 'contract');
    }

    public function test_hr_cannot_delete_employee_immediately(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('deletion_requests.create_employee', $employee));

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_submit_requires_reason_or_document(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)
            ->from(route('deletion_requests.create_employee', $employee))
            ->post(route('deletion_requests.store_employee', $employee), ['reason' => ''])
            ->assertRedirect(route('deletion_requests.create_employee', $employee))
            ->assertSessionHas('error');

        $this->assertSame(0, DeletionRequest::count());
    }

    public function test_hr_submits_employee_deletion_for_director(): void
    {
        Storage::fake('public');
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'Nghỉ việc theo biên bản',
            'document' => UploadedFile::fake()->create('bien_ban.pdf', 40, 'application/pdf'),
        ])->assertRedirect(route('deletion_requests.index'));

        $request = DeletionRequest::first();
        $this->assertNotNull($request);
        $this->assertSame(DeletionRequest::PENDING, $request->status);
        $this->assertSame(DeletionRequest::EMPLOYEE, $request->subject_type);
        $this->assertSame($employee->id, $request->subject_id);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'pending_termination']);
        $this->assertTrue(
            Notification::where('target', 'director')->get()->contains(
                fn ($n) => data_get($n->data, 'deletion_request_id') == $request->id
            )
        );
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_duplicate_pending_is_blocked(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'Lần 1',
        ])->assertRedirect(route('deletion_requests.index'));

        $this->actingAs($hr)
            ->from(route('deletion_requests.create_employee', $employee))
            ->post(route('deletion_requests.store_employee', $employee), ['reason' => 'Lần 2'])
            ->assertSessionHas('error');

        $this->assertSame(1, DeletionRequest::count());
    }

    public function test_hr_cannot_approve_and_director_cannot_submit(): void
    {
        ['hr' => $hr, 'director' => $director, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'Nghỉ việc',
        ]);
        $request = DeletionRequest::first();

        $this->actingAs($hr)->post(route('deletion_requests.approve', $request))->assertForbidden();
        $this->actingAs($director)->get(route('deletion_requests.create_employee', $employee))->assertForbidden();
        $this->actingAs($director)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'GĐ không được gửi',
        ])->assertForbidden();
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_director_approves_employee_termination_keeps_history_and_locks_account(): void
    {
        ['hr' => $hr, 'director' => $director, 'admin' => $admin, 'nvUser' => $nvUser, 'employee' => $employee, 'contract' => $contract] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'Chấm dứt HĐ',
            'last_working_day' => '2026-08-20',
        ]);
        $request = DeletionRequest::first();

        $this->actingAs($director)
            ->post(route('deletion_requests.approve', $request))
            ->assertRedirect(route('deletion_requests.show', $request));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'terminated',
            'terminated_at' => '2026-08-20',
            'user_id' => $nvUser->id,
        ]);
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'terminated',
            'end_date' => '2026-08-20',
        ]);
        $this->assertDatabaseHas('attendances', ['employee_id' => $employee->id, 'date' => '2026-08-03']);
        $this->assertDatabaseHas('users', ['id' => $nvUser->id, 'email' => $nvUser->email, 'is_locked' => 1]);

        $request->refresh();
        $this->assertSame(DeletionRequest::APPROVED, $request->status);
        $this->assertSame($employee->id, $request->subject_id);
        $this->assertSame($nvUser->id, $request->account_user_id);
        $this->assertSame('Nguyễn Văn A', $request->snapshot['employee']['name'] ?? null);
        $this->assertSame(1, $request->snapshot['related_counts']['attendances'] ?? null);
        $this->assertSame('terminated', $request->snapshot['contracts'][0]['status'] ?? null);
        $this->assertSame('HD-KT-001', $request->snapshot['contracts'][0]['contract_code'] ?? null);
        $this->assertStringContainsString('Chấm dứt do nghỉ việc', $request->snapshot['contracts'][0]['notes'] ?? '');
        $this->assertSame('2026-08-20', $request->snapshot['last_working_day'] ?? null);
        $this->assertNotEmpty($request->snapshot['settlement'] ?? null);
        $this->assertTrue(Notification::where('target', 'admin')->where('data->type', 'account_deletion')->exists());
        $this->assertTrue(Notification::where('target', 'hr')->where('title', 'like', 'Đã duyệt nghỉ việc%')->exists());

        $this->actingAs($admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Đã khóa tài khoản sau khi nhân viên nghỉ việc');

        $this->actingAs($director)->get(route('deletion_requests.show', $request))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee('Dữ liệu lưu trữ')
            ->assertSee('Hợp đồng đã chấm dứt')
            ->assertSee('HD-KT-001')
            ->assertSee('Đã chấm dứt')
            ->assertSee('Chốt công');

        $this->actingAs($hr)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee('Đã nghỉ việc');
    }

    public function test_director_reject_keeps_employee(): void
    {
        ['hr' => $hr, 'director' => $director, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_employee', $employee), [
            'reason' => 'Nghỉ việc',
        ]);
        $request = DeletionRequest::first();

        $this->actingAs($director)->post(route('deletion_requests.reject', $request), [
            'rejection_reason' => 'Chưa đủ hồ sơ',
        ])->assertRedirect(route('deletion_requests.show', $request));

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'active']);
        $this->assertSame(DeletionRequest::REJECTED, $request->fresh()->status);
        $this->assertTrue(Notification::where('target', 'hr')->where('title', 'like', 'Từ chối%')->exists());
    }

    public function test_department_with_employees_cannot_be_requested_until_cleared(): void
    {
        ['hr' => $hr, 'department' => $department, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)
            ->get(route('deletion_requests.create_department', $department))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee('Gửi GĐ duyệt chuyển')
            ->assertDontSee('Gửi Giám đốc duyệt xóa');

        $this->actingAs($hr)
            ->from(route('deletion_requests.create_department', $department))
            ->post(route('deletion_requests.store_department', $department), ['reason' => 'Tái cơ cấu'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'department_id' => $department->id]);
        $this->assertSame(0, DeletionRequest::count());
    }

    public function test_hr_can_transfer_employees_then_request_department_deletion(): void
    {
        ['hr' => $hr, 'director' => $director, 'department' => $department, 'emptyDept' => $emptyDept, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.transfer_employees', $department), [
            'target_department_id' => $emptyDept->id,
            'transfer_all' => 1,
            'reason' => 'Tái cơ cấu trước khi giải thể',
        ])->assertRedirect(route('deletion_requests.index'));

        $this->assertSame($department->id, $employee->fresh()->department_id);
        $transfer = DeletionRequest::where('subject_type', DeletionRequest::TRANSFER)->first();
        $this->assertNotNull($transfer);
        $this->assertSame(DeletionRequest::PENDING, $transfer->status);
        $this->assertTrue(
            Notification::where('target', 'director')->get()->contains(
                fn ($n) => data_get($n->data, 'type') === 'transfer_request' && data_get($n->data, 'deletion_request_id') == $transfer->id
            )
        );

        $this->actingAs($hr)->post(route('deletion_requests.approve', $transfer))->assertForbidden();
        $this->assertSame($department->id, $employee->fresh()->department_id);

        $this->actingAs($hr)
            ->from(route('deletion_requests.create_department', $department))
            ->post(route('deletion_requests.store_department', $department), ['reason' => 'Giải thể'])
            ->assertSessionHas('error');

        $this->actingAs($director)->post(route('deletion_requests.approve', $transfer));
        $this->assertSame($emptyDept->id, $employee->fresh()->department_id);
        $this->assertSame(0, $department->employees()->count());
        $this->assertTrue(Notification::where('target', 'hr')->where('title', 'Đã chuyển nhân viên')->exists());

        $this->actingAs($hr)->post(route('deletion_requests.store_department', $department), [
            'reason' => 'Giải thể sau khi chuyển nhân viên',
        ])->assertRedirect(route('deletion_requests.index'));

        $delete = DeletionRequest::where('subject_type', DeletionRequest::DEPARTMENT)->first();
        $this->actingAs($director)->post(route('deletion_requests.approve', $delete));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'department_id' => $emptyDept->id]);
    }

    public function test_transferred_employee_is_notified_and_can_feedback_to_hr(): void
    {
        ['hr' => $hr, 'director' => $director, 'nvUser' => $nvUser, 'department' => $department, 'emptyDept' => $emptyDept, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.transfer_employees', $department), [
            'target_department_id' => $emptyDept->id,
            'transfer_all' => 1,
            'reason' => 'Điều chuyển theo quyết định',
        ]);
        $transfer = DeletionRequest::where('subject_type', DeletionRequest::TRANSFER)->first();
        $this->actingAs($director)->post(route('deletion_requests.approve', $transfer));

        $this->assertTrue(
            Notification::where('target', 'employee')->where('title', 'Thông báo điều chuyển nhân sự')->get()->contains(
                fn ($n) => data_get($n->data, 'employee_id') == $employee->id
            )
        );

        $this->actingAs($nvUser)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Thông báo điều chuyển nhân sự')
            ->assertSee('Gửi phản hồi cho HR');

        $this->actingAs($nvUser)->post(route('me.transfers.feedback', $transfer), [
            'agree' => '0',
            'message' => 'Tôi muốn ở lại phòng kỹ thuật',
        ])->assertRedirect();

        $this->assertTrue(Notification::where('target', 'hr')->where('data->type', 'transfer_feedback')->exists());
        $this->actingAs($hr)->get(route('deletion_requests.show', $transfer))
            ->assertOk()
            ->assertSee('Tôi muốn ở lại phòng kỹ thuật');

        $this->actingAs($hr)->post(route('deletion_requests.reply_feedback', [$transfer, $employee->id]), [
            'reply' => 'Đã ghi nhận, HR sẽ xem xét lại.',
        ])->assertRedirect();

        $this->actingAs($nvUser)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('HR đã phản hồi điều chuyển nhân sự')
            ->assertSee('Đã ghi nhận, HR sẽ xem xét lại.');
    }

    public function test_director_approves_empty_department_deletion_with_history(): void
    {
        ['hr' => $hr, 'director' => $director, 'emptyDept' => $emptyDept] = $this->people();

        $this->actingAs($hr)->post(route('deletion_requests.store_department', $emptyDept), [
            'reason' => 'Giải thể phòng dự án',
        ])->assertRedirect(route('deletion_requests.index'));

        $request = DeletionRequest::first();
        $this->actingAs($director)->post(route('deletion_requests.approve', $request));

        $this->assertDatabaseMissing('departments', ['id' => $emptyDept->id]);
        $request->refresh();
        $this->assertSame(DeletionRequest::APPROVED, $request->status);
        $this->assertSame('Dự án tạm', $request->snapshot['department']['name'] ?? null);

        $this->actingAs($hr)->get(route('deletion_requests.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('Dự án tạm');
    }

    public function test_hr_can_transfer_from_standalone_form(): void
    {
        ['hr' => $hr, 'director' => $director, 'nvUser' => $nvUser, 'department' => $department, 'emptyDept' => $emptyDept, 'employee' => $employee] = $this->people();

        $this->actingAs($director)->get(route('transfers.create'))->assertForbidden();

        $this->actingAs($hr)->get(route('transfers.create'))
            ->assertOk()
            ->assertSee('Chọn phòng ban')
            ->assertSee('Chọn nhân viên')
            ->assertSee('— Chọn phòng ban trước —')
            ->assertDontSee('Phòng ban nguồn');

        $this->actingAs($hr)->get(route('transfers.create', ['from' => $department->id]))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee($department->name)
            ->assertDontSee('— Chọn phòng ban trước —');

        $this->actingAs($hr)->get(route('transfers.create', ['employee' => $employee->id]))
            ->assertOk()
            ->assertSee('Nguyễn Văn A')
            ->assertSee('Phòng ban hiện tại')
            ->assertSee($department->name)
            ->assertSee('Gửi Giám đốc duyệt')
            ->assertDontSee('Phòng ban nguồn')
            ->assertDontSee('name="from_department_id"', false)
            ->assertDontSee('name="source_department_id"', false);

        $this->actingAs($hr)->post(route('transfers.store'), [
            'employee_id' => $employee->id,
            'target_department_id' => $emptyDept->id,
            'source_department_id' => 999,
            'from_department_id' => $emptyDept->id,
            'reason' => 'Bố trí lại nhân sự',
        ])->assertRedirect(route('deletion_requests.index'));

        $this->assertSame($department->id, $employee->fresh()->department_id);
        $transfer = DeletionRequest::where('subject_type', DeletionRequest::TRANSFER)->first();
        $this->assertNotNull($transfer);
        $this->assertSame($department->id, (int) data_get($transfer->snapshot, 'from.id'));
        $this->assertSame($emptyDept->id, (int) data_get($transfer->snapshot, 'to.id'));
        $this->assertSame(DeletionRequest::PENDING, $transfer->status);
        $this->assertSame('Chờ Giám đốc duyệt', $transfer->statusLabel());

        $this->actingAs($director)->post(route('deletion_requests.approve', $transfer));
        $this->assertSame($emptyDept->id, $employee->fresh()->department_id);
        $transfer->refresh();
        $this->assertSame('Đã duyệt', $transfer->statusLabel());
        $this->assertSame('Điều chuyển nhân viên', data_get($transfer->snapshot, 'history.title'));
        $this->assertSame($director->name, data_get($transfer->snapshot, 'history.approved_by'));
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_id' => $emptyDept->id,
            'end_reason' => null,
            'status' => 'holding',
        ]);
        $this->assertTrue(
            \App\Models\ActivityLog::query()
                ->where('action', 'transfer_approved')
                ->where('meta', 'like', '%Từ: Kỹ thuật%')
                ->where('meta', 'like', '%Đến: Dự án tạm%')
                ->exists()
        );
        $this->actingAs($nvUser)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Thông báo điều chuyển nhân sự');
        $this->actingAs($hr)->get(route('deletion_requests.show', $transfer))
            ->assertOk()
            ->assertSee('Điều chuyển nhân viên')
            ->assertSee('Đã duyệt')
            ->assertSee($director->name);
    }

    public function test_transfer_rejects_same_department_and_unassigned_employee(): void
    {
        ['hr' => $hr, 'department' => $department, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)
            ->from(route('transfers.create', ['employee' => $employee->id]))
            ->post(route('transfers.store'), [
                'employee_id' => $employee->id,
                'target_department_id' => $department->id,
                'reason' => 'Giữ nguyên phòng',
            ])
            ->assertSessionHasErrors(['target_department_id']);

        $this->assertSame(0, DeletionRequest::count());
        $this->assertSame($department->id, $employee->fresh()->department_id);

        $unassigned = Employee::create([
            'name' => 'Nguyễn Văn B',
            'email' => 'b-unassigned@smarthr.test',
            'position' => 'Dev',
            'department_id' => null,
            'status' => 'active',
            'employee_code' => 'NONE-001',
        ]);

        $this->actingAs($hr)->get(route('transfers.create', ['employee' => $unassigned->id]))
            ->assertOk()
            ->assertSee('Nhân viên chưa được phân công phòng ban. Vui lòng cập nhật hồ sơ nhân viên trước khi tạo yêu cầu điều chuyển.');

        $this->actingAs($hr)
            ->from(route('transfers.create', ['employee' => $unassigned->id]))
            ->post(route('transfers.store'), [
                'employee_id' => $unassigned->id,
                'target_department_id' => $department->id,
                'source_department_id' => $department->id,
                'reason' => 'Phân công mới',
            ])
            ->assertSessionHas('error', 'Nhân viên chưa được phân công phòng ban. Vui lòng cập nhật hồ sơ nhân viên trước khi tạo yêu cầu điều chuyển.');

        $this->assertSame(0, DeletionRequest::count());
        $this->assertNull($unassigned->fresh()->department_id);
    }

    public function test_director_reject_keeps_employee_department(): void
    {
        ['hr' => $hr, 'director' => $director, 'department' => $department, 'emptyDept' => $emptyDept, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)->post(route('transfers.store'), [
            'employee_id' => $employee->id,
            'target_department_id' => $emptyDept->id,
            'reason' => 'Tăng cường dự án',
        ]);
        $transfer = DeletionRequest::where('subject_type', DeletionRequest::TRANSFER)->first();
        $this->assertSame($department->id, $employee->fresh()->department_id);

        $this->actingAs($director)->post(route('deletion_requests.reject', $transfer), [
            'rejection_reason' => 'Chưa đủ biên bản',
        ]);

        $this->assertSame($department->id, $employee->fresh()->department_id);
        $this->assertSame(DeletionRequest::REJECTED, $transfer->fresh()->status);
        $this->assertSame(0, \App\Models\EmployeePositionHistory::count());
    }

    public function test_employee_profile_cannot_bypass_transfer_approval(): void
    {
        ['hr' => $hr, 'department' => $department, 'emptyDept' => $emptyDept, 'employee' => $employee] = $this->people();

        $this->actingAs($hr)
            ->from(route('employees.edit', $employee))
            ->put(route('employees.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'position' => $employee->position,
                'department_id' => $emptyDept->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['department_id']);

        $this->assertSame($department->id, $employee->fresh()->department_id);
    }

    public function test_transfer_form_includes_board_but_excludes_director(): void
    {
        ['hr' => $hr, 'department' => $department, 'employee' => $employee] = $this->people();
        $board = Department::create(['name' => 'Ban Giám đốc', 'code' => 'BGD', 'manager' => 'GĐ']);
        $assistant = Employee::create([
            'name' => 'Trợ lý BGD',
            'email' => 'troly.bgd.'.uniqid().'@example.com',
            'department_id' => $board->id,
            'position' => 'Trợ lý Giám đốc',
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $director = Employee::create([
            'name' => 'Giám đốc Test',
            'email' => 'giamdoc.test.'.uniqid().'@example.com',
            'department_id' => $board->id,
            'position' => 'Giám đốc',
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $this->actingAs($hr)->get(route('transfers.create'))
            ->assertOk()
            ->assertSee('Ban Giám đốc')
            ->assertSee($department->name);

        $this->actingAs($hr)->get(route('transfers.create', ['from' => $board->id]))
            ->assertOk()
            ->assertSee('Ban Giám đốc')
            ->assertSee($assistant->name)
            ->assertDontSee($director->name);

        $this->actingAs($hr)->post(route('transfers.store'), [
            'employee_id' => $assistant->id,
            'target_department_id' => $department->id,
            'reason' => 'Chuyển trợ lý sang phòng khác để hỗ trợ',
        ])->assertRedirect(route('deletion_requests.index'));
    }
}
