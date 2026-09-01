<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminOrHr
{
    /** Kế toán chỉ được GHI các thao tác tính/chi lương, không ghi dữ liệu nhân sự. */
    private const ACCOUNTANT_WRITE_ROUTES = [
        'payroll.generate',
        'payroll.email.send_all',
        'payroll.email.send',
        'payroll.payment.confirm',
        'salary_payments.create',
        'salary_payments.update',
        'salary_payments.send_email',
        'salary_payments.pay',
        'salary_payments.action',
    ];

    /** Màn hình thuộc HR/GĐ — kế toán xem payroll/kỳ lương, không vào form sửa nguồn. */
    private const ACCOUNTANT_DENIED_ROUTES = [
        'hr-dashboard.index',
        'hr-dashboard.export',
        'employees.create',
        'employees.edit',
        'departments.create',
        'departments.edit',
        'contracts.create',
        'contracts.edit',
        'contracts.renew',
        'attendance.create',
        'attendance.edit',
        'leave_requests.create',
        'evaluations.create',
        'evaluations.edit',
        'payroll.issues.fix_form',
        'payroll.bank_requests.index',
        'promotion_requests.index',
        'promotion_requests.create',
        'promotion_requests.show',
    ];

    /** Form ghi dữ liệu HR/KT — Giám đốc xem, không mở màn hình sửa nguồn hay thanh toán. */
    private const DIRECTOR_DENIED_GET_ROUTES = [
        'employees.create',
        'employees.edit',
        'departments.create',
        'departments.edit',
        'contracts.create',
        'contracts.edit',
        'contracts.renew',
        'attendance.create',
        'attendance.edit',
        'leave_requests.create',
        'evaluations.create',
        'evaluations.edit',
        'payroll.issues.fix_form',
        'payroll.payment.show',
        'payroll.email.index',
        'promotion_requests.create',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || (! $user->is_admin && ! $user->is_hr && ! $user->is_accountant && ! $user->is_director)) {
            abort(403);
        }

        if ($user->is_director && ! $user->is_hr && ! $user->is_admin) {
            $routeName = $request->route()?->getName();
            if ($request->isMethodSafe() && in_array($routeName, self::DIRECTOR_DENIED_GET_ROUTES, true)) {
                abort(403, 'Giám đốc chỉ xem dữ liệu phục vụ phê duyệt, không dùng form ghi của HR hoặc Kế toán.');
            }
            if (! $request->isMethodSafe()) {
                $allowed = ['payroll.approve', 'payroll.approve_all', 'contracts.sign', 'notifications.store', 'promotion_requests.approve', 'promotion_requests.reject', 'deletion_requests.approve', 'deletion_requests.reject'];
                if (! in_array($routeName, $allowed, true)) {
                    abort(403, 'Giám đốc chỉ được phê duyệt cấp cao, không chỉnh sửa dữ liệu nhân sự hay cấu hình hệ thống.');
                }
            }
        }

        if ($user->is_accountant && ! $user->is_hr && ! $user->is_admin) {
            $routeName = $request->route()?->getName();
            if (in_array($routeName, self::ACCOUNTANT_DENIED_ROUTES, true)) {
                abort(403, 'Kế toán không dùng màn hình này. Dữ liệu nhân sự / duyệt thuộc HR hoặc Giám đốc.');
            }
        }

        if ($user->is_accountant && ! $request->isMethodSafe()) {
            if (! in_array($request->route()?->getName(), self::ACCOUNTANT_WRITE_ROUTES, true)) {
                abort(403, 'Kế toán chỉ được tính lương và thanh toán. Không được ghi dữ liệu nhân sự, hợp đồng, chấm công hay nghỉ phép.');
            }
        }

        return $next($request);
    }
}
