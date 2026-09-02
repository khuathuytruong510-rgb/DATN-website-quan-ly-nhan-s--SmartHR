<?php

use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\HR\PromotionRequestController;
use App\Http\Controllers\Web\EmployeeAttendanceController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\DeletionRequestController;
use App\Http\Controllers\Web\HrSupportRequestController;
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

// Xác nhận bảng lương qua email (không cần đăng nhập)
Route::get('/payroll/confirm/{token}', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'confirmByToken'])
    ->name('payroll.confirm.token');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [SmartHrController::class, 'dashboard'])->name('dashboard');

    Route::post('/logout', [SmartHrController::class, 'logout'])->name('logout');

    // API: Lấy thông tin vị trí theo ID (must be before {name})
    Route::get('/api/positions/id/{id}', [SmartHrController::class, 'getPositionById'])->name('api.positions.get_by_id')->middleware(\App\Http\Middleware\EnsureAdminOrHr::class);
    // API: Lấy thông tin vị trí theo tên
    Route::get('/api/positions/{name}', [SmartHrController::class, 'getPositionByName'])->name('api.positions.get')->middleware(\App\Http\Middleware\EnsureAdminOrHr::class);
    // API: Tạo mã nhân viên tiếp theo
    Route::get('/api/employees/next-code', [SmartHrController::class, 'getNextEmployeeCode'])->name('api.employees.next_code')->middleware(\App\Http\Middleware\EnsureAdminOrHr::class);

    Route::get('/admin', [SmartHrController::class, 'dashboard'])
        ->name('admin.dashboard');

    Route::middleware(\App\Http\Middleware\EnsureHrOrAdmin::class)->group(function () {
        Route::get('/accounts', [SmartHrController::class, 'accounts'])->name('accounts.index');
        Route::get('/accounts/create', [SmartHrController::class, 'createAccount'])->name('accounts.create');
        Route::post('/accounts', [SmartHrController::class, 'storeAccount'])->name('accounts.store');
        Route::get('/accounts/{user}/edit', [SmartHrController::class, 'editAccount'])->name('accounts.edit');
        Route::put('/accounts/{user}', [SmartHrController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{user}', [SmartHrController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/accounts/{user}/toggle-lock', [SmartHrController::class, 'toggleLockAccount'])->name('accounts.toggle_lock');
        Route::post('/accounts/{user}/impersonate', [SmartHrController::class, 'impersonate'])->name('accounts.impersonate');
    });

    Route::middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
        Route::get('/permissions', [SmartHrController::class, 'permissions'])->name('permissions.index');
        Route::put('/permissions/{user}', [SmartHrController::class, 'updatePermissions'])->name('permissions.update');
        Route::get('/director-succession', [\App\Http\Controllers\Web\DirectorSuccessionController::class, 'index'])->name('director_succession.index');
        Route::get('/director-succession/nguoi-moi', [\App\Http\Controllers\Web\DirectorSuccessionController::class, 'prepareNew'])->name('director_succession.prepare_new');
        Route::post('/director-succession', [\App\Http\Controllers\Web\DirectorSuccessionController::class, 'store'])->name('director_succession.store');
        Route::get('/system-logs', [SmartHrController::class, 'systemLogs'])->name('system_logs.index');
        Route::get('/settings', [SmartHrController::class, 'settings'])->name('settings.index');
        Route::get('/admin/notifications', [NotificationController::class, 'adminIndex'])->name('admin.notifications.index');
    });

    // Stop impersonation (any authenticated user can stop if impersonating)
    Route::post('/impersonation/stop', [SmartHrController::class, 'stopImpersonation'])->name('impersonation.stop');

    Route::get('/me/unlinked', [EmployeeController::class, 'unlinked'])->name('me.unlinked')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me', [EmployeeController::class, 'dashboard'])->name('me.dashboard')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/profile', [EmployeeController::class, 'profile'])->name('me.profile')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    
    Route::get('/me/profile/edit', [EmployeeController::class, 'editProfile'])->name('me.profile.edit')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::put('/me/profile', [EmployeeController::class, 'updateProfile'])->name('me.profile.update')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/trainings', [EmployeeController::class, 'trainings'])->name('me.trainings')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/rewards', [EmployeeController::class, 'rewards'])->name('me.rewards')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/attendance', [EmployeeController::class, 'attendanceIndex'])->name('me.attendance')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/attendance/create', [EmployeeController::class, 'attendanceCreate'])->name('me.attendance.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/attendance', [EmployeeController::class, 'attendanceStore'])->name('me.attendance.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/attendance/{attendance}/adjust', [EmployeeController::class, 'requestAttendanceAdjustment'])->name('me.attendance.adjust')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::put('/me/attendance/{attendance}', [EmployeeController::class, 'attendanceUpdate'])->name('me.attendance.update')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['patch', 'delete'], '/me/attendance/{attendance}', fn () => abort(403, 'Không được sửa hoặc xóa bản ghi chấm công.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/payroll/{payroll}', fn () => abort(403, 'Nhân viên không được sửa trạng thái bảng lương.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/contracts/{contract}', fn () => abort(403, 'Nhân viên không được sửa hợp đồng.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/leave-requests/{leaveRequest}', fn () => abort(403, 'Không được sửa hoặc xóa đơn nghỉ. Dùng hủy đơn.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/overtime-requests/{overtimeRequest}', fn () => abort(403, 'Không được sửa hoặc xóa đăng ký tăng ca.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/support-requests/{supportRequest}', fn () => abort(403, 'Không được sửa trạng thái hoặc xóa yêu cầu hỗ trợ.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::match(['put', 'patch', 'delete'], '/me/salary-histories/{salaryHistory}', fn () => abort(403, 'Không được sửa lịch sử lương.'))->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/contracts', [EmployeeController::class, 'contracts'])->name('me.contracts')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/contracts/{contract}/sign', [EmployeeController::class, 'signContract'])->name('me.contracts.sign')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/contracts/{contract}/document', [\App\Http\Controllers\Web\ContractEsignController::class, 'document'])->name('me.contracts.document')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/payroll', [EmployeeController::class, 'payrolls'])->name('me.payrolls')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/payroll/{payroll}/history', [EmployeeController::class, 'payrollHistory'])->name('me.payroll.history')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/payroll/{payroll}', [EmployeeController::class, 'payrollShow'])->name('me.payroll.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/contracts/{contract}', [EmployeeController::class, 'contractShow'])->name('me.contracts.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/attendance/{attendance}', [EmployeeController::class, 'attendanceShow'])->name('me.attendance.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/payroll/{payroll}/confirm', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'confirm'])->name('me.payroll.confirm')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/payroll/{payroll}/report-issue', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'reportIssue'])->name('me.payroll.report_issue')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/payroll/bank-change-request', [\App\Http\Controllers\Web\PayrollConfirmationController::class, 'requestBankChange'])->name('me.payroll.bank_change')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/evaluations', [EmployeeController::class, 'evaluations'])->name('me.evaluations')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/benefits', [EmployeeController::class, 'benefits'])->name('me.benefits')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/leave-requests', [EmployeeController::class, 'leaveIndex'])->name('me.leave_requests')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/leave-requests/create', [EmployeeController::class, 'leaveCreate'])->name('me.leave_requests.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/leave-requests', [EmployeeController::class, 'leaveStore'])->name('me.leave_requests.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/leave-requests/{leaveRequest}/cancel', [EmployeeController::class, 'cancelLeave'])->name('me.leave_requests.cancel')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/notifications', [EmployeeController::class, 'notifications'])->name('me.notifications')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/notifications/{notification}/read', [EmployeeController::class, 'markNotificationRead'])->name('me.notifications.read')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/transfers/{deletionRequest}/feedback', [EmployeeController::class, 'submitTransferFeedback'])->name('me.transfers.feedback')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    Route::get('/me/schedule', [EmployeeController::class, 'schedule'])->name('me.schedule')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Employee payment history
    Route::get('/me/payment-history', [EmployeeController::class, 'paymentHistory'])->name('me.payment_history')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    Route::get('/me/salary-advances', fn () => redirect()->route('me.payrolls'))->name('me.salary_advances')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/salary-advances/create', fn () => redirect()->route('me.payrolls'))->name('me.salary_advances.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/salary-advances', fn () => redirect()->route('me.payrolls'))->name('me.salary_advances.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    // Employee support requests
    Route::get('/me/support-requests', [\App\Http\Controllers\Web\SupportRequestController::class, 'index'])->name('me.support_requests')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/support-requests/create', [\App\Http\Controllers\Web\SupportRequestController::class, 'create'])->name('me.support_requests.create')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/support-requests', [\App\Http\Controllers\Web\SupportRequestController::class, 'store'])->name('me.support_requests.store')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/support-requests/{supportRequest}', [\App\Http\Controllers\Web\SupportRequestController::class, 'show'])->name('me.support_requests.show')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/support-requests/{supportRequest}/follow-up', [\App\Http\Controllers\Web\SupportRequestController::class, 'followUp'])->name('me.support_requests.follow_up')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::post('/me/support-requests/{supportRequest}/feedback', [\App\Http\Controllers\Web\SupportRequestController::class, 'feedback'])->name('me.support_requests.feedback')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

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
    Route::get('/me/salary-histories', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'meIndex'])->name('me.salary_histories')->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);
    Route::get('/me/salary-histories/{salaryHistory}', [\App\Http\Controllers\Web\SalaryHistoryController::class, 'meShow'])
        ->name('me.salary_histories.show')
        ->middleware(\App\Http\Middleware\EnsureNotAdminOrHr::class);

    Route::middleware(\App\Http\Middleware\EnsureAdminOrHr::class)->group(function () {
        Route::get('/positions', [SmartHrController::class, 'positions'])->name('positions.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');

        Route::get('/transfers/create', [DeletionRequestController::class, 'createTransfer'])->name('transfers.create');
        Route::post('/transfers', [DeletionRequestController::class, 'storeTransfer'])->name('transfers.store');

        Route::get('/deletion-requests', [DeletionRequestController::class, 'index'])->name('deletion_requests.index');
        Route::get('/deletion-requests/{deletionRequest}/document', [DeletionRequestController::class, 'document'])->name('deletion_requests.document');
        Route::post('/deletion-requests/{deletionRequest}/approve', [DeletionRequestController::class, 'approve'])->name('deletion_requests.approve');
        Route::post('/deletion-requests/{deletionRequest}/reject', [DeletionRequestController::class, 'reject'])->name('deletion_requests.reject');
        Route::post('/deletion-requests/{deletionRequest}/feedback/{employee}/reply', [DeletionRequestController::class, 'replyTransferFeedback'])->name('deletion_requests.reply_feedback');
        Route::get('/deletion-requests/{deletionRequest}', [DeletionRequestController::class, 'show'])->name('deletion_requests.show');

        Route::get('/departments', [SmartHrController::class, 'departments'])->name('departments.index');
        Route::get('/departments/create', [SmartHrController::class, 'createDepartment'])->name('departments.create');
        Route::post('/departments', [SmartHrController::class, 'storeDepartment'])->name('departments.store');
        Route::get('/departments/{department}/edit', [SmartHrController::class, 'editDepartment'])->name('departments.edit');
        Route::get('/departments/{department}/deletion-request', [DeletionRequestController::class, 'createDepartment'])->name('deletion_requests.create_department');
        Route::post('/departments/{department}/deletion-request', [DeletionRequestController::class, 'storeDepartment'])->name('deletion_requests.store_department');
        Route::post('/departments/{department}/transfer-employees', [DeletionRequestController::class, 'transferEmployees'])->name('deletion_requests.transfer_employees');
        Route::get('/departments/{department}', [SmartHrController::class, 'showDepartment'])->name('departments.show');
        Route::put('/departments/{department}', [SmartHrController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [SmartHrController::class, 'destroyDepartment'])->name('departments.destroy');

        // Yêu cầu xóa nhân viên / phòng ban — HR tạo, Giám đốc duyệt, HR thực hiện
        Route::get('/deletion-requests', [\App\Http\Controllers\HR\DeletionRequestController::class, 'index'])->name('deletion_requests.index');
        Route::get('/deletion-requests/create', [\App\Http\Controllers\HR\DeletionRequestController::class, 'create'])->name('deletion_requests.create');
        Route::post('/deletion-requests', [\App\Http\Controllers\HR\DeletionRequestController::class, 'store'])->name('deletion_requests.store');
        Route::get('/deletion-requests/{deletionRequest}', [\App\Http\Controllers\HR\DeletionRequestController::class, 'show'])->name('deletion_requests.show');
        Route::post('/deletion-requests/{deletionRequest}/approve', [\App\Http\Controllers\HR\DeletionRequestController::class, 'approve'])->name('deletion_requests.approve');
        Route::post('/deletion-requests/{deletionRequest}/reject', [\App\Http\Controllers\HR\DeletionRequestController::class, 'reject'])->name('deletion_requests.reject');
        Route::post('/deletion-requests/{deletionRequest}/execute', [\App\Http\Controllers\HR\DeletionRequestController::class, 'execute'])->name('deletion_requests.execute');
        Route::post('/deletion-requests/{deletionRequest}/cancel', [\App\Http\Controllers\HR\DeletionRequestController::class, 'cancel'])->name('deletion_requests.cancel');

        Route::get('/employees', [SmartHrController::class, 'employees'])->name('employees.index');
        Route::get('/employees/create', [SmartHrController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [SmartHrController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [SmartHrController::class, 'editEmployee'])->name('employees.edit');
        Route::get('/employees/{employee}/deletion-request', [DeletionRequestController::class, 'createEmployee'])->name('deletion_requests.create_employee');
        Route::post('/employees/{employee}/deletion-request', [DeletionRequestController::class, 'storeEmployee'])->name('deletion_requests.store_employee');
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
        Route::get('/contracts/{contract}/renew', [SmartHrController::class, 'renewContract'])->name('contracts.renew');
        Route::post('/contracts/{contract}/renew', [SmartHrController::class, 'storeRenewalContract'])->name('contracts.storeRenewal');
        Route::post('/contracts/{contract}/handle-expiry', [SmartHrController::class, 'handleContractExpiry'])->name('contracts.handle_expiry');
        Route::get('/contracts/{contract}/document', [\App\Http\Controllers\Web\ContractEsignController::class, 'document'])->name('contracts.document');
        Route::post('/contracts/{contract}/send-for-signature', [\App\Http\Controllers\Web\ContractEsignController::class, 'sendForSignature'])->name('contracts.send_for_signature');
        Route::post('/contracts/{contract}/reject-signature', [\App\Http\Controllers\Web\ContractEsignController::class, 'reject'])->name('contracts.reject_signature');
        Route::post('/contracts/{contract}/sign', [SmartHrController::class, 'signContract'])->name('contracts.sign');
        Route::post('/contracts/{contract}/sync-salary', [SmartHrController::class, 'syncContractSalary'])->name('contracts.sync_salary');
        Route::post('/contracts/sync-salary/from-payroll', [\App\Http\Controllers\Web\SmartHrController::class, 'syncAllContractSalariesFromPayroll'])->name('contracts.sync_salary_from_payroll');
        Route::post('/contracts/sync-salary/from-contract', [\App\Http\Controllers\Web\SmartHrController::class, 'syncAllPayrollSalariesFromContracts'])->name('contracts.sync_salary_from_contract');

        Route::get('/contract-templates/content', [\App\Http\Controllers\Web\ContractTemplateController::class, 'templateContent'])->name('contract-templates.content');
        Route::resource('contract-templates', \App\Http\Controllers\Web\ContractTemplateController::class)->names('contract-templates');

        Route::get('/attendance', [SmartHrController::class, 'attendance'])->name('attendance.index');
        Route::get('/attendance/create', [SmartHrController::class, 'createAttendance'])->name('attendance.create');
        Route::post('/attendance', [SmartHrController::class, 'storeAttendance'])->name('attendance.store');
        Route::post('/attendance/adjustments/{adjustment}/approve', [SmartHrController::class, 'approveAttendanceAdjustment'])->name('attendance.adjustments.approve');
        Route::post('/attendance/adjustments/{adjustment}/reject', [SmartHrController::class, 'rejectAttendanceAdjustment'])->name('attendance.adjustments.reject');
        Route::post('/face-profiles/{faceProfile}/approve', [SmartHrController::class, 'approveFaceRegistration'])->name('face_profiles.approve');
        Route::post('/face-profiles/{faceProfile}/reject', [SmartHrController::class, 'rejectFaceRegistration'])->name('face_profiles.reject');
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
            Route::post('/payroll/period/lock', [PayrollController::class, 'lockPeriod'])
                ->name('payroll.period.lock');
            Route::post('/payroll/period/unlock', [PayrollController::class, 'unlockPeriod'])
                ->name('payroll.period.unlock');

            // In bảng lương trình Giám đốc + duyệt toàn bộ (đặt trước {payroll})
            Route::get('/payroll/print', [PayrollController::class, 'printSheet'])
                ->name('payroll.print');
            Route::post('/payroll/review-all', [PayrollController::class, 'reviewAll'])
                ->name('payroll.review_all');
            Route::post('/payroll/approve-all', [PayrollController::class, 'approveAll'])
                ->name('payroll.approve_all');

            // Yêu cầu đổi STK/QR — đặt trước {payroll}
            Route::get('/payroll/bank-requests', [\App\Http\Controllers\HR\SalaryReceiveChangeRequestController::class, 'index'])
                ->name('payroll.bank_requests.index');
            Route::post('/payroll/bank-requests/{changeRequest}/approve', [\App\Http\Controllers\HR\SalaryReceiveChangeRequestController::class, 'approve'])
                ->name('payroll.bank_requests.approve');
            Route::post('/payroll/bank-requests/{changeRequest}/reject', [\App\Http\Controllers\HR\SalaryReceiveChangeRequestController::class, 'reject'])
                ->name('payroll.bank_requests.reject');

            // Gửi phiếu lương qua email (phải đặt TRƯỚC /payroll/{payroll})
            Route::get('/payroll/email', [\App\Http\Controllers\Web\PayrollEmailController::class, 'index'])
                ->name('payroll.email.index');
            Route::post('/payroll/email/send-all', [\App\Http\Controllers\Web\PayrollEmailController::class, 'sendAll'])
                ->name('payroll.email.send_all');
            Route::post('/payroll/email/{payroll}/send', [\App\Http\Controllers\Web\PayrollEmailController::class, 'send'])
                ->name('payroll.email.send');

            // Sự cố lương từ nhân viên
            Route::get('/payroll/issues', [PayrollController::class, 'issues'])
                ->name('payroll.issues.index');
            Route::get('/payroll/{payroll}/fix-issue', [PayrollController::class, 'fixIssueForm'])
                ->name('payroll.issues.fix_form');
            Route::post('/payroll/{payroll}/fix-issue', [PayrollController::class, 'fixIssueSave'])
                ->name('payroll.issues.fix');

            // Chi tiết
            Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])
                ->name('payroll.show');

            // HR kiểm tra dữ liệu; Giám đốc phê duyệt cuối
            Route::post('/payroll/{payroll}/review', [PayrollController::class, 'review'])
                ->name('payroll.review');
            Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])
                ->name('payroll.approve');

            Route::post('/payroll/{payroll}/approve-with-payment', [PayrollController::class, 'approveWithPayment'])
                ->name('payroll.approve_with_payment');

            // Thanh toán lương (Kế toán)
            Route::get('/payroll/{payroll}/payment', [\App\Http\Controllers\HR\PayrollPaymentController::class, 'show'])
                ->name('payroll.payment.show');
            Route::post('/payroll/{payroll}/payment/bank', [\App\Http\Controllers\HR\PayrollPaymentController::class, 'updateBank'])
                ->name('payroll.payment.bank');
            Route::post('/payroll/{payroll}/payment/confirm', [\App\Http\Controllers\HR\PayrollPaymentController::class, 'confirm'])
                ->name('payroll.payment.confirm');

            // Xóa
            Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])
                ->name('payroll.destroy');
        });

        Route::get('/evaluations', [SmartHrController::class, 'evaluations'])->name('evaluations.index');
        Route::get('/evaluations/create', [SmartHrController::class, 'createEvaluation'])->name('evaluations.create');
        Route::get('/evaluations/suggest', [SmartHrController::class, 'evaluationSuggest'])->name('evaluations.suggest');
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
        Route::get('/overtime-requests', [SmartHrController::class, 'overtimeRequests'])->name('overtime_requests.index');
        Route::post('/overtime-requests/assign', [SmartHrController::class, 'assignOvertimeRequest'])->name('overtime_requests.assign');
        Route::post('/overtime-requests/{overtimeRequest}/approve', [SmartHrController::class, 'approveOvertimeRequest'])->name('overtime_requests.approve');
        Route::post('/overtime-requests/{overtimeRequest}/reject', [SmartHrController::class, 'rejectOvertimeRequest'])->name('overtime_requests.reject');
        Route::post('/overtime-requests/{overtimeRequest}/verify', [SmartHrController::class, 'verifyOvertimeRequest'])->name('overtime_requests.verify');

        Route::get('/support-requests', [HrSupportRequestController::class, 'index'])->name('support_requests.index');
        Route::get('/support-requests/{supportRequest}', [HrSupportRequestController::class, 'show'])->name('support_requests.show');
        Route::post('/support-requests/{supportRequest}/approve', [HrSupportRequestController::class, 'approve'])->name('support_requests.approve');
        Route::post('/support-requests/{supportRequest}/resolve', [HrSupportRequestController::class, 'resolve'])->name('support_requests.resolve');
        
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

        // Statistics & Reports
        Route::get('/statistics', [\App\Http\Controllers\Web\StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('/statistics/export', [\App\Http\Controllers\Web\StatisticsController::class, 'exportExcel'])->name('statistics.export');
        Route::get('/statistics/trend', [\App\Http\Controllers\Web\StatisticsController::class, 'trend'])->name('statistics.trend');
        Route::get('/statistics/departments', [\App\Http\Controllers\Web\StatisticsController::class, 'departmentReport'])->name('statistics.departments');
        Route::get('/statistics/departments/export', [\App\Http\Controllers\Web\StatisticsController::class, 'exportDepartment'])->name('statistics.departments.export');
        Route::get('/api/statistics/trend', [\App\Http\Controllers\Web\StatisticsController::class, 'apiTrend'])->name('api.statistics.trend');
        Route::get('/api/statistics/distribution', [\App\Http\Controllers\Web\StatisticsController::class, 'apiDistribution'])->name('api.statistics.distribution');

        // HR Dashboard tổng hợp
        Route::get('/hr-dashboard', [\App\Http\Controllers\Web\HrDashboardController::class, 'index'])->name('hr-dashboard.index');
        Route::get('/hr-dashboard/export', [\App\Http\Controllers\Web\HrDashboardController::class, 'export'])->name('hr-dashboard.export');
    });

    // Đề xuất thăng chức / tăng lương — HR tạo, Giám đốc duyệt/từ chối
    // (ngoài EnsureAdminOrHr để Giám đốc truy cập; controller tự kiểm tra vai trò)
    Route::get('/promotion-requests', [PromotionRequestController::class, 'index'])->name('promotion_requests.index');
    Route::get('/promotion-requests/create', [PromotionRequestController::class, 'create'])->name('promotion_requests.create');
    Route::post('/promotion-requests', [PromotionRequestController::class, 'store'])->name('promotion_requests.store');
    Route::get('/promotion-requests/{promotionRequest}', [PromotionRequestController::class, 'show'])->name('promotion_requests.show');
    Route::post('/promotion-requests/{promotionRequest}/approve', [PromotionRequestController::class, 'approve'])->name('promotion_requests.approve');
    Route::post('/promotion-requests/{promotionRequest}/reject', [PromotionRequestController::class, 'reject'])->name('promotion_requests.reject');
    Route::post('/promotion-requests/{promotionRequest}/apply', [PromotionRequestController::class, 'apply'])->name('promotion_requests.apply');
    Route::post('/promotion-requests/{promotionRequest}/cancel', [PromotionRequestController::class, 'cancel'])->name('promotion_requests.cancel');

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


