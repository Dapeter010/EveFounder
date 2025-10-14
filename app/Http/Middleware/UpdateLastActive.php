<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActive
{
    /**
     * Handle an incoming request.
     *
     * Updates the authenticated user's last_active_at timestamp
     * to track online/offline status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only update if last_active_at is older than 5 minutes
            // This prevents excessive database writes
            if (!$user->last_active_at || $user->last_active_at->lt(now()->subMinutes(5))) {
                $user->update(['last_active_at' => now()]);
            }
        }

        return $next($request);
    }
}
