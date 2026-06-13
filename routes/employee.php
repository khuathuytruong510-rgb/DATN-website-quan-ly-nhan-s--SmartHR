<?php

use Illuminate\Support\Facades\Route;

// Employee route stubs, kept for future employee-specific endpoints.
// Current API controllers are organized under HR and Admin.

Route::get('/', function () {
    return response()->json(['message' => 'Employee route placeholder']);
});
