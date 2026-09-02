<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $director = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => true]);
        $nvUser = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'Kỹ thuật', 'code' => 'KT', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nguyễn Văn A',
            'email' => $nvUser->email,
            'user_id' => $nvUser->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'KT-001',
        ]);
        $hrEmployee = Employee::create([
            'name' => 'Trần Thị Bích',
            'email' => $hr->email,
            'user_id' => $hr->id,
            'position' => 'HR',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'HR-001',
        ]);
        $directorEmployee = Employee::create([
            'name' => 'Phạm Thị Dung',
            'email' => $director->email,
            'user_id' => $director->id,
            'position' => 'Giám đốc',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'GD-001',
        ]);

        return compact('hr', 'director', 'nvUser', 'employee', 'hrEmployee', 'directorEmployee');
    }

    public function test_employee_submit_goes_to_hr_not_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'nvUser' => $nvUser] = $this->people();

        $this->actingAs($nvUser)->post(route('me.support_requests.store'), [
            'subject' => 'Sai giờ chấm công',
            'message' => 'Ngày 30/08 máy không nhận mặt',
            'type' => 'attendance',
        ])->assertRedirect(route('me.support_requests'));

        $ticket = SupportRequest::first();
        $this->assertNotNull($ticket);
        $this->assertSame(SupportRequest::PENDING, $ticket->status);
        $this->assertTrue(Notification::where('target', 'hr')->where('title', 'Yêu cầu hỗ trợ cần duyệt')->exists());
        $this->assertFalse(Notification::where('target', 'director')->where('data->type', 'support_request')->exists());

        $this->actingAs($director)->post(route('support_requests.approve', $ticket))->assertForbidden();
        $this->actingAs($nvUser)->post(route('support_requests.approve', $ticket))->assertForbidden();
        $this->assertSame(SupportRequest::PENDING, $ticket->fresh()->status);

        $this->actingAs($hr)->get(route('support_requests.index'))
            ->assertOk()
            ->assertSee('Sai giờ chấm công')
            ->assertSee('Duyệt');
    }

    public function test_hr_approve_notifies_employee_and_button_becomes_resolved(): void
    {
        ['hr' => $hr, 'nvUser' => $nvUser] = $this->people();
        $this->actingAs($nvUser)->post(route('me.support_requests.store'), [
            'subject' => 'Cần giấy xác nhận',
            'message' => 'Xin giấy xác nhận thu nhập',
            'type' => 'document',
        ]);
        $ticket = SupportRequest::first();

        $this->actingAs($hr)
            ->post(route('support_requests.approve', $ticket))
            ->assertRedirect(route('support_requests.show', $ticket));

        $this->assertSame(SupportRequest::PROCESSING, $ticket->fresh()->status);
        $this->assertTrue(
            Notification::where('target', 'employee')->where('title', 'Yêu cầu hỗ trợ đã được duyệt')->exists()
        );

        $this->actingAs($nvUser)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Yêu cầu hỗ trợ đã được duyệt');

        $this->actingAs($hr)->get(route('support_requests.index'))
            ->assertOk()
            ->assertSee('Đã xử lý')
            ->assertDontSee('>Duyệt</button>', false);

        $this->actingAs($nvUser)
            ->post(route('me.support_requests.feedback', $ticket), ['employee_feedback' => 'Chưa xong'])
            ->assertSessionHas('error');
        $this->assertNull($ticket->fresh()->employee_feedback);
    }

    public function test_hr_resolve_notifies_employee_who_can_feedback(): void
    {
        ['hr' => $hr, 'nvUser' => $nvUser, 'employee' => $employee] = $this->people();
        $this->actingAs($nvUser)->post(route('me.support_requests.store'), [
            'subject' => 'Sai lương',
            'message' => 'Thiếu phụ cấp',
            'type' => 'payroll',
        ]);
        $ticket = SupportRequest::first();
        $this->actingAs($hr)->post(route('support_requests.approve', $ticket));
        $this->actingAs($hr)->post(route('support_requests.resolve', $ticket), [
            'hr_reply' => 'Đã bổ sung phụ cấp tháng 8.',
        ])->assertRedirect(route('support_requests.show', $ticket));

        $ticket->refresh();
        $this->assertSame(SupportRequest::RESOLVED, $ticket->status);
        $this->assertSame('Đã bổ sung phụ cấp tháng 8.', $ticket->hr_reply);
        $this->assertTrue(Notification::where('target', 'employee')->where('title', 'Yêu cầu hỗ trợ đã được xử lý')->exists());

        $this->actingAs($nvUser)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Yêu cầu hỗ trợ đã được xử lý')
            ->assertSee('Gửi phản hồi cho HR');

        $this->actingAs($nvUser)->get(route('me.support_requests.show', $ticket))
            ->assertOk()
            ->assertSee('Đã bổ sung phụ cấp tháng 8.')
            ->assertSee('Phản hồi kết quả xử lý');

        $this->actingAs($nvUser)->post(route('me.support_requests.feedback', $ticket), [
            'employee_feedback' => 'Đã nhận, cảm ơn HR.',
        ])->assertRedirect();

        $this->assertSame('Đã nhận, cảm ơn HR.', $ticket->fresh()->employee_feedback);
        $this->assertTrue(Notification::where('target', 'hr')->where('data->type', 'support_feedback')->exists());

        $this->actingAs($hr)->get(route('support_requests.show', $ticket))
            ->assertOk()
            ->assertSee('Đã nhận, cảm ơn HR.')
            ->assertSee('Đã xử lý');
    }

    public function test_hr_support_is_handled_by_director(): void
    {
        ['hr' => $hr, 'director' => $director, 'hrEmployee' => $hrEmployee] = $this->people();

        $this->actingAs($hr)->post(route('me.support_requests.store'), [
            'subject' => 'Cần GĐ xác nhận',
            'message' => 'Xin duyệt chính sách mới',
            'type' => 'personnel',
        ])->assertRedirect(route('me.support_requests'));

        $ticket = SupportRequest::where('employee_id', $hrEmployee->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame(SupportRequest::PENDING, $ticket->status);
        $this->assertTrue(Notification::where('target', 'director')->where('title', 'Yêu cầu hỗ trợ cần duyệt')->exists());
        $this->assertFalse(Notification::where('target', 'hr')->where('title', 'Yêu cầu hỗ trợ cần duyệt')->exists());

        $this->actingAs($hr)->post(route('support_requests.approve', $ticket))->assertForbidden();
        $this->assertSame(SupportRequest::PENDING, $ticket->fresh()->status);

        $this->actingAs($director)->get(route('support_requests.index'))
            ->assertOk()
            ->assertSee('Cần GĐ xác nhận')
            ->assertSee('Duyệt');

        $this->actingAs($hr)->get(route('support_requests.index'))
            ->assertOk()
            ->assertDontSee('Cần GĐ xác nhận');

        $this->actingAs($director)->post(route('support_requests.approve', $ticket))
            ->assertRedirect(route('support_requests.show', $ticket));
        $this->assertSame(SupportRequest::PROCESSING, $ticket->fresh()->status);

        $this->actingAs($hr)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Yêu cầu hỗ trợ đã được duyệt');

        $this->actingAs($director)->get(route('support_requests.show', $ticket))
            ->assertOk()
            ->assertSee('Đã xử lý');

        $this->actingAs($director)->post(route('support_requests.resolve', $ticket), [
            'hr_reply' => 'Đã xác nhận chính sách.',
        ])->assertRedirect(route('support_requests.show', $ticket));

        $this->assertSame(SupportRequest::RESOLVED, $ticket->fresh()->status);
        $this->actingAs($hr)->get(route('me.notifications'))
            ->assertOk()
            ->assertSee('Yêu cầu hỗ trợ đã được xử lý')
            ->assertSee('Gửi phản hồi cho Giám đốc');

        $this->actingAs($hr)->post(route('me.support_requests.feedback', $ticket), [
            'employee_feedback' => 'Đã rõ, cảm ơn Giám đốc.',
        ])->assertRedirect();

        $this->assertTrue(Notification::where('target', 'director')->where('data->type', 'support_feedback')->exists());
        $this->actingAs($director)->get(route('support_requests.show', $ticket))
            ->assertOk()
            ->assertSee('Đã rõ, cảm ơn Giám đốc.');
    }

    public function test_hr_cannot_manage_director_employee(): void
    {
        ['hr' => $hr, 'directorEmployee' => $directorEmployee] = $this->people();

        $this->actingAs($hr)->get(route('employees.edit', $directorEmployee))->assertForbidden();
        $this->actingAs($hr)->put(route('employees.update', $directorEmployee), [
            'name' => 'Đổi tên GĐ',
            'email' => $directorEmployee->email,
            'position' => 'Giám đốc',
            'department_id' => $directorEmployee->department_id,
            'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($hr)->get(route('deletion_requests.create_employee', $directorEmployee))->assertForbidden();
        $this->actingAs($hr)->get(route('transfers.create', ['employee' => $directorEmployee->id]))->assertForbidden();
        $this->actingAs($hr)->get(route('employees.show', $directorEmployee))
            ->assertOk()
            ->assertDontSee('Sửa thông tin');
    }
}
