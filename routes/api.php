<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HR\PayrollController;

Route::post('/auth/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::post('/auth/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/payroll/{id}/send', [PayrollController::class, 'sendPayroll']);

Route::get('/payroll/confirm/{token}', [PayrollController::class, 'confirmPayroll']);

Route::post('/payroll/{id}/pay', [PayrollController::class, 'payPayroll']);

Route::middleware('api.auth')->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout']);

    require __DIR__ . '/admin.php';
    require __DIR__ . '/hr.php';
    require __DIR__ . '/employee.php';
});

