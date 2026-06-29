<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class EmployeeAttendanceController extends Controller
{
    /**
     * Show employee attendance page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Verify user is an employee
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn không có quyền truy cập trang này.');
        }

        return view('employee.attendance.index', [
            'employee' => $employee,
        ]);
    }
}