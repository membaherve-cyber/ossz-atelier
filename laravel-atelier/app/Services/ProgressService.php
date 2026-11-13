<?php

namespace App\Services;

use App\Models\ProductionStage;
use App\Models\ProductionTask;
use App\Models\TaskProgressHistory;
use App\Models\TaskStageState;
use App\Support\Atelier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * OSSZ ATELIER — task progress engine.
 *
 * Ported from src/lib/atelier/production.ts (deriveProgress +
 * recalculateProgress). Task progress is the weighted average of the
 * task's APPLICABLE checklist stages:
 *
 *   COMPLETED      -> full stage weight
 *   IN_PROGRESS    -> half the stage weight
 *   NOT_STARTED    -> 0
 *   NOT_APPLICABLE -> excluded from the average entirely
 *
 * Stages are filtered by ProductionStage.applies_to vs. the task category
 * (App\Support\Atelier::stageAppliesTo(), "ALL" applies everywhere). When
 * no stage applies the manual progress value is kept, and progress derived
 * from stages never DECREASES (manual supervisor corrections remain
 * possible with explicit MANUAL history entries). Every actual change is
 * recorded in task_progress_histories with source "STAGE".
 */
final class ProgressService
{
    /**
     * Recalculate (and persist) the progress of the given task from its
     * stage states. Returns the resulting progress value (0-100).
     */
    public static function recompute(ProductionTask $task, ?string $note = null): int
    {
        // Active stages applicable to this task's category, workflow order.
        $stages = ProductionStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ProductionStage $stage) => Atelier::stageAppliesTo(
                (string) self::attr($stage, 'applies_to'),
                (string) self::attr($task, 'category')
            ))
            ->values();

        $derived = self::deriveProgress(
            $stages,
            TaskStageState::query()->where('task_id', $task->id)->get()
        );

        // No applicable stages (or all NOT_APPLICABLE): keep manual progress.
        if ($derived === null || $derived <= (int) self::attr($task, 'progress')) {
            return (int) self::attr($task, 'progress');
        }

        $user = Auth::user();
        $previous = (int) self::attr($task, 'progress');

        DB::transaction(function () use ($task, $derived, $user, $previous, $note): void {
            $task->forceFill(['progress' => $derived])->save();

            TaskProgressHistory::query()->create([
                'task_id' => self::attr($task, 'id'),
                'user_id' => $user?->id ?? 'system',
                'user_name' => $user?->name ?? 'System',
                'previous_progress' => $previous,
                'new_progress' => $derived,
                'source' => 'STAGE',
                'note' => $note ?? 'Automatic update from production checklist',
            ]);
        });

        return $derived;
    }

    /**
     * Pure weighted average of stage states — returns null when no stage
     * carries weight (ported from deriveProgress() in production.ts).
     *
     * @param  iterable  $stages  items exposing "id" and "weight" (models or arrays)
     * @param  iterable  $states  items exposing "stage_id" and "state"
     */
    public static function deriveProgress(iterable $stages, iterable $states): ?int
    {
        $stateMap = [];

        foreach ($states as $state) {
            $stateMap[self::attr($state, 'stage_id')] = self::attr($state, 'state');
        }

        $totalWeight = 0.0;
        $score = 0.0;

        foreach ($stages as $stage) {
            $state = $stateMap[self::attr($stage, 'id')] ?? 'NOT_STARTED';

            if ($state === 'NOT_APPLICABLE') {
                continue;
            }

            $weight = (float) self::attr($stage, 'weight');
            $totalWeight += $weight;

            if ($state === 'COMPLETED') {
                $score += $weight;
            } elseif ($state === 'IN_PROGRESS') {
                $score += $weight * 0.5;
            }
        }

        if ($totalWeight === 0.0) {
            return null;
        }

        return (int) round(($score / $totalWeight) * 100);
    }

    /**
     * Read an attribute from a model, stdClass or array regardless of style.
     */
    private static function attr(mixed $item, string $key): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if ($item instanceof \ArrayAccess && $item->offsetExists($key)) {
            return $item[$key];
        }

        return $item->{$key} ?? null;
    }
}
