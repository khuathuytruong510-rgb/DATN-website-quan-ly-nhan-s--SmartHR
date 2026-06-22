<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if user is NOT an employee (meaning they're an admin)
        $employee = Employee::where('user_id', Auth::id())->first();
        
        if ($employee) {
            // User is an employee, redirect to employee page
            return redirect()->route('employee.attendance')
                ->with('error', 'Bạn không có quyền truy cập trang này. Vui lòng sử dụng trang nhân viên.');
        }

        return $next($request);
    }
}
