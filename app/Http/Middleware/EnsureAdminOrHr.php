<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminOrHr
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || (! $user->is_admin && ! $user->is_hr && ! $user->is_accountant)) {
            abort(403);
        }

        return $next($request);
    }
}
