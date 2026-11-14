<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable record of every checklist stage status change.
 *
 * 1:1 port of the Prisma `TaskStageUpdate` model: `stageId` / `userId`
 * plus `stageName` / `userName` are snapshots (no relations in Prisma).
 * History table with `created_at` only.
 */
class TaskStageUpdate extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** History table: only created_at exists. */
    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    /** Prisma datetimes. */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** The task this update belongs to. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The stage this update refers to (no FK — snapshot reference). */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'stage_id');
    }
}
