<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FaceProfile;
use App\Services\AttendanceCalculationService;
use App\Services\FaceRecognitionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceAttendanceController extends Controller
{
    private AttendanceCalculationService $calculationService;
    private FaceRecognitionService $faceService;

    public function __construct(AttendanceCalculationService $calculationService, FaceRecognitionService $faceService)
    {
        $this->calculationService = $calculationService;
        $this->faceService = $faceService;
    }

    public function getFaceProfile()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $profile = FaceProfile::where('employee_id', $employee->id)->first();

        return response()->json([
            'success' => true,
            'face_profile' => $profile,
        ]);
    }

    public function registerFaceProfile(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'face_embedding' => 'nullable|string',
            'face_image' => 'nullable|string',
        ]);

        $profile = FaceProfile::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'face_embedding' => $data['face_embedding'] ?? null,
                'face_image' => $data['face_image'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký khuôn mặt thành công.',
            'face_profile' => $profile,
        ]);
    }

    public function faceAttendance(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'face_embedding' => 'nullable|string',
            'face_image' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $profile = FaceProfile::where('employee_id', $employee->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng ký khuôn mặt.',
            ], 400);
        }

        $matched = $this->faceService->matches(
            $profile->face_embedding,
            $data['face_embedding'] ?? null,
            $profile->face_image,
            $data['face_image'] ?? null
        );

        if (!$matched) {
            return response()->json([
                'success' => false,
                'message' => 'Không nhận diện được khuôn mặt.',
            ], 400);
        }

        $today = Carbon::today();
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $notes = $data['notes'] ?? null;
        $ipAddress = $request->ip();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $today, $latitude, $longitude, $notes, $ipAddress) {
            $attendance = Attendance::lockForEmployeeDate($employee->id, $today);

            if (!$attendance->check_in) {
                $attendance->update([
                    'check_in' => Carbon::now(),
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_location' => $this->formatCoordinates($latitude, $longitude),
                    'check_in_ip_address' => $ipAddress,
                    'check_in_notes' => $notes,
                    'attendance_method' => 'face',
                    'attendance_status' => 'check_in',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Chấm công vào bằng khuôn mặt thành công.',
                    'attendance' => $attendance->fresh(),
                ]);
            }

            if (!$attendance->check_out) {
                $attendance->update([
                    'check_out' => Carbon::now(),
                    'check_out_latitude' => $latitude,
                    'check_out_longitude' => $longitude,
                    'check_out_location' => $this->formatCoordinates($latitude, $longitude),
                    'check_out_ip_address' => $ipAddress,
                    'check_out_notes' => $notes,
                    'attendance_method' => 'face',
                    'attendance_status' => 'check_out',
                ]);

                $attendance = $this->calculationService->updateAttendanceMetrics($attendance);

                return response()->json([
                    'success' => true,
                    'message' => 'Chấm công ra bằng khuôn mặt thành công.',
                    'attendance' => $attendance,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Bạn đã chấm công vào và ra đủ trong ngày.',
            ], 400);
        });
    }

    private function formatCoordinates(?float $latitude, ?float $longitude): ?string
    {
        if (!$latitude || !$longitude) {
            return null;
        }

        return sprintf('%.6f, %.6f', $latitude, $longitude);
    }
}
