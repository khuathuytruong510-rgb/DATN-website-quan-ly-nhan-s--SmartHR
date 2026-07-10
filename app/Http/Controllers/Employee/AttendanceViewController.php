<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AttendanceViewController extends Controller
{
    /**
     * Show attendance dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('employee.attendance.dashboard');
    }

    /**
     * Show attendance history page
     */
    public function history()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('employee.attendance.history');
    }

    /**
     * Show attendance statistics page
     */
    public function statistics()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('employee.attendance.statistics');
    }
}
