<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InspectionRecord;
use App\Models\PerformanceConfig;
use App\Models\ProductionTask;
use App\Models\StaffAssessment;
use App\Models\TaskAssignment;
use App\Models\TaskStatusChange;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;

/**
 * OSSZ ATELIER — staff performance & scoring engine.
 *
 * 1:1 port of src/lib/atelier/performance.ts (periodRange +
 * computeStaffPerformance + listStaffPerformance). All metrics are computed
 * from real recorded data: assignments, task completions, inspection
 * results and status history. Weights are configurable by Management and
 * must total 100.
 *
 * Deviation mandated by spec §5: the management score is NULL (not 0) when
 * the staff member has not been assessed for the period, and the overall
 * score is then renormalized over the remaining weights.
 *
 * Row shape (binding contract — PerformanceController + the incentives
 * generation code against this, do not rename keys):
 *
 *   user         App\Models\User          the staff account
 *   staffId      string                   alias of $user->id (defensive
 *                                         consumers read staffId/userId)
 *   productivity ?float                   completed / effective piece target (0-100)
 *   timeliness   ?float                   on-time share of completions (0-100)
 *   quality      ?float                   share of completions without corrections (0-100)
 *   reliability  ?float                   100 − (blocks + corrections)/assigned (0-100)
 *   management   ?float|null              StaffAssessment score for the period
 *   overall      ?float                   weighted sum of the non-null scores
 *   assigned     int                      distinct tasks assigned in range
 *   avgHours     ?float                   avg hours from last assignment to approval
 *   pieces       int                      pieces completed in range (= completed)
 *   pieceTarget  int                      effective target (pieceTarget × months)
 *   completed    int                      tasks completed in range
 *   onTime       int                      completions with completed_at <= deadline
 *   overdue      int                      deadlines missed inside the period
 *   inspections  int                      inspection records on the staff's tasks
 *   approved     int                      of which APPROVED
 *   corrections  int                      of which CHANGES_REQUESTED
 *   blockedCount int                      distinct tasks blocked in range
 *   assessment   ?StaffAssessment         the period's management assessment
 */
final class PerformanceService
{
    /**
     * Supported range types (= PeriodType in performance.ts).
     */
    private const RANGE_TYPES = ['MONTH', 'QUARTER', 'YEAR'];

    /**
     * Performance for every ACTIVE staff account for a period.
     *
     * $rangeType: 'MONTH' | 'QUARTER' | 'YEAR'; $rangeValue: 'Y-m' |
     * 'Y-QN' | 'Y' (quarter values are normalized to upper case).
     *
     * @param  string  $rangeType  one of MONTH|QUARTER|YEAR
     * @param  string  $rangeValue  '2026-11' | '2026-Q4' | '2026'
     * @return array{rows: list<array<string, mixed>>, config: PerformanceConfig}
     *
     * @throws InvalidArgumentException when the type or value is malformed
     */
    public static function forRange(string $rangeType, string $rangeValue): array
    {
        $rangeType = strtoupper(trim($rangeType));

        if (! in_array($rangeType, self::RANGE_TYPES, true)) {
            throw new InvalidArgumentException("Unknown performance range type [{$rangeType}].");
        }

        [$start, $end, $months] = self::boundaries($rangeType, $rangeValue);

        // Canonical period key used for the StaffAssessment lookup
        // ("2026-q4" and "2026-Q4" address the same quarter).
        $periodValue = $rangeType === 'QUARTER' ? strtoupper(trim($rangeValue)) : trim($rangeValue);

        $config = self::config();
        $effectiveTarget = max(1, (int) $config->piece_target * $months);

        $rows = [];

        foreach (self::staffUsers() as $user) {
            $rows[] = self::staffRow($user, $periodValue, $start, $end, $config, $effectiveTarget);
        }

        return ['rows' => $rows, 'config' => $config];
    }

    /**
     * The single global scoring configuration, created with the migration
     * defaults (30/25/30/10/5, pieceTarget 20) when missing.
     */
    public static function config(): PerformanceConfig
    {
        return PerformanceConfig::ensure();
    }

    // ------------------------------------------------------------------
    // Period boundaries (periodRange() in performance.ts)
    // ------------------------------------------------------------------

