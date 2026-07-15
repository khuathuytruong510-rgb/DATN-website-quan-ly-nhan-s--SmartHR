<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Show attendance detail page
     */
    public function show($id)
    {
        // Eager load employee and approver + department
        $attendance = Attendance::with(['employee.department', 'approver'])->find($id);

        if (! $attendance) {
            abort(404);
        }

        // Ensure work hours and minutes are calculated
        if (! isset($attendance->work_hours) || $attendance->work_hours === null) {
            if ($attendance->check_in && $attendance->check_out) {
                $minutes = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
                $attendance->work_hours = round($minutes / 60, 2);
            } else {
                $attendance->work_hours = 0;
            }
        }

        // Format helper to display hours/minutes like "8 giờ 30 phút"
        $formatHours = function ($hours) {
            $totalMinutes = (int) round($hours * 60);
            $h = intdiv($totalMinutes, 60);
            $m = $totalMinutes % 60;
            if ($h > 0 && $m > 0) {
                return "{$h} giờ {$m} phút";
            }
            if ($h > 0) {
                return "{$h} giờ";
            }
            if ($m > 0) {
                return "{$m} phút";
            }
            return '0 phút';
        };

        $workHoursLabel = $formatHours($attendance->work_hours ?? 0);
        $overtimeLabel = $formatHours($attendance->overtime_hours ?? 0);

        return view('attendances.show', compact('attendance', 'workHoursLabel', 'overtimeLabel'));
    }
}
