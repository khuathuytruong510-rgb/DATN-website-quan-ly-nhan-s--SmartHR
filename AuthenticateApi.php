<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra xem có session auth không
        if (Auth::check()) {
            return $next($request);
        }

        // Hoặc kiểm tra API token
        $token = $request->bearerToken();
        if ($token) {
            $user = \App\Models\User::where('api_token', $token)->first();
            if ($user) {
                Auth::setUser($user);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}