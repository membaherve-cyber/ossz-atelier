<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guest middleware (alias: `guest`) for the auth screen.
 *
 * Registered as an explicit alias in bootstrap/app.php so that already
 * authenticated users visiting /login are always redirected to the
 * dashboard route "/" (spec §3), regardless of the framework's default
 * guest redirect target.
 */
class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
