<?php

use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\SmartHrController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SmartHrController::class, 'showLogin'])->name('login');
    Route::post('/login', [SmartHrController::class, 'login'])->name('login.store');
    Route::get('/register', [SmartHrController::class, 'showRegister'])->name('register');
    Route::post('/register', [SmartHrController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [SmartHrController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [SmartHrController::class, 'dashboard']);
    Route::post('/logout', [SmartHrController::class, 'logout'])->name('logout');

    Route::middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
        Route::get('/accounts', [SmartHrController::class, 'accounts'])->name('accounts.index');
        Route::get('/permissions', [SmartHrController::class, 'permissions'])->name('permissions.index');
        Route::put('/permissions/{user}', [SmartHrController::class, 'updatePermissions'])->name('permissions.update');
        Route::get('/system-logs', [SmartHrController::class, 'systemLogs'])->name('system_logs.index');
        Route::get('/settings', [SmartHrController::class, 'settings'])->name('settings.index');
    });

    Route::get('/me', [EmployeeController::class, 'dashboard'])->name('me.dashboard');
    Route::get('/me/profile', [EmployeeController::class, 'profile'])->name('me.profile');
    Route::get('/me/profile/edit', [EmployeeController::class, 'editProfile'])->name('me.profile.edit');
    Route::put('/me/profile', [EmployeeController::class, 'updateProfile'])->name('me.profile.update');
    Route::get('/me/trainings', [EmployeeController::class, 'trainings'])->name('me.trainings');
    Route::get('/me/rewards', [EmployeeController::class, 'rewards'])->name('me.rewards');
    Route::get('/me/attendance', [EmployeeController::class, 'attendanceIndex'])->name('me.attendance');
    Route::get('/me/attendance/create', [EmployeeController::class, 'attendanceCreate'])->name('me.attendance.create');
    Route::post('/me/attendance', [EmployeeController::class, 'attendanceStore'])->name('me.attendance.store');
    Route::get('/me/contracts', [EmployeeController::class, 'contracts'])->name('me.contracts');
    Route::get('/me/payroll', [EmployeeController::class, 'payrolls'])->name('me.payrolls');
    Route::get('/me/leave-requests', [EmployeeController::class, 'leaveIndex'])->name('me.leave_requests');
    Route::get('/me/leave-requests/create', [EmployeeController::class, 'leaveCreate'])->name('me.leave_requests.create');
    Route::post('/me/leave-requests', [EmployeeController::class, 'leaveStore'])->name('me.leave_requests.store');
    Route::get('/me/notifications', [EmployeeController::class, 'notifications'])->name('me.notifications');

    Route::middleware(\App\Http\Middleware\EnsureAdminOrHr::class)->group(function () {
        Route::get('/positions', [SmartHrController::class, 'positions'])->name('positions.index');
        Route::get('/notifications', [SmartHrController::class, 'notifications'])->name('notifications.index');

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
        
        Route::get('/payroll', [SmartHrController::class, 'payroll'])->name('payroll.index');
        Route::get('/payroll/create', [SmartHrController::class, 'createPayroll'])->name('payroll.create');
        Route::post('/payroll', [SmartHrController::class, 'storePayroll'])->name('payroll.store');
        Route::get('/payroll/{payroll}', [SmartHrController::class, 'showPayroll'])->name('payroll.show');
        Route::get('/payroll/{payroll}/edit', [SmartHrController::class, 'editPayroll'])->name('payroll.edit');
        Route::put('/payroll/{payroll}', [SmartHrController::class, 'updatePayroll'])->name('payroll.update');
        Route::delete('/payroll/{payroll}', [SmartHrController::class, 'destroyPayroll'])->name('payroll.destroy');
        
        Route::get('/leave-requests', [SmartHrController::class, 'leaveRequests'])->name('leave_requests.index');
        Route::get('/leave-requests/create', [SmartHrController::class, 'createLeaveRequest'])->name('leave_requests.create');
        Route::post('/leave-requests', [SmartHrController::class, 'storeLeaveRequest'])->name('leave_requests.store');
        Route::post('/leave-requests/{leaveRequest}/approve', [SmartHrController::class, 'approveLeaveRequest'])->name('leave_requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [SmartHrController::class, 'rejectLeaveRequest'])->name('leave_requests.reject');
        Route::delete('/leave-requests/{leaveRequest}', [SmartHrController::class, 'destroyLeaveRequest'])->name('leave_requests.destroy');
    });
});
