<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\FaceAttendanceController;

// Web-based attendance API routes (session authenticated)
Route::middleware(['auth'])->prefix('api/employee/attendance')->group(function () {
    Route::controller(AttendanceController::class)->group(function () {
        Route::get('/today', 'getTodayAttendance');
        Route::post('/check-in', 'checkIn');
        Route::post('/check-out', 'checkOut');
        Route::get('/history', 'getAttendanceHistory');
        Route::get('/office-location', 'getOfficeLocation');
    });

    Route::controller(FaceAttendanceController::class)->group(function () {
        Route::get('/face-profile', 'getFaceProfile');
        Route::post('/register-face', 'registerFaceProfile');
        Route::post('/face', 'faceAttendance');
    });
});
