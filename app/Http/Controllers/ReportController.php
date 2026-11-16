<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductionTask;
use App\Models\User;
use App\Support\Atelier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Production reports for a period (spec §3: GET /reports, `perm:production.reports`).
 *
 * Semantics ported from src/app/api/atelier/reports/route.ts + the Laravel
 * view contract #13:
 * - the period selector uses month | quarter | year (default month); an
 *   optional `value` query param (YYYY-MM | YYYY-QN | YYYY) addresses a
 *   past period (Next.js parity) and falls back to the current period
 *   when it does not match the expected format;
 * - $start is INCLUSIVE, $end EXCLUSIVE (periodRange() in performance.ts);
 * - creation-based population (tasks created in range) feeds totalTasks,
 *   byStatus (all 10 statuses, 0-filled), byCategory (all 6 categories,
 *   0-filled), cancelled, blocked, overdue and avgProgress;
 * - completion-based population (completed_at / delivered_at in range)
 *   feeds completed, delivered, totalPieces ("one task = one garment
 *   piece", reports/route.ts) and topStaff (top 5 completions grouped by
 *   assignee).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class ReportController
{
    /** Supported period selectors (query param `period`). */
    private const PERIODS = ['month', 'quarter', 'year'];

    /** Rows shown in the Top Staff table. */
    private const TOP_STAFF_LIMIT = 5;

    /**
     * GET /reports — period statistics + top staff.
     */
    public function index(Request $request): View
    {
        $period = (string) $request->query('period', 'month');

        if (! in_array($period, self::PERIODS, true)) {
            $period = 'month';
        }

        [$start, $end] = $this->periodRange($period, trim((string) $request->query('value', '')));

        // --- creation-based population: tasks registered in the period ---
        $byStatus = ProductionTask::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->pluck('aggregate', 'status');

        $byCategory = ProductionTask::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupBy('category')
            ->selectRaw('category, count(*) as aggregate')
            ->pluck('aggregate', 'category');

        // Creation-based rows that are overdue RIGHT NOW (Atelier::taskIsOverdue
        // semantics: deadline passed, not delivered/cancelled).
        $overdue = ProductionTask::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->whereNotIn('status', Atelier::terminalStatuses())
            ->count();

        $avgProgress = (float) ProductionTask::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->avg('progress');

        // --- completion-based population -----------------------------------
        $completed = ProductionTask::query()
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<', $end)
            ->count();

        $delivered = ProductionTask::query()
            ->where('delivered_at', '>=', $start)
            ->where('delivered_at', '<', $end)
            ->count();

        $stats = [
            'totalTasks' => (int) $byStatus->sum(),
            'completed' => $completed,
            'delivered' => $delivered,
            'cancelled' => (int) ($byStatus['CANCELLED'] ?? 0),
            'blocked' => (int) ($byStatus['BLOCKED'] ?? 0),
            'overdue' => $overdue,
            'avgProgress' => (int) round($avgProgress),
            // One task = one garment piece (reports/route.ts: piecesCompleted).
            'totalPieces' => $completed,
            'byStatus' => $this->zeroFilled($byStatus, Atelier::TASK_STATUSES),
            'byCategory' => $this->zeroFilled($byCategory, Atelier::TASK_CATEGORIES),
            'topStaff' => $this->topStaff($start, $end),
        ];

        return view('reports.index', [
            'period' => $period,
            'stats' => $stats,
            'start' => $start,
            'end' => $end,
        ]);
    }

    // ------------------------------------------------------------------
    // Period math
    // ------------------------------------------------------------------

    /**
     * Resolve the [inclusive start, exclusive end] window for the selected
     * period. An optional `value` (YYYY-MM | YYYY-QN | YYYY) selects a past
     * period; anything else falls back to the CURRENT month/quarter/year.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(string $period, string $value): array
    {
        $now = now();

        if ($period === 'quarter') {
            if (preg_match('/^(\d{4})-Q([1-4])$/', strtoupper($value), $m) === 1) {
                $start = Carbon::create((int) $m[1], ((int) $m[2] - 1) * 3 + 1, 1)->startOfDay();
            } else {
                $start = $now->copy()->firstOfQuarter()->startOfDay();
            }

            return [$start, $start->copy()->addQuarter()];
        }

        if ($period === 'year') {
            $start = preg_match('/^\d{4}$/', $value) === 1
                ? Carbon::create((int) $value, 1, 1)->startOfDay()
                : $now->copy()->startOfYear();

            return [$start, $start->copy()->addYear()];
        }

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value, $m) === 1) {
            $start = Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        } else {
            $start = $now->copy()->startOfMonth();
        }

        return [$start, $start->copy()->addMonth()];
    }

    // ------------------------------------------------------------------
    // Stats helpers
    // ------------------------------------------------------------------

    /**
     * 0-fill a grouped count collection over a fixed key list so the views
     * can iterate every status / category unconditionally.
     *
     * @param  Collection<string, mixed>  $counts
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    private function zeroFilled(Collection $counts, array $keys): array
    {
        $filled = [];

        foreach ($keys as $key) {
            $filled[$key] = (int) ($counts[$key] ?? 0);
        }

        return $filled;
    }

    /**
     * Top 5 staff by completions inside the range (completion-based),
     * grouped by assignee. Each entry carries the User model plus the
     * completed count and the piece count (= completions, one task = one
     * garment piece).
     *
     * @return list<array{user: User, completed: int, pieces: int}>
     */
    private function topStaff(Carbon $start, Carbon $end): array
    {
        $rows = ProductionTask::query()
            ->whereNotNull('assigned_staff_id')
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<', $end)
            ->groupBy('assigned_staff_id')
            ->selectRaw('assigned_staff_id, count(*) as aggregate')
            ->orderByDesc('aggregate')
            ->orderBy('assigned_staff_id')
            ->limit(self::TOP_STAFF_LIMIT)
            ->get();

        $userIds = $rows->pluck('assigned_staff_id')->unique()->values();

        $users = $userIds->isEmpty()
            ? Collection::make()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $topStaff = [];

        foreach ($rows as $row) {
            $user = $users->get($row->assigned_staff_id);

            if ($user === null) {
                continue;
            }

            $completed = (int) $row->aggregate;

            $topStaff[] = [
                'user' => $user,
                'completed' => $completed,
                'pieces' => $completed,
            ];
        }

        return $topStaff;
    }
}
