<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Financial dashboard (enhancement spec §5) — GET /accounting.
 *
 * Every figure on the page comes from AccountingService::dashboard(),
 * which computes revenue / payments / expenses / salaries / advances and
 * the derived balances from recorded database rows only (spec §5.1 —
 * nothing sampled or estimated). The period selector accepts the
 * AccountingService::PERIOD_PRESETS presets plus a custom from/to range;
 * an unknown preset falls back to the current month inside
 * AccountingService::resolvePeriod().
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class DashboardController
{
    /**
     * GET /accounting (route perm accounting.view) — the period-scoped
     * financial dashboard. The resolved period (from/to/preset) is echoed
     * back to the view together with the preset list so the filter keeps
     * its state.
     */
    public function index(Request $request): View
    {
        $from = trim((string) ($request->query('from') ?? ''));
        $to = trim((string) ($request->query('to') ?? ''));

        $period = AccountingService::resolvePeriod(
            (string) ($request->query('period') ?? 'month'),
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
        );

        return view('accounting.dashboard', [
            'dash' => AccountingService::dashboard($period['from'], $period['to']),
            'period' => $period,
            'presets' => AccountingService::PERIOD_PRESETS,
        ]);
    }
}
