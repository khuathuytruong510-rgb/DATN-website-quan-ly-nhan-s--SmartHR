<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminOrHr
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || (! $user->is_admin && ! $user->is_hr)) {
            abort(403);
        }

        return $next($request);
    }
}
