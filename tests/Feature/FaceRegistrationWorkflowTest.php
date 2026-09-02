<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\FaceProfile;
use App\Models\Notification;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceRegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $user = User::factory()->create(['is_hr' => false, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $department = Department::create(['name' => 'IT', 'code' => 'ITF', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'Nam',
            'email' => $user->email,
            'user_id' => $user->id,
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'ITF01',
        ]);

        return compact('hr', 'user', 'employee');
    }

    private function embedding(): string
    {
        return json_encode(array_fill(0, FaceRecognitionService::DESCRIPTOR_SIZE, 0.08));
    }

    private function faceImage(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode('fake-face');
    }

    public function test_register_sends_hr_notification_and_does_not_enable_punch(): void
    {
        ['user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->postJson('/api/employee/attendance/register-face', [
            'face_embedding' => $this->embedding(),
            'face_image' => $this->faceImage(),
        ])->assertOk()->assertJson([
            'success' => true,
            'registered' => false,
            'pending' => true,
        ]);

        $profile = FaceProfile::where('employee_id', $employee->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame(FaceProfile::PENDING, $profile->status);
        $this->assertNull($profile->face_embedding);
        $this->assertNotNull($profile->pending_face_embedding);
        $this->assertNotNull($profile->pending_face_image);

        $this->assertDatabaseHas('notifications', [
            'target' => 'hr',
            'title' => 'Đăng ký khuôn mặt cần duyệt',
        ]);

        $this->actingAs($user)->postJson('/api/employee/attendance/face', [
            'face_embedding' => $this->embedding(),
            'latitude' => 21.0285,
            'longitude' => 105.8542,
        ])->assertStatus(400)->assertJsonFragment([
            'success' => false,
        ]);
    }

    public function test_hr_approve_saves_face_and_notifies_employee(): void
    {
        ['hr' => $hr, 'user' => $user, 'employee' => $employee] = $this->people();

        $this->actingAs($user)->postJson('/api/employee/attendance/register-face', [
            'face_embedding' => $this->embedding(),
            'face_image' => $this->faceImage(),
        ])->assertOk();

        $profile = FaceProfile::where('employee_id', $employee->id)->first();

        $this->actingAs($user)->post(route('face_profiles.approve', $profile))->assertForbidden();

        $this->actingAs($hr)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Đăng ký khuôn mặt cần duyệt')
            ->assertSee('Duyệt khuôn mặt');

        $this->actingAs($hr)->post(route('face_profiles.approve', $profile))->assertRedirect();

        $profile->refresh();
        $this->assertSame(FaceProfile::APPROVED, $profile->status);
        $this->assertNotNull($profile->face_embedding);
        $this->assertNull($profile->pending_face_embedding);

        $this->assertDatabaseHas('notifications', [
            'target' => 'employee',
            'title' => 'Đăng ký khuôn mặt đã được duyệt',
        ]);

        $this->actingAs($user)->getJson('/api/employee/attendance/face-profile')->assertOk()->assertJson([
            'success' => true,
            'registered' => true,
            'pending' => false,
        ]);
    }
}
