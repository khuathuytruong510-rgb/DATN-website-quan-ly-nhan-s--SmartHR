<?php

use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\ContractController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\HR\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/employees/{id}', [EmployeeController::class, 'show']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

Route::get('/contracts', [ContractController::class, 'index']);
Route::get('/contracts/{id}', [ContractController::class, 'show']);
Route::post('/contracts', [ContractController::class, 'store']);
Route::put('/contracts/{id}', [ContractController::class, 'update']);
Route::delete('/contracts/{id}', [ContractController::class, 'destroy']);

Route::get('/attendance', [AttendanceController::class, 'index']);
Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
Route::post('/attendance', [AttendanceController::class, 'store']);
Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy']);

Route::get('/payroll', [PayrollController::class, 'index']);
Route::get('/payroll/{id}', [PayrollController::class, 'show']);
Route::post('/payroll', [PayrollController::class, 'store']);
Route::put('/payroll/{id}', [PayrollController::class, 'update']);
Route::delete('/payroll/{id}', [PayrollController::class, 'destroy']);

Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show']);
Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
Route::put('/leave-requests/{id}', [LeaveRequestController::class, 'update']);
Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy']);
