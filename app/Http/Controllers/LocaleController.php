<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UI language switch (route: GET /locale/{lang}, name: locale.switch).
 *
 * OWNERSHIP NOTE: this is the ONLY controller owned by the platform /
 * skeleton agent (8-a) — it is tiny and tightly coupled to routes/web.php
 * and the SetLocale middleware. Every other controller under
 * App\Http\Controllers (Dashboard, Task, TaskAction, Order, Customer, User,
 * Stage, Performance, Incentive, Report, Notification, Audit and
 * Auth\Login) is owned by the controllers agent (8-c) per spec §3/§6.
 *
 * Deliberately does NOT extend a base Controller class so it stays
 * self-contained (Laravel route controllers do not need to).
 */
class LocaleController
{
    /**
     * Store the requested language in the session and send the user back
     * to the page they came from.
     *
     * Only "en" and "fr" are accepted (the two locales shipped under
     * lang/); any other value leaves the session untouched.
     *
     * The redirect target is validated as SAME-ORIGIN: the Referer header
     * is client-controlled, so blindly redirecting to it would give any
     * attacker a free open redirect off the app domain (phishing). A
     * referer whose host+scheme do not match this application is ignored
     * and the user lands on the dashboard instead.
     */
    public function switch(Request $request, string $lang): RedirectResponse
    {
        if (in_array($lang, ['en', 'fr'], true)) {
            $request->session()->put('locale', $lang);
        }

        $referer = $request->headers->get('referer');

        if ($referer !== null && $referer !== '' && ! str_contains($referer, '/locale/') && $this->isSameOrigin($referer)) {
            return redirect()->away($referer);
        }

        return redirect('/');
    }

    /**
     * Whether the referer URL belongs to this application: a plain
     * http/https URL whose host matches the host of the current request.
     */
    private function isSameOrigin(string $referer): bool
    {
        $parsed = parse_url($referer);

        if ($parsed === false || ! isset($parsed['host']) || ! isset($parsed['scheme'])) {
            return false;
        }

        if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        return strcasecmp($parsed['host'], (string) request()->getHost()) === 0;
    }
}
