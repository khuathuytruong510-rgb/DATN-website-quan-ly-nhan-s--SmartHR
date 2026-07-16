<?php

use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\Web\EmployeeAttendanceController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\SmartHrController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [SmartHrController::class, 'showLogin'])->name('login');
    Route::post('/login', [SmartHrController::class, 'login'])->name('login.store');
    Route::get('/register', [SmartHrController::class, 'showRegister'])->name('register');
    Route::post('/register', [SmartHrController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [SmartHrController::class, 'dashboard'])->name('dashboard');

    Route::post('/logout', [SmartHrController::class, 'logout'])->name('logout');

    Route::get('/admin', [SmartHrController::class, 'dashboard'])
        ->name('admin.dashboard');

    Route::middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
        Route::get('/accounts', [SmartHrController::class, 'accounts'])->name('accounts.index');
        Route::get('/accounts/create', [SmartHrController::class, 'createAccount'])->name('accounts.create');
        Route::post('/accounts', [SmartHrController::class, 'storeAccount'])->name('accounts.store');
        Route::get('/accounts/{user}/edit', [SmartHrController::class, 'editAccount'])->name('accounts.edit');
        Route::put('/accounts/{user}', [SmartHrController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{user}', [SmartHrController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/accounts/{user}/toggle-lock', [SmartHrController::class, 'toggleLockAccount'])->name('accounts.toggle_lock');
        Route::post('/accounts/{user}/impersonate', [SmartHrController::class, 'impersonate'])->name('accounts.impersonate');
        Route::get('/permissions', [SmartHrController::class, 'permissions'])->name('permissions.index');
        Route::put('/permissions/{user}', [SmartHrController::class, 'updatePermissions'])->name('permissions.update');
        Route::get('/system-logs', [SmartHrController::class, 'systemLogs'])->name('system_logs.index');
        Route::get('/settings', [SmartHrController::class, 'settings'])->name('settings.index');
    });

    // Stop impersonation (any authenticated user can stop if impersonating)
    Route::post('/impersonation/stop', [SmartHrController::class, 'stopImpersonation'])->name('impersonation.stop');

    Route::get('/me', [EmployeeController::class, 'dashboard'])->name('me.dashboard')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/profile', [EmployeeController::class, 'profile'])->name('me.profile')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    
    Route::get('/me/profile/edit', [EmployeeController::class, 'editProfile'])->name('me.profile.edit')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::put('/me/profile', [EmployeeController::class, 'updateProfile'])->name('me.profile.update')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/trainings', [EmployeeController::class, 'trainings'])->name('me.trainings')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/rewards', [EmployeeController::class, 'rewards'])->name('me.rewards')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/attendance', [EmployeeController::class, 'attendanceIndex'])->name('me.attendance')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/attendance/create', [EmployeeController::class, 'attendanceCreate'])->name('me.attendance.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/attendance', [EmployeeController::class, 'attendanceStore'])->name('me.attendance.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::put('/me/attendance/{attendance}', [EmployeeController::class, 'attendanceUpdate'])->name('me.attendance.update')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/contracts', [EmployeeController::class, 'contracts'])->name('me.contracts');
    Route::get('/me/payroll', [EmployeeController::class, 'payrolls'])->name('me.payrolls')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/payroll/{payroll}/confirm', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'confirm'])->name('me.payroll.confirm')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/payroll/{payroll}/report-issue', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'reportIssue'])->name('me.payroll.report_issue')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/evaluations', [EmployeeController::class, 'evaluations'])->name('me.evaluations')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/benefits', [EmployeeController::class, 'benefits'])->name('me.benefits')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/leave-requests', [EmployeeController::class, 'leaveIndex'])->name('me.leave_requests')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/leave-requests/create', [EmployeeController::class, 'leaveCreate'])->name('me.leave_requests.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/leave-requests', [EmployeeController::class, 'leaveStore'])->name('me.leave_requests.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/notifications', [EmployeeController::class, 'notifications'])->name('me.notifications')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    Route::get('/me/schedule', [EmployeeController::class, 'schedule'])->name('me.schedule')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Employee salary advances (own)
    Route::get('/me/salary-advances', [\App\Http\Controllers\Web\SalaryAdvanceController::class, 'index'])->name('me.salary_advances')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/salary-advances/create', [\App\Http\Controllers\Web\SalaryAdvanceController::class, 'create'])->name('me.salary_advances.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/salary-advances', [\App\Http\Controllers\Web\SalaryAdvanceController::class, 'store'])->name('me.salary_advances.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Employee support requests
    Route::get('/me/support-requests', [\App\Http\Controllers\Web\SupportRequestController::class, 'index'])->name('me.support_requests')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/support-requests/create', [\App\Http\Controllers\Web\SupportRequestController::class, 'create'])->name('me.support_requests.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/support-requests', [\App\Http\Controllers\Web\SupportRequestController::class, 'store'])->name('me.support_requests.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/support-requests/{supportRequest}', [\App\Http\Controllers\Web\SupportRequestController::class, 'show'])->name('me.support_requests.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Overtime requests
    Route::get('/me/overtime-requests', [\App\Http\Controllers\Web\OvertimeRequestController::class, 'index'])->name('me.overtime_requests')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/overtime-requests/create', [\App\Http\Controllers\Web\OvertimeRequestController::class, 'create'])->name('me.overtime_requests.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/overtime-requests', [\App\Http\Controllers\Web\OvertimeRequestController::class, 'store'])->name('me.overtime_requests.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/overtime-requests/{overtimeRequest}', [\App\Http\Controllers\Web\OvertimeRequestController::class, 'show'])->name('me.overtime_requests.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Change password
    Route::get('/me/change-password', [EmployeeController::class, 'showChangePassword'])->name('me.password.change')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/change-password', [EmployeeController::class, 'updatePassword'])->name('me.password.update')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Activity logs
    Route::get('/me/activity-logs', [\App\Http\Controllers\Web\ActivityLogController::class, 'index'])->name('me.activity_logs')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Employee-facing salary history (own records)
    Route::get('/me/salary-histories', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'meIndex'])->name('me.salary_histories');

    Route::middleware(\App\Http\Middleware\EnsureAdminOrHr::class)->group(function () {
        Route::get('/positions', [SmartHrController::class, 'positions'])->name('positions.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');

        Route::get('/departments', [SmartHrController::class, 'departments'])->name('departments.index');
        Route::get('/departments/create', [SmartHrController::class, 'createDepartment'])->name('departments.create');
        Route::post('/departments', [SmartHrController::class, 'storeDepartment'])->name('departments.store');
        Route::get('/departments/{department}/edit', [SmartHrController::class, 'editDepartment'])->name('departments.edit');
        Route::get('/departments/{department}', [SmartHrController::class, 'showDepartment'])->name('departments.show');
        Route::put('/departments/{department}', [SmartHrController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [SmartHrController::class, 'destroyDepartment'])->name('departments.destroy');

        Route::get('/employees', [SmartHrController::class, 'employees'])->name('employees.index');
        Route::get('/employees/create', [SmartHrController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [SmartHrController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [SmartHrController::class, 'editEmployee'])->name('employees.edit');
        Route::get('/employees/{employee}', [SmartHrController::class, 'showEmployee'])->name('employees.show');
        Route::put('/employees/{employee}', [SmartHrController::class, 'updateEmployee'])->name('employees.update');
        Route::delete('/employees/{employee}', [SmartHrController::class, 'destroyEmployee'])->name('employees.destroy');

        Route::get('/contracts', [SmartHrController::class, 'contracts'])->name('contracts.index');
        Route::get('/contracts/create', [SmartHrController::class, 'createContract'])->name('contracts.create');
        Route::post('/contracts', [SmartHrController::class, 'storeContract'])->name('contracts.store');
        Route::get('/contracts/{contract}/edit', [SmartHrController::class, 'editContract'])->name('contracts.edit');
        Route::get('/contracts/{contract}', [SmartHrController::class, 'showContract'])->name('contracts.show');
        Route::put('/contracts/{contract}', [SmartHrController::class, 'updateContract'])->name('contracts.update');
        Route::delete('/contracts/{contract}', [SmartHrController::class, 'destroyContract'])->name('contracts.destroy');

        Route::get('/attendance', [SmartHrController::class, 'attendance'])->name('attendance.index');
        Route::get('/attendance/create', [SmartHrController::class, 'createAttendance'])->name('attendance.create');
        Route::post('/attendance', [SmartHrController::class, 'storeAttendance'])->name('attendance.store');
        Route::get('/attendance/{attendance}/edit', [SmartHrController::class, 'editAttendance'])->name('attendance.edit');
        Route::put('/attendance/{attendance}', [SmartHrController::class, 'updateAttendance'])->name('attendance.update');
        Route::delete('/attendance/{attendance}', [SmartHrController::class, 'destroyAttendance'])->name('attendance.destroy');
        
        Route::middleware(\App\Http\Middleware\EnsureAdminOrHr::class)->group(function () {

            // Danh sách bảng lương
            Route::get('/payroll', [PayrollController::class, 'index'])
                ->name('payroll.index');

            // Tính lương
            Route::post('/payroll/generate', [PayrollController::class, 'generate'])
                ->name('payroll.generate');

            // Tạo mới (nếu có)
            Route::get('/payroll/create', [PayrollController::class, 'create'])
                ->name('payroll.create');

            Route::post('/payroll', [PayrollController::class, 'store'])
                ->name('payroll.store');

            // Chi tiết
            Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])
                ->name('payroll.show');

            // Chỉnh sửa
            Route::get('/payroll/{payroll}/edit', [PayrollController::class, 'edit'])
                ->name('payroll.edit');

            Route::put('/payroll/{payroll}', [PayrollController::class, 'update'])
                ->name('payroll.update');

            // Duyệt bảng lương
            Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])
                ->name('payroll.approve');

            // Duyệt bảng lương và tạo thanh toán
            Route::post('/payroll/{payroll}/approve-with-payment', [PayrollController::class, 'approveWithPayment'])
                ->name('payroll.approve_with_payment');

            // Đánh dấu đã thanh toán
            Route::post('/payroll/{payroll}/paid', [PayrollController::class, 'paid'])
                ->name('payroll.paid');

            // Gửi xác nhận
            Route::post('/payroll/{payroll}/send-confirmation', [PayrollController::class, 'sendConfirmation'])
                ->name('payroll.send_confirmation');

            // Gửi phiếu lương
            Route::get('/payroll/email', [\App\Http\Controllers\Web\PayrollEmailController::class, 'index'])
                ->name('payroll.email.index');

            Route::post('/payroll/email/send/{payroll}', [\App\Http\Controllers\Web\PayrollEmailController::class, 'send'])
                ->name('payroll.email.send');

            Route::post('/payroll/email/send-all', [\App\Http\Controllers\Web\PayrollEmailController::class, 'sendAll'])
                ->name('payroll.email.send_all');

            // Xóa
            Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])
                ->name('payroll.destroy');
        });

        Route::get('/evaluations', [SmartHrController::class, 'evaluations'])->name('evaluations.index');
        Route::get('/evaluations/create', [SmartHrController::class, 'createEvaluation'])->name('evaluations.create');
        Route::post('/evaluations', [SmartHrController::class, 'storeEvaluation'])->name('evaluations.store');
        Route::get('/evaluations/{evaluation}', [SmartHrController::class, 'showEvaluation'])->name('evaluations.show');
        Route::get('/evaluations/{evaluation}/edit', [SmartHrController::class, 'editEvaluation'])->name('evaluations.edit');
        Route::put('/evaluations/{evaluation}', [SmartHrController::class, 'updateEvaluation'])->name('evaluations.update');
        Route::post('/evaluations/{evaluation}/approve', [SmartHrController::class, 'approveEvaluation'])->name('evaluations.approve');
        Route::delete('/evaluations/{evaluation}', [SmartHrController::class, 'destroyEvaluation'])->name('evaluations.destroy');
        
        Route::get('/benefits', [SmartHrController::class, 'benefits'])->name('benefits.index');
        Route::get('/benefits/export', [SmartHrController::class, 'exportBenefits'])->name('benefits.export');
        Route::get('/benefits/create', [SmartHrController::class, 'createBenefit'])->name('benefits.create');
        Route::post('/benefits', [SmartHrController::class, 'storeBenefit'])->name('benefits.store');
        Route::get('/benefits/assignments', [SmartHrController::class, 'benefitAssignments'])->name('benefits.assignments.index');
        Route::get('/benefits/assignments/create', [SmartHrController::class, 'createBenefitAssignment'])->name('benefits.assignments.create');
        Route::post('/benefits/assignments', [SmartHrController::class, 'storeBenefitAssignment'])->name('benefits.assignments.store');
        Route::get('/benefits/assignments/{assignment}/edit', [SmartHrController::class, 'editBenefitAssignment'])->name('benefits.assignments.edit');
        Route::put('/benefits/assignments/{assignment}', [SmartHrController::class, 'updateBenefitAssignment'])->name('benefits.assignments.update');
        Route::delete('/benefits/assignments/{assignment}', [SmartHrController::class, 'destroyBenefitAssignment'])->name('benefits.assignments.destroy');
        Route::get('/benefits/{benefit}', [SmartHrController::class, 'showBenefit'])->name('benefits.show');
        Route::get('/benefits/{benefit}/edit', [SmartHrController::class, 'editBenefit'])->name('benefits.edit');
        Route::put('/benefits/{benefit}', [SmartHrController::class, 'updateBenefit'])->name('benefits.update');
        Route::post('/benefits/{benefit}/approve', [SmartHrController::class, 'approveBenefit'])->name('benefits.approve');
        Route::delete('/benefits/{benefit}', [SmartHrController::class, 'destroyBenefit'])->name('benefits.destroy');
        
        Route::get('/leave-requests', [SmartHrController::class, 'leaveRequests'])->name('leave_requests.index');
        Route::get('/leave-requests/create', [SmartHrController::class, 'createLeaveRequest'])->name('leave_requests.create');
        Route::post('/leave-requests', [SmartHrController::class, 'storeLeaveRequest'])->name('leave_requests.store');
        Route::post('/leave-requests/{leaveRequest}/approve', [SmartHrController::class, 'approveLeaveRequest'])->name('leave_requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [SmartHrController::class, 'rejectLeaveRequest'])->name('leave_requests.reject');
        Route::delete('/leave-requests/{leaveRequest}', [SmartHrController::class, 'destroyLeaveRequest'])->name('leave_requests.destroy');
        
        // Salary history detail
        Route::get('/salary-histories/{salaryHistory}', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'show'])->name('salary_histories.show');
        // Route to view salary history by payroll
        Route::get('/payroll/{payroll}/salary-history', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'byPayroll'])->name('payroll.salary_history');
        // Salary history list
        Route::get('/salary-histories', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'index'])->name('salary_histories.index');

        // HR/Admin manage salary advances
        Route::get('/salary-advances', [\App\Http\Controllers\Web\SalaryAdvanceController::class, 'index'])->name('salary_advances.index');
        Route::post('/salary-advances/{salaryAdvance}/approve', [\App\Http\Controllers\Web\SalaryAdvanceController::class, 'approve'])->name('salary_advances.approve');

        // Salary payments (accountant)
        Route::get('/salary-payments', [\App\Http\Controllers\Web\SalaryPaymentController::class, 'index'])->name('salary_payments.index');
        Route::get('/salary-payments/select-payroll', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'selectPayroll'])->name('salary_payments.select_payroll');
        Route::post('/salary-payments/create/{payroll}', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'create'])->name('salary_payments.create');
        Route::get('/salary-payments/{salaryPayment}', [\App\Http\Controllers\Web\SalaryPaymentController::class, 'show'])->name('salary_payments.show');
        Route::get('/salary-payments/{salaryPayment}/edit', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'edit'])->name('salary_payments.edit');
        Route::put('/salary-payments/{salaryPayment}', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'update'])->name('salary_payments.update');
        Route::post('/salary-payments/{salaryPayment}/send-email', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'sendEmail'])->name('salary_payments.send_email');
        Route::post('/salary-payments/{salaryPayment}/pay', [\App\Http\Controllers\Web\SalaryPaymentController::class, 'pay'])->name('salary_payments.pay');
        Route::post('/salary-payments/{salaryPayment}/action', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'pay'])->name('salary_payments.action');
        Route::delete('/salary-payments/{salaryPayment}', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'destroy'])->name('salary_payments.destroy');
        Route::get('/salary-payments/export/{month}/{year}', [\App\Http\Controllers\Accountant\SalaryPaymentController::class, 'export'])->name('salary_payments.export');

        // Attendance detail (web)
        Route::get('/attendances/{attendance}', [\App\Http\Controllers\Web\AttendanceController::class, 'show'])->name('attendances.show');
    });

    // Accountant portal (only for accountant role)
    Route::middleware(\App\Http\Middleware\EnsureAccountant::class)->group(function () {
        Route::get('/accountant', [\App\Http\Controllers\Web\AccountantController::class, 'dashboard'])->name('accountant.dashboard');
        Route::get('/accountant/payroll', [\App\Http\Controllers\Web\AccountantController::class, 'payrollIndex'])->name('accountant.payroll.index');
        Route::get('/accountant/payroll/generate', [\App\Http\Controllers\Web\AccountantController::class, 'payrollGenerate'])->name('accountant.payroll.generate');
        Route::post('/accountant/payroll/generate', [\App\Http\Controllers\Web\AccountantController::class, 'generatePayroll'])->name('accountant.payroll.generate_post');
        Route::get('/accountant/payroll/send', [\App\Http\Controllers\Web\AccountantController::class, 'payrollSend'])->name('accountant.payroll.send');
        Route::post('/accountant/payroll/send-all', [\App\Http\Controllers\Web\AccountantController::class, 'sendAllPayrolls'])->name('accountant.payroll.send_all');
        Route::get('/accountant/payroll/feedback', [\App\Http\Controllers\Web\AccountantController::class, 'payrollFeedback'])->name('accountant.payroll.feedback');
        Route::get('/accountant/leave-requests', [\App\Http\Controllers\Web\AccountantController::class, 'leaveRequests'])->name('accountant.leave_requests');
        Route::get('/accountant/leave-requests/create', [\App\Http\Controllers\Web\AccountantController::class, 'createLeaveRequest'])->name('accountant.leave_requests.create');
        Route::post('/accountant/leave-requests', [\App\Http\Controllers\Web\AccountantController::class, 'storeLeaveRequest'])->name('accountant.leave_requests.store');
        Route::post('/accountant/leave-requests/{leaveRequest}/approve', [\App\Http\Controllers\Web\AccountantController::class, 'approveLeaveRequest'])->name('accountant.leave_requests.approve');
        Route::post('/accountant/leave-requests/{leaveRequest}/reject', [\App\Http\Controllers\Web\AccountantController::class, 'rejectLeaveRequest'])->name('accountant.leave_requests.reject');
        Route::get('/accountant/payroll/{payroll}', [\App\Http\Controllers\Web\AccountantController::class, 'payrollShow'])->name('accountant.payroll.show');
        Route::post('/accountant/payroll/{payroll}/send-email', [\App\Http\Controllers\Web\AccountantController::class, 'sendPayrollEmail'])->name('accountant.payroll.send_email');

        Route::get('/accountant/allowances', [\App\Http\Controllers\Web\AccountantController::class, 'allowances'])->name('accountant.allowances');
        Route::get('/accountant/deductions', [\App\Http\Controllers\Web\AccountantController::class, 'deductions'])->name('accountant.deductions');
        Route::get('/accountant/bonuses', [\App\Http\Controllers\Web\AccountantController::class, 'bonuses'])->name('accountant.bonuses');

        Route::get('/accountant/reports', [\App\Http\Controllers\Web\AccountantController::class, 'reports'])->name('accountant.reports');
        Route::get('/accountant/export', [\App\Http\Controllers\Web\AccountantController::class, 'export'])->name('accountant.export');

        Route::get('/accountant/activity-logs', [\App\Http\Controllers\Web\AccountantController::class, 'activityLogs'])->name('accountant.activity_logs');
        // Accountant salary payments management
        Route::get('/accountant/salary-payments', [\App\Http\Controllers\Web\SalaryPaymentController::class, 'index'])->name('accountant.salary_payments.index');
        Route::post('/accountant/salary-payments/{salaryPayment}/pay', [\App\Http\Controllers\Web\SalaryPaymentController::class, 'pay'])->name('accountant.salary_payments.pay');
        Route::post('/accountant/payroll/{payroll}/recalculate', [\App\Http\Controllers\Web\AccountantController::class, 'recalculatePayroll'])->name('accountant.payroll.recalculate');
        Route::post('/accountant/payroll/{payroll}/lock', [\App\Http\Controllers\Web\AccountantController::class, 'lockPayroll'])->name('accountant.payroll.lock');
        Route::post('/accountant/payroll/{payroll}/unlock', [\App\Http\Controllers\Web\AccountantController::class, 'unlockPayroll'])->name('accountant.payroll.unlock');
        Route::get('/accountant/profile', [\App\Http\Controllers\Web\AccountantController::class, 'profile'])->name('accountant.profile');
        Route::get('/accountant/change-password', [\App\Http\Controllers\Web\AccountantController::class, 'showChangePassword'])->name('accountant.password.change');
        Route::post('/accountant/change-password', [\App\Http\Controllers\Web\AccountantController::class, 'updatePassword'])->name('accountant.password.update');
    });
});

require __DIR__ . '/attendance-web.php';


