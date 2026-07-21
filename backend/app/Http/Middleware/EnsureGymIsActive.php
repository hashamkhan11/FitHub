<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymIsActive
{
    /**
     * Block access for anyone (owner, staff, or member) belonging to a gym
     * RankSol has suspended. Platform admins are unaffected by this check.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $gym = $user?->gym;

        if ($gym && $gym->isSuspended()) {
            $message = 'This gym\'s account has been suspended. Please contact RankSol support.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
