<?php

namespace App\Http\Controllers\Employee;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Services\EmployeeAttendanceRecorder;
use App\Services\FaceRecognitionService;
use App\Services\FaceRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceAttendanceController extends Controller
{
    public function __construct(
        private EmployeeAttendanceRecorder $recorder,
        private FaceRecognitionService $faceService,
        private FaceRegistrationService $registration,
    ) {
    }

    public function getFaceProfile()
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        $profile = FaceProfile::where('employee_id', $employee->id)->first();
        $registered = $profile && $this->faceService->parse($profile->face_embedding);

        return response()->json([
            'success' => true,
            'registered' => $registered,
            'pending' => (bool) $profile?->isPending(),
            'status' => $profile?->status,
            'face_image' => $profile?->previewImage(),
        ]);
    }

    public function registerFaceProfile(Request $request)
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        $data = $request->validate([
            'face_embedding' => 'required|string',
            'face_image' => 'required|string',
        ]);

        try {
            $profile = $this->registration->submit(
                $employee,
                Auth::user(),
                $data['face_embedding'],
                $data['face_image']
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $registered = (bool) $this->faceService->parse($profile->face_embedding);
        $approver = \App\Support\RequestApprover::queueLabel($employee);

        return response()->json([
            'success' => true,
            'message' => $registered
                ? "Đã gửi ảnh khuôn mặt mới cho {$approver} duyệt. Bạn vẫn chấm công bằng khuôn mặt đã duyệt cho đến khi {$approver} duyệt ảnh mới."
                : "Đã gửi ảnh khuôn mặt cho {$approver} duyệt. Bạn chỉ chấm công được sau khi {$approver} duyệt.",
            'registered' => $registered,
            'pending' => $profile->isPending(),
            'status' => $profile->status,
            'face_image' => $profile->previewImage(),
        ]);
    }

    public function faceAttendance(Request $request)
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        $data = $request->validate([
            'face_embedding' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $profile = FaceProfile::where('employee_id', $employee->id)->first();
        if (! $profile || ! $profile->isUsableForPunch() || ! $this->faceService->parse($profile->face_embedding)) {
            $pending = $profile?->isPending();
            $approver = \App\Support\RequestApprover::queueLabel($employee);

            return response()->json([
                'success' => false,
                'message' => $pending
                    ? "Khuôn mặt đang chờ {$approver} duyệt. Bạn chưa thể chấm công bằng khuôn mặt."
                    : "Bạn chưa đăng ký khuôn mặt hoặc {$approver} chưa duyệt. Hãy đăng ký rồi đợi {$approver} duyệt.",
            ], 400);
        }

        if (! $this->faceService->matches($profile->face_embedding, $data['face_embedding'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không khớp khuôn mặt đã đăng ký. Hãy nhìn thẳng camera, đủ sáng rồi thử lại.',
            ], 400);
        }

        try {
            $result = $this->recorder->record(
                $employee,
                Auth::user(),
                (float) $data['latitude'],
                (float) $data['longitude'],
                $data['notes'] ?? null,
                'face',
                (string) $request->ip(),
            );
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'action' => $result['action'],
            'attendance' => $result['attendance'],
            'distance' => $result['distance'],
            'metrics' => $result['metrics'] ?? null,
        ]);
    }
}
