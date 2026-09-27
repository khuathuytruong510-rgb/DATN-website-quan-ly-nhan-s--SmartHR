<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_super_admin, 403, 'Chỉ Siêu Admin được thực hiện thao tác này.');

        return $next($request);
    }
}