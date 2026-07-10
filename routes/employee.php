<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\SimpleAttendanceController;

// Attendance routes
Route::prefix('attendance')->controller(AttendanceController::class)->group(function () {
    Route::get('/today', 'getTodayAttendance');
    Route::post('/check-in', 'checkIn');
    Route::post('/check-out', 'checkOut');
    Route::get('/history', 'getAttendanceHistory');
    Route::get('/office-location', 'getOfficeLocation');
    Route::get('/monthly-statistics', 'getMonthlyStatistics');
    Route::get('/today-summary', 'getTodaySummary');
    Route::get('/monthly-summary', 'getMonthlySummary');
    Route::get('/standard-times', 'getStandardTimes');
});

// Simple Attendance routes (one button only)
Route::prefix('attendance/simple')->controller(SimpleAttendanceController::class)->group(function () {
    Route::post('/check', 'checkAttendance');
    Route::get('/today-status', 'getTodayStatus');
});