    /**
     * Inclusive start / exclusive end of a period plus its month count.
     *
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private static function boundaries(string $rangeType, string $rangeValue): array
    {
        if ($rangeType === 'MONTH') {
            if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', trim($rangeValue)) !== 1) {
                throw new InvalidArgumentException('Invalid month period. Expected format: YYYY-MM.');
            }

            $year = (int) substr(trim($rangeValue), 0, 4);
            $month = (int) substr(trim($rangeValue), 5, 2);
            $start = Carbon::create($year, $month, 1);

            if ($start === false) {
                throw new InvalidArgumentException('Invalid month period. Expected format: YYYY-MM.');
            }

            $start = $start->startOfDay();

            return [$start, $start->copy()->addMonth()->startOfDay(), 1];
        }

        if ($rangeType === 'QUARTER') {
            $value = strtoupper(trim($rangeValue));

            if (preg_match('/^(\d{4})-Q([1-4])$/', $value) !== 1) {
                throw new InvalidArgumentException('Invalid quarter period. Expected format: YYYY-QN.');
            }

            $year = (int) substr($value, 0, 4);
            $quarter = (int) substr($value, 6, 1);
            $start = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);

            if ($start === false) {
                throw new InvalidArgumentException('Invalid quarter period. Expected format: YYYY-QN.');
            }

            $start = $start->startOfDay();

            return [$start, $start->copy()->addMonths(3)->startOfDay(), 3];
        }

        if (preg_match('/^(\d{4})$/', trim($rangeValue)) !== 1) {
            throw new InvalidArgumentException('Invalid year period. Expected format: YYYY.');
        }

        $start = Carbon::create((int) trim($rangeValue), 1, 1);

        if ($start === false) {
            throw new InvalidArgumentException('Invalid year period. Expected format: YYYY.');
        }

        $start = $start->startOfDay();

        return [$start, $start->copy()->addYear()->startOfDay(), 12];
    }

    // ------------------------------------------------------------------
    // Per-staff computation (computeStaffPerformance() in performance.ts)
    // ------------------------------------------------------------------

    /**
     * Active staff accounts, name-ordered (= listStaffPerformance's user
     * query restricted to active accounts per the view contract).
     *
     * @return EloquentCollection<int, User>
     */
    private static function staffUsers(): EloquentCollection
    {
        return User::query()
            ->where('is_active', true)
            ->where('role', 'STAFF')
            ->orderBy('name')
            ->get();
    }

