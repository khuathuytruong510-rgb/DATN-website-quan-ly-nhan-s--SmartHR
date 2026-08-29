<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAdminOrHr
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isStaffUser()) {
            return redirect()->route('dashboard');
        }

        if ($user && ! $user->linkedEmployee() && ! $request->routeIs('me.unlinked')) {
            return redirect()->route('me.unlinked');
        }

        return $next($request);
    }
}
