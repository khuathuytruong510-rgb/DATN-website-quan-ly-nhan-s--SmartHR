<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\AttendanceController;

// Web-based attendance API routes (session authenticated)
Route::middleware(['auth'])
    ->prefix('api/employee/attendance')
    ->controller(AttendanceController::class)
    ->group(function () {
        Route::get('/today', 'getTodayAttendance');
        Route::post('/check-in', 'checkIn');
        Route::post('/check-out', 'checkOut');
        Route::get('/history', 'getAttendanceHistory');
        Route::get('/office-location', 'getOfficeLocation');
    });