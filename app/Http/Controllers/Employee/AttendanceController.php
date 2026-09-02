<?php

namespace App\Http\Controllers\Employee;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceCalculationService;
use App\Services\EmployeeAttendanceRecorder;
use App\Services\OfficeLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceCalculationService $calculationService,
        private EmployeeAttendanceRecorder $recorder,
        private OfficeLocationService $office,
    ) {
    }

    /**
     * Get today's attendance record for current employee
     */
    public function getTodayAttendance()
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

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
        return $this->punch($request, requireCheckIn: true);
    }

    /**
     * Check-out with location verification
     */
    public function checkOut(Request $request)
    {
        return $this->punch($request, requireCheckIn: false);
    }

    private function punch(Request $request, bool $requireCheckIn)
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today();
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($requireCheckIn && $existing?->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã chấm công vào hôm nay lúc '.$existing->check_in->format('H:i:s'),
            ], 400);
        }

        if (! $requireCheckIn && ! $existing?->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa chấm công vào. Vui lòng chấm công vào trước.',
            ], 400);
        }

        try {
            $result = $this->recorder->record(
                $employee,
                Auth::user(),
                (float) $data['latitude'],
                (float) $data['longitude'],
                $data['notes'] ?? null,
                'gps',
                (string) $request->ip(),
            );
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'attendance' => $result['attendance'],
            'distance' => $result['distance'],
            'metrics' => $result['metrics'] ?? null,
        ]);
    }

    /**
     * Get attendance history for current employee
     */
    public function getAttendanceHistory(Request $request)
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['adjustmentRequests' => fn ($q) => $q->where('status', 'pending')])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function (Attendance $attendance) {
                $payload = $attendance->toArray();
                $payload['pending_adjustment'] = $attendance->adjustmentRequests->isNotEmpty();

                return $payload;
            });

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
            ...$this->office->settings(),
        ]);
    }

    /**
     * Get monthly statistics for current employee
     */
    public function getMonthlyStatistics(Request $request)
    {
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

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
                'total_late_penalty_fee' => round($stats['total_late_penalty_fee'], 2),
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
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

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
        try {
            $employee = $this->recorder->resolveEmployee(Auth::user());
        } catch (AttendanceException $e) {
            return $e->toResponse();
        }

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
                'total_late_penalty_fee' => round($stats['total_late_penalty_fee'], 2),
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
