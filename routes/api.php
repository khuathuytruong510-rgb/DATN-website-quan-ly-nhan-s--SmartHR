<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('api.auth')->group(function () {

    Route::post('/auth/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::post('/auth/change-password', [
        AuthController::class,
        'changePassword'
    ]);

    require __DIR__.'/admin.php';
    require __DIR__.'/hr.php';
    require __DIR__.'/employee.php';
});