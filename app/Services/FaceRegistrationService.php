<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\FaceProfile;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\RequestApprover;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FaceRegistrationService
{
    public function __construct(private FaceRecognitionService $faceService)
    {
    }

    public function submit(Employee $employee, User $actor, string $embeddingJson, string $image): FaceProfile
    {
        $embedding = $this->faceService->parse($embeddingJson);
        if (! $embedding) {
            throw new RuntimeException('Dữ liệu khuôn mặt không hợp lệ. Hãy chụp lại, đảm bảo chỉ có một khuôn mặt trong khung hình.');
        }

        $image = $this->sanitizeFaceImage($image);
        if (! $image) {
            throw new RuntimeException('Ảnh khuôn mặt không hợp lệ.');
        }

        return DB::transaction(function () use ($employee, $actor, $embedding, $image) {
            $profile = FaceProfile::firstOrNew(['employee_id' => $employee->id]);
            $profile->fill([
                'pending_face_embedding' => $this->faceService->encode($embedding),
                'pending_face_image' => $image,
                'status' => FaceProfile::PENDING,
                'rejection_reason' => null,
            ]);
            $profile->save();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'face_registration_submitted',
                'meta' => 'face_profile:'.$profile->id,
            ]);

            RequestApprover::notifyQueue(
                $employee,
                $actor,
                'Đăng ký khuôn mặt cần duyệt',
                sprintf(
                    '%s gửi ảnh khuôn mặt để chấm công. Vui lòng đối chiếu ảnh và duyệt trước khi được phép chấm công bằng khuôn mặt.',
                    $employee->name
                ),
                [
                    'type' => 'face_registration',
                    'face_profile_id' => $profile->id,
                ]
            );

            return $profile->fresh();
        });
    }

    public function approve(FaceProfile $profile, User $hr): FaceProfile
    {
        $profile->loadMissing('employee');
        if (! RequestApprover::canReview($hr, $profile->employee)) {
            throw new RuntimeException(
                RequestApprover::needsDirector($profile->employee)
                    ? 'Đăng ký khuôn mặt của HR do Giám đốc duyệt.'
                    : 'Chỉ HR được duyệt đăng ký khuôn mặt của nhân viên.'
            );
        }
        if (! $profile->isPending()) {
            throw new RuntimeException('Không có đăng ký khuôn mặt đang chờ duyệt.');
        }

        return DB::transaction(function () use ($profile, $hr) {
            $profile->update([
                'face_embedding' => $profile->pending_face_embedding,
                'face_image' => $profile->pending_face_image,
                'pending_face_embedding' => null,
                'pending_face_image' => null,
                'status' => FaceProfile::APPROVED,
                'approved_by' => $hr->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            ActivityLog::create([
                'user_id' => $hr->id,
                'action' => 'face_registration_approved',
                'meta' => 'face_profile:'.$profile->id,
            ]);

            HrApprovalNotifier::approved($profile->employee_id, $hr, 'Đăng ký khuôn mặt', [
                'type' => 'face_registration',
                'face_profile_id' => $profile->id,
            ]);

            return $profile->fresh();
        });
    }

    public function reject(FaceProfile $profile, User $hr, string $reason): FaceProfile
    {
        $profile->loadMissing('employee');
        if (! RequestApprover::canReview($hr, $profile->employee)) {
            throw new RuntimeException(
                RequestApprover::needsDirector($profile->employee)
                    ? 'Đăng ký khuôn mặt của HR do Giám đốc duyệt.'
                    : 'Chỉ HR được từ chối đăng ký khuôn mặt của nhân viên.'
            );
        }
        if (! $profile->isPending()) {
            throw new RuntimeException('Không có đăng ký khuôn mặt đang chờ duyệt.');
        }

        $reason = trim($reason) !== '' ? trim($reason) : 'HR từ chối đăng ký khuôn mặt.';

        return DB::transaction(function () use ($profile, $hr, $reason) {
            $keepApproved = filled($profile->face_embedding);
            $profile->update([
                'pending_face_embedding' => null,
                'pending_face_image' => null,
                'status' => $keepApproved ? FaceProfile::APPROVED : FaceProfile::REJECTED,
                'approved_by' => $hr->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            ActivityLog::create([
                'user_id' => $hr->id,
                'action' => 'face_registration_rejected',
                'meta' => 'face_profile:'.$profile->id,
            ]);

            HrApprovalNotifier::rejected($profile->employee_id, $hr, 'Đăng ký khuôn mặt', $reason, [
                'type' => 'face_registration',
                'face_profile_id' => $profile->id,
            ]);

            return $profile->fresh();
        });
    }

    public function sanitizeFaceImage(string $image): ?string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $image)) {
            return null;
        }

        if (strlen($image) > 800000) {
            return null;
        }

        return $image;
    }
}