    /**
     * Metrics + scores for one staff member in the period.
     *
     * @return array<string, mixed>
     */
    private static function staffRow(
        User $user,
        string $periodValue,
        CarbonInterface $start,
        CarbonInterface $end,
        PerformanceConfig $config,
        int $effectiveTarget
    ): array {
        $now = Carbon::now();

        // Distinct tasks assigned (ASSIGN/REASSIGN) during the period.
        $assignedCount = TaskAssignment::query()
            ->where('staff_id', $user->id)
            ->whereIn('type', ['ASSIGN', 'REASSIGN'])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->distinct()
            ->pluck('task_id')
            ->count();

        // Tasks completed (approved) during the period.
        $completed = ProductionTask::query()
            ->where('assigned_staff_id', $user->id)
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<', $end)
            ->get(['id', 'deadline', 'completed_at', 'created_at']);
        $completedCount = $completed->count();

        $onTime = $completed
            ->filter(static fn (ProductionTask $task): bool => $task->deadline !== null
                && $task->completed_at !== null
                && $task->completed_at->lte($task->deadline))
            ->count();

        // Overdue: the deadline fell inside the period (up to "now") and the
        // work was not finished on time — still open, or completed after the
        // deadline (final comparison in memory, exactly like the TS engine).
        $deadlineCeiling = $now->lt($end) ? $now : $end;

        $overdue = ProductionTask::query()
            ->where('assigned_staff_id', $user->id)
            ->whereNotNull('deadline')
            ->where('deadline', '>=', $start)
            ->where('deadline', '<', $deadlineCeiling)
            ->get(['deadline', 'completed_at'])
            ->filter(static fn (ProductionTask $task): bool => $task->completed_at === null
                || $task->completed_at->gt($task->deadline))
            ->count();

        // Quality: of the tasks completed in the period, the share approved
        // without any corrections request across the task's whole history.
        $correctedIds = [];

        if ($completedCount > 0) {
            $correctedIds = InspectionRecord::query()
                ->whereIn('task_id', $completed->pluck('id'))
                ->where('decision', 'CHANGES_REQUESTED')
                ->distinct()
                ->pluck('task_id')
                ->all();
        }

        $cleanApprovals = $completed
            ->filter(static fn (ProductionTask $task): bool => ! in_array((string) $task->id, $correctedIds, true))
            ->count();

        // Every task currently mapped to this staff member (inspection
        // records and blocked occurrences are matched against this set).
        $staffTaskIds = ProductionTask::query()
            ->where('assigned_staff_id', $user->id)
            ->pluck('id');

        $inspections = 0;
        $approved = 0;
        $corrections = 0;
        $blockedCount = 0;

        if ($staffTaskIds->isNotEmpty()) {
            // Inspection decisions on the staff's tasks during the period.
            $decisions = InspectionRecord::query()
                ->whereIn('task_id', $staffTaskIds)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->pluck('decision');

            $inspections = $decisions->count();
            $approved = $decisions->filter(static fn (string $decision): bool => $decision === 'APPROVED')->count();
            $corrections = $decisions->filter(static fn (string $decision): bool => $decision === 'CHANGES_REQUESTED')->count();

            // Blocked occurrences during the period (distinct tasks).
            $blockedCount = TaskStatusChange::query()
                ->where('to_status', 'BLOCKED')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->whereIn('task_id', $staffTaskIds)
                ->distinct()
                ->pluck('task_id')
                ->count();
        }

        // Management assessment for the period.
        $assessment = StaffAssessment::query()
            ->where('staff_id', $user->id)
            ->where('period', $periodValue)
            ->first();

        // Average completion time (hours), from last assignment to approval
        // (avgCompletionHours in performance.ts; falls back to the task's
        // created_at when no assignment row exists).
        $avgHours = null;

        if ($completedCount > 0) {
            $lastAssignments = TaskAssignment::query()
                ->whereIn('task_id', $completed->pluck('id'))
                ->where('staff_id', $user->id)
                ->whereIn('type', ['ASSIGN', 'REASSIGN'])
                ->orderByDesc('created_at')
                ->get(['task_id', 'created_at']);

            $assignMap = [];

            foreach ($lastAssignments as $assignment) {
                if (! array_key_exists((string) $assignment->task_id, $assignMap)) {
                    $assignMap[(string) $assignment->task_id] = $assignment->created_at;
                }
            }

            $durations = [];

            foreach ($completed as $task) {
                if ($task->completed_at === null) {
                    continue;
                }

                $started = $assignMap[(string) $task->id] ?? $task->created_at;

                if ($started !== null) {
                    $durations[] = $task->completed_at->getTimestamp() - $started->getTimestamp();
                }
            }

            if ($durations !== []) {
                $avgHours = round(array_sum($durations) / count($durations) / 3600, 1);
            }
        }

        // Scores (exact formulas from performance.ts).
        $productivity = (float) min(100, round($completedCount / $effectiveTarget * 100));
        $timeliness = $completedCount > 0 ? (float) round($onTime / $completedCount * 100) : 0.0;
        $quality = $completedCount > 0 ? (float) round($cleanApprovals / $completedCount * 100) : 0.0;
        $reliability = $assignedCount > 0
            ? (float) max(0, round(100 - (($blockedCount + $corrections) / $assignedCount) * 100))
            : 100.0;
        $management = $assessment !== null
            ? (float) max(0, min(100, round((float) $assessment->score)))
            : null;

        // Weighted overall. An unassessed management score is excluded and
        // the remaining weights renormalized (spec §5); with all five scores
        // present the denominator is exactly 100, matching the TS engine.
        $parts = [
            [$productivity, (float) $config->productivity_weight],
            [$timeliness, (float) $config->timely_weight],
            [$quality, (float) $config->quality_weight],
            [$reliability, (float) $config->reliability_weight],
        ];

        if ($management !== null) {
            $parts[] = [$management, (float) $config->management_weight];
        }

        $weightSum = array_sum(array_map(static fn (array $part): float => $part[1], $parts));
        $overall = $weightSum > 0
            ? (float) round(array_sum(array_map(static fn (array $part): float => $part[0] * $part[1], $parts)) / $weightSum, 1)
            : null;

        return [
            'user' => $user,
            'staffId' => (string) $user->id,
            'productivity' => $productivity,
            'timeliness' => $timeliness,
            'quality' => $quality,
            'reliability' => $reliability,
            'management' => $management,
            'overall' => $overall,
            'assigned' => $assignedCount,
            'avgHours' => $avgHours,
            'pieces' => $completedCount,
            'pieceTarget' => $effectiveTarget,
            'completed' => $completedCount,
            'onTime' => $onTime,
            'overdue' => $overdue,
            'inspections' => $inspections,
            'approved' => $approved,
            'corrections' => $corrections,
            'blockedCount' => $blockedCount,
            'assessment' => $assessment,
        ];
    }
}
