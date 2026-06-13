<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::post('/auth/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);

Route::middleware('api.auth')->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout']);

    require __DIR__ . '/admin.php';
    require __DIR__ . '/hr.php';
    require __DIR__ . '/employee.php';
});

