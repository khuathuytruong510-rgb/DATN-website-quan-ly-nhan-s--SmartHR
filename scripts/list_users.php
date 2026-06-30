<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
$users = User::select('id','name','email','is_admin','is_hr','is_locked')->get()->toArray();
print_r($users);

echo PHP_EOL;