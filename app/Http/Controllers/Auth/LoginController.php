<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Authentication (spec §3: /login, /logout).
 *
 * Ported from src/app/api/atelier/auth/login + logout routes:
 * - rate limit 5 attempts / 60 s per email+IP (Laravel RateLimiter)
 * - inactive accounts are blocked and audited (LOGIN_BLOCKED_INACTIVE)
 *   — but only AFTER the password is verified, so an unauthenticated
 *   attacker can never use the distinct message to enumerate accounts
 * - failed attempts are audited (LOGIN_FAILED) and hit the limiter
 * - success regenerates the session id and audits LOGIN_SUCCESS
 *
 * Deliberately does not extend a base Controller class (Laravel route
 * controllers do not need to), mirroring LocaleController.
 */
class LoginController
{
    /** Maximum failed attempts before the limiter blocks the email+IP pair. */
    private const MAX_ATTEMPTS = 5;

    /** Limiter decay window in seconds. */
    private const DECAY_SECONDS = 60;

    /**
     * GET /login — sign-in form. Authenticated users are sent to the dashboard.
     */
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * POST /login — validate credentials and start the session.
     *
     * Order of operations mirrors the reference implementation: rate-limit
     * check, account lookup, inactive-account block, password verification,
     * session regeneration, audit trail.
     */
    public function attempt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:200'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $email = strtolower(trim($data['email']));
        $key = 'login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return redirect()
                ->back()
                ->with('error', __('flash.loginRateLimited'))
                ->withInput($request->only('email'));
        }

        $user = User::where('email', $email)->first();

        if ($user !== null && ! $user->is_active && Auth::validate(['email' => $email, 'password' => $data['password']])) {
            // Correct password on a deactivated account — the caller has
            // proven credentials, so the specific inactive-account message
            // leaks nothing an attacker could exploit for enumeration.
            Audit::log(null, 'LOGIN_BLOCKED_INACTIVE', 'USER', $user->id, ['email' => $email]);

            return redirect()
                ->back()
                ->with('error', __('flash.loginInactive'))
                ->withInput($request->only('email'));
        }

        if (! Auth::attempt(['email' => $email, 'password' => $data['password']], false)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            Audit::log(null, 'LOGIN_FAILED', 'USER', null, ['email' => $email]);

            return redirect()
                ->back()
                ->with('error', __('login.error'))
                ->withInput($request->only('email'));
        }

        if (! $user?->is_active) {
            // Defensive: attempt() succeeded but the account was deactivated
            // between the two queries — end the session immediately.
            Auth::logout();
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return redirect()
                ->back()
                ->with('error', __('flash.loginInactive'))
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var User $authed */
        $authed = $request->user();
        Audit::log($authed, 'LOGIN_SUCCESS', 'USER', $authed->id);

        return redirect()->intended('/');
    }

    /**
     * POST /logout — audit the sign-out, destroy the session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            Audit::log($user, 'LOGOUT', 'USER', $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
