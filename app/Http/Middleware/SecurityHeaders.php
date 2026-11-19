<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security response headers for every web response (defence in depth —
 * the host's LiteSpeed layer sends none of these by default).
 *
 * - X-Content-Type-Options: nosniff — browsers must not MIME-sniff.
 * - X-Frame-Options: DENY + CSP frame-ancestors 'none' — the app is never
 *   framed (clickjacking protection; print views open in their own tab).
 * - Referrer-Policy: strict-origin-when-cross-origin — full referrer only
 *   same-origin, origin-only cross-origin (no session/URL leakage).
 * - Permissions-Policy — deny camera, microphone, geolocation etc.
 * - Strict-Transport-Security — HTTPS for 180 days (scoped to this
 *   subdomain; includeSubDomains deliberately omitted so neighbouring
 *   subdomains of osszcollection.com are not affected).
 * - Content-Security-Policy — a pragmatic baseline that matches the app's
 *   architecture (Tailwind CSS + Alpine.js use inline attributes, inline
 *   <script> blocks and runtime expression evaluation, so scripts require
 *   'unsafe-inline' + 'unsafe-eval'): everything else is locked to 'self',
 *   objects/frames/base-uri are blocked, and forms only post same-origin.
 *   (Verified in-browser: without 'unsafe-eval' Alpine cannot evaluate
 *   x-data/x-show expressions and the whole interactive layer dies.)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=15552000');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            ."style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data:; "
            ."font-src 'self'; "
            ."connect-src 'self'; "
            ."form-action 'self'; "
            ."object-src 'none'; "
            ."frame-src 'none'; "
            ."frame-ancestors 'none'; "
            ."base-uri 'self'"
        );

        return $response;
    }
}
