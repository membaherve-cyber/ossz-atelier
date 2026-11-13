<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Current state of a checklist stage for a task
 * (NOT_STARTED | IN_PROGRESS | COMPLETED | NOT_APPLICABLE).
 *
 * 1:1 port of the Prisma `TaskStageState` model: unique per
 * (task_id, stage_id); the table only carries `updated_at` (no
 * created_at), so CREATED_AT is disabled and Eloquent maintains
 * updated_at automatically.
 */
class TaskStageState extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Only updated_at exists on this table (Prisma has no createdAt). */
    public const CREATED_AT = null;

    /** Prisma datetimes. */
    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /** The task this stage state belongs to. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The stage this state refers to (no FK — snapshot reference). */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'stage_id');
    }
}
