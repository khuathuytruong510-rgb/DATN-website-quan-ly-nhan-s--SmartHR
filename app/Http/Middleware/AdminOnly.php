<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Employee;

class AdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu user không đăng nhập, redirect tới login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Nếu user có Employee record = nhân viên, không cho vào admin
        $isEmployee = Employee::where('user_id', $request->user()->id)->exists();
        
        if ($isEmployee) {
            return redirect()->route('employee.attendance.simple')
                ->with('error', 'Bạn không có quyền truy cập khu vực này.');
        }

        return $next($request);
    }
}
