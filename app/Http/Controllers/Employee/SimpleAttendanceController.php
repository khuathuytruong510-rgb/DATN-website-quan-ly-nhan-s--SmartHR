<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceCalculationService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SimpleAttendanceController extends Controller
{
    private AttendanceCalculationService $calculationService;

    public function __construct(AttendanceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Display simple attendance page
     */
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $today = Carbon::today();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return view('employee.attendance.simple', compact('employee', 'attendance', 'today'));
    }

    /**
     * Simple check-in/out - one button for both
     */
    public function checkAttendance()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        
        $today = Carbon::today();
        $now = Carbon::now();

        // Get or create today's attendance record
        $attendance = Attendance::firstOrCreate([
            'employee_id' => $employee->id,
            'date' => $today,
        ]);

        // Determine if this is check-in or check-out
        if (!$attendance->check_in) {
            // First press - Check In
            $attendance->update([
                'check_in' => $now,
            ]);

            $message = 'Chấm công vào lúc ' . $now->format('H:i:s') . ' thành công!';
            $status = 'checked_in';
        } elseif (!$attendance->check_out) {
            // Second press - Check Out + Calculate metrics
            $attendance->update([
                'check_out' => $now,
            ]);

            // Calculate all metrics after check-out
            $attendance = $this->calculationService->updateAttendanceMetrics($attendance);

            $message = 'Chấm công ra lúc ' . $now->format('H:i:s') . ' thành công!';
            $status = 'checked_out';
        } else {
            // Already checked in and out
            $message = 'Bạn đã hoàn thành chấm công hôm nay.';
            $status = 'completed';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $status,
            'attendance' => [
                'check_in' => $attendance->check_in?->format('H:i:s'),
                'check_out' => $attendance->check_out?->format('H:i:s'),
                'work_hours' => $attendance->work_hours,
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_hours' => $attendance->overtime_hours,
                'status_label' => $attendance->status_label ?? $attendance->status,
            ],
        ]);
    }

    /**
     * Get today's attendance status
     */
    public function getTodayStatus()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $today = Carbon::today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $status = 'not_checked';
        $checkInTime = null;
        $checkOutTime = null;

        if ($attendance) {
            if ($attendance->check_in && $attendance->check_out) {
                $status = 'completed';
                $checkInTime = $attendance->check_in->format('H:i:s');
                $checkOutTime = $attendance->check_out->format('H:i:s');
            } elseif ($attendance->check_in) {
                $status = 'working';
                $checkInTime = $attendance->check_in->format('H:i:s');
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'check_in' => $checkInTime,
            'check_out' => $checkOutTime,
            'attendance' => $attendance,
        ]);
    }
}
