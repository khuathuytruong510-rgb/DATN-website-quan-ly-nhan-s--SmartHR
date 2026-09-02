<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAdminOrHr
{
    /** Cổng /me dành cho nhân viên; HR/Kế toán có hồ sơ vẫn dùng được phần cá nhân. */
    private const STAFF_SELF_SERVICE = [
        'me.profile',
        'me.profile.*',
        'me.leave_requests',
        'me.leave_requests.*',
        'me.overtime_requests',
        'me.overtime_requests.*',
        'me.attendance',
        'me.attendance.*',
        'me.notifications',
        'me.notifications.*',
        'me.transfers.*',
        'me.payrolls',
        'me.payroll.*',
        'me.salary_histories',
        'me.salary_histories.*',
        'me.contracts',
        'me.contracts.*',
        'me.evaluations',
        'me.benefits',
        'me.schedule',
        'me.schedule.*',
        'me.support_requests',
        'me.support_requests.*',
        'me.trainings',
        'me.rewards',
        'me.payment_history',
        'me.salary_advances',
        'me.salary_advances.*',
        'me.password.change',
        'me.password.*',
        'me.activity_logs',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isStaffUser()) {
            $staffSelfService = ($user->is_hr || $user->is_accountant)
                && ! $user->is_admin
                && $user->linkedEmployee()
                && $request->routeIs(self::STAFF_SELF_SERVICE);

            if (! $staffSelfService) {
                if ($user->is_accountant && ! $user->is_hr && ! $user->is_admin && ! $user->is_director) {
                    return redirect()->route('accountant.dashboard');
                }

                return redirect()->route('dashboard');
            }
        }

        if ($user && ! $user->linkedEmployee() && ! $request->routeIs('me.unlinked')) {
            return redirect()->route('me.unlinked');
        }

        return $next($request);
    }
}
