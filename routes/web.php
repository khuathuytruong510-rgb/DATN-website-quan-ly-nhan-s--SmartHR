<?php

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
    Route::get('/leave-requests', [SmartHrController::class, 'leaveRequests'])->name('leave_requests.index');
    Route::get('/leave-requests/create', [SmartHrController::class, 'createLeaveRequest'])->name('leave_requests.create');
    Route::post('/leave-requests', [SmartHrController::class, 'storeLeaveRequest'])->name('leave_requests.store');
    Route::post('/leave-requests/{leaveRequest}/approve', [SmartHrController::class, 'approveLeaveRequest'])->name('leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [SmartHrController::class, 'rejectLeaveRequest'])->name('leave_requests.reject');
    Route::delete('/leave-requests/{leaveRequest}', [SmartHrController::class, 'destroyLeaveRequest'])->name('leave_requests.destroy');
});
