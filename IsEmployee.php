<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class IsEmployee
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

        // Check if user IS an employee
        $employee = Employee::where('user_id', Auth::id())->first();
        
        if (!$employee) {
            // User is not an employee, redirect to admin dashboard
            return redirect()->route('dashboard')
                ->with('error', 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}