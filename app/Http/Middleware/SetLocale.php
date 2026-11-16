<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale for the current request.
 *
 * The UI language is stored in the session by LocaleController (route
 * GET /locale/{lang}). When a session locale exists we adopt it; otherwise
 * the default from config('app.locale') ('en') stays in effect. The locale
 * switch request itself is passed through untouched so the controller can
 * update the session without the middleware overriding the outgoing request
 * locale (spec §3: "UI language from session").
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('locale.switch') && $request->session()->has('locale')) {
            app()->setLocale($request->session()->get('locale'));
        }

        return $next($request);
    }
}
