<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\FaceAttendanceController;
use App\Http\Controllers\Employee\SimpleAttendanceController;

// Attendance routes
Route::prefix('attendance')->group(function () {
    Route::controller(AttendanceController::class)->group(function () {
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

    Route::controller(FaceAttendanceController::class)->group(function () {
        Route::get('/face-profile', 'getFaceProfile');
        Route::post('/register-face', 'registerFaceProfile');
        Route::post('/face', 'faceAttendance');
    });
});

// Simple Attendance routes (one button only)
Route::prefix('attendance/simple')->controller(SimpleAttendanceController::class)->group(function () {
    Route::post('/check', 'checkAttendance');
    Route::get('/today-status', 'getTodayStatus');
});
