<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private AttendanceCalculationService $calculationService;

    public function __construct(AttendanceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }
    // Office location coordinates (Hà Nội coordinates as default)
    private const OFFICE_LATITUDE = 21.0285;
    private const OFFICE_LONGITUDE = 105.8542;
    private const ALLOWED_DISTANCE_METERS = 10000; // 10000 meters radius

    /**
     * Get today's attendance record for current employee
     */
    public function getTodayAttendance()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        
        $today = Carbon::today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
            'today' => $today->format('Y-m-d'),
        ]);
    }

    /**
     * Check-in with location verification
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today();
        
        // Check if already checked in today
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã chấm công vào hôm nay lúc ' . $existingAttendance->check_in->format('H:i:s'),
            ], 400);
        }

        // Calculate distance from office
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            self::OFFICE_LATITUDE,
            self::OFFICE_LONGITUDE
        );

        // Check if within allowed distance
        if ($distance > self::ALLOWED_DISTANCE_METERS) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đang cách văn phòng ' . round($distance, 0) . ' mét. Không thể chấm công.',
                'distance' => round($distance, 2),
                'allowed_distance' => self::ALLOWED_DISTANCE_METERS,
            ], 400);
        }

        // Get location name from coordinates (reverse geocoding)
        $locationName = $this->getLocationName($request->latitude, $request->longitude);
        $ipAddress = $request->ip();

        // Create or update attendance record
        if ($existingAttendance) {
            $attendance = $existingAttendance;
        } else {
            $attendance = Attendance::firstOrCreate([
                'employee_id' => $employee->id,
                'date' => $today,
            ]);
        }

        $attendance->update([
            'check_in' => Carbon::now(),
            'check_in_latitude' => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'check_in_location' => $locationName,
            'check_in_ip_address' => $ipAddress,
            'check_in_distance' => round($distance, 2),
            'check_in_notes' => $request->notes,
            'status' => $this->determineStatus($today),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chấm công vào lúc ' . $attendance->check_in->format('H:i:s') . ' thành công!',
            'attendance' => $attendance,
            'distance' => round($distance, 2),
        ]);
    }

    /**
     * Check-out with location verification
     */
    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->firstOrFail();

        if (!$attendance->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa chấm công vào. Vui lòng chấm công vào trước.',
            ], 400);
        }

        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã chấm công ra lúc ' . $attendance->check_out->format('H:i:s'),
            ], 400);
        }

        // Calculate distance from office
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            self::OFFICE_LATITUDE,
            self::OFFICE_LONGITUDE
        );

        // Get location name from coordinates
        $locationName = $this->getLocationName($request->latitude, $request->longitude);
        $ipAddress = $request->ip();

        $attendance->update([
            'check_out' => Carbon::now(),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'check_out_location' => $locationName,
            'check_out_ip_address' => $ipAddress,
            'check_out_distance' => round($distance, 2),
            'check_out_notes' => $request->notes,
        ]);

        // Calculate all metrics after check-out
        $attendance = $this->calculationService->updateAttendanceMetrics($attendance);

        return response()->json([
            'success' => true,
            'message' => 'Chấm công ra lúc ' . $attendance->check_out->format('H:i:s') . ' thành công!',
            'attendance' => $attendance,
            'distance' => round($distance, 2),
            'metrics' => [
                'work_hours' => $attendance->work_hours,
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_hours' => $attendance->overtime_hours,
                'status' => $attendance->status_label,
            ],
        ]);
    }

    /**
     * Get attendance history for current employee
     */
    public function getAttendanceHistory(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'attendances' => $attendances,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Get office location settings
     */
    public function getOfficeLocation()
    {
        return response()->json([
            'success' => true,
            'office_latitude' => self::OFFICE_LATITUDE,
            'office_longitude' => self::OFFICE_LONGITUDE,
            'allowed_distance' => self::ALLOWED_DISTANCE_METERS,
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Get location name from coordinates (simple implementation)
     * In production, use Google Maps Reverse Geocoding API
     */
    private function getLocationName($latitude, $longitude)
    {
        // For now, return coordinates as location
        // TODO: Integrate with Google Maps API or OpenStreetMap API for actual location names
        return "Vị trí: " . round($latitude, 6) . ", " . round($longitude, 6);
    }

    /**
     * Determine attendance status based on check-in time
     */
    private function determineStatus($date)
    {
        $checkInTime = Carbon::now();
        $standardCheckInTime = Carbon::createFromTime(8, 0, 0); // 8:00 AM

        if ($checkInTime->greaterThan($standardCheckInTime)) {
            return 'late';
        }

        return 'present';
    }

    /**
     * Get monthly statistics for current employee
     */
    public function getMonthlyStatistics(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $stats = $this->calculationService->getMonthlyStatistics($employee->id, $month, $year);

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'statistics' => [
                'total_days_worked' => $stats['total_days_worked'],
                'total_days_absent' => $stats['total_days_absent'],
                'total_late_days' => $stats['total_late_days'],
                'total_early_leave_days' => $stats['total_early_leave_days'],
                'total_work_hours' => round($stats['total_work_hours'], 2),
                'total_late_minutes' => $stats['total_late_minutes'],
                'total_overtime_hours' => round($stats['total_overtime_hours'], 2),
                'average_work_hours_per_day' => round($stats['average_work_hours_per_day'], 2),
            ],
            'details' => $stats['attendances']->map(function ($attendance) {
                return $this->calculationService->getDailySummary($attendance);
            })->values(),
        ]);
    }

    /**
     * Get summary for today
     */
    public function getTodaySummary()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $today = Carbon::today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => true,
                'message' => 'Chưa có dữ liệu chấm công hôm nay',
                'summary' => null,
            ]);
        }

        $summary = $this->calculationService->getDailySummary($attendance);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Get monthly summary for dashboard
     */
    public function getMonthlySummary(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $stats = $this->calculationService->getMonthlyStatistics($employee->id, $month, $year);

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'summary' => [
                'worked_days' => $stats['total_days_worked'],
                'absent_days' => $stats['total_days_absent'],
                'late_days' => $stats['total_late_days'],
                'early_leave_days' => $stats['total_early_leave_days'],
                'total_hours' => round($stats['total_work_hours'], 2),
                'total_late_minutes' => $stats['total_late_minutes'],
                'overtime_hours' => round($stats['total_overtime_hours'], 2),
                'average_daily_hours' => round($stats['average_work_hours_per_day'], 2),
            ],
        ]);
    }

    /**
     * Get standard working times
     */
    public function getStandardTimes()
    {
        $times = AttendanceCalculationService::getStandardTimes();

        return response()->json([
            'success' => true,
            'standard_times' => $times,
        ]);
    }
}