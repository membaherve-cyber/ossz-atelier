<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Atelier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Permanent audit trail browser (spec §3: GET /audit, `audit.view` —
 * Management only via the route middleware).
 *
 * Ported from src/app/api/atelier/audit/route.ts: paginated, newest first
 * (created_at desc, then id desc as a stable tiebreaker), filterable by
 * action, user and a from/to date range. Date-only "to" inputs are
 * inclusive of the whole end day, mirroring dateBound() in the reference
 * implementation.
 *
 * AuditLog keeps denormalized snapshots (user_id / user_name) and defines
 * NO Eloquent relations, so the acting User models are attached IN MEMORY
 * on each row — views may use `$log->user?->name` or the never-null
 * `$log->user_name` snapshot (the Audit service writes "System" for
 * system-generated entries).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class AuditController
{
    /** Rows per page on the audit screen. */
    private const PAGE_SIZE = 30;

    /**
     * GET /audit — filterable audit history.
     *
     * Filters (all optional, carried through pagination via
     * withQueryString): action (one of Atelier::AUDIT_ACTIONS), user
     * (user id), from / to (dates; "to" covers the whole end day when
     * given date-only).
     */
    public function index(Request $request): View
    {
        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'user' => trim((string) $request->query('user', '')),
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
        ];

        $query = AuditLog::query();

        if ($filters['action'] !== '' && in_array($filters['action'], Atelier::AUDIT_ACTIONS, true)) {
            $query->where('action', $filters['action']);
        }

        if ($filters['user'] !== '') {
            $query->where('user_id', $filters['user']);
        }

        $from = $this->parseDate($filters['from']);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        $to = $this->parseDate($filters['to']);

        if ($to !== null) {
            // Date-only inputs include the whole end day (= dateBound in
            // the reference audit route); values carrying a time are used
            // as-is.
            $dateOnly = ! str_contains($filters['to'], 'T') && ! str_contains($filters['to'], ':');

            $query->where('created_at', '<=', $dateOnly ? $to->endOfDay() : $to);
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PAGE_SIZE)
            ->withQueryString();

        $this->attachUsers($logs->getCollection());

        return view('audit.index', [
            'logs' => $logs,
            'actions' => Atelier::AUDIT_ACTIONS,
            'users' => User::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Attach the acting User model to each log row in memory (AuditLog has
     * no Eloquent relation — snapshots only). Rows whose account was
     * deleted keep a null ->user and fall back to user_name.
     *
     * @param  Collection<int, AuditLog>  $logs
     */
    private function attachUsers(Collection $logs): void
    {
        $userIds = $logs
            ->pluck('user_id')
            ->filter(static fn ($id) => (string) $id !== '')
            ->unique()
            ->values();

        $users = $userIds->isEmpty()
            ? Collection::make()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $logs->each(static function (AuditLog $log) use ($users): void {
            $log->setAttribute('user', $users->get($log->user_id));
        });
    }

    /**
     * Parse a filter date; empty or unparseable values are ignored so the
     * page stays renderable (the reference route validates, the Blade UI
     * posts date inputs).
     */
    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
