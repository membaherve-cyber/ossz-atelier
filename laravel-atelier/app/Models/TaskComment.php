<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Production notes & reported issues.
 *
 * 1:1 port of the Prisma `TaskComment` model: `userId` / `userName`
 * are snapshots (no relation in Prisma). History table with
 * `created_at` only.
 */
class TaskComment extends Model
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

    /** Prisma booleans + datetimes. */
    protected $casts = [
        'is_issue' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** The task this comment belongs to. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The comment author (snapshot reference, no FK). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
