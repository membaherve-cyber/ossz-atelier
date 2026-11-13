<?php

namespace App\Http\Middleware;

use App\Services\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission gate middleware (alias: `perm`).
 *
 * Usage: ->middleware('perm:orders.manage') — the parameter must be one of
 * the permission keys in App\Services\Permissions::PERMISSIONS (the exact
 * list from src/lib/permissions.ts).
 *
 * Guests are rejected with 403 (the login screen itself is public and the
 * `auth` middleware handles the redirect-to-login behaviour on routes that
 * declare it); authenticated users are checked against the role matrix
 * (MANAGEMENT = all permissions, ADMIN = operational list, STAFF = none —
 * staff access is scoped to their own tasks inside the controllers).
 */
class EnsurePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! Permissions::allows($user, $permission)) {
            abort(403);
        }

        return $next($request);
    }
}
