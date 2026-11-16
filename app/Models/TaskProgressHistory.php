<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable numerical progress history (append-only).
 *
 * 1:1 port of the Prisma `TaskProgressHistory` model: `userId` /
 * `userName` are snapshots (no relation in Prisma). Source: MANUAL |
 * STAGE | INSPECTION. History table with `created_at` only.
 */
class TaskProgressHistory extends Model
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

    /** Prisma integers + datetimes. */
    protected $casts = [
        'previous_progress' => 'integer',
        'new_progress' => 'integer',
        'created_at' => 'datetime',
    ];

    /** The task this history entry belongs to. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The staff member who changed the progress (snapshot reference). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
