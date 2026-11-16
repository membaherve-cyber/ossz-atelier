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
     * The redirect uses the Referer header (i.e. "back") and falls back to
     * the dashboard when no referer is present — this avoids redirect
     * loops when the locale URL is entered directly in the address bar.
     */
    public function switch(Request $request, string $lang): RedirectResponse
    {
        if (in_array($lang, ['en', 'fr'], true)) {
            $request->session()->put('locale', $lang);
        }

        $referer = $request->headers->get('referer');

        if ($referer !== null && $referer !== '' && ! str_contains($referer, '/locale/')) {
            return redirect()->away($referer);
        }

        return redirect('/');
    }
}
