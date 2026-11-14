<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assignment & reassignment history (immutable).
 *
 * 1:1 port of the Prisma `TaskAssignment` model: `staffId` /
 * `assignedById` are denormalized name/id snapshots (no relations in
 * Prisma). Type: ASSIGN | REASSIGN | UNASSIGN. History table with
 * `created_at` only — Eloquent timestamp handling is disabled and the
 * column is cast manually.
 */
class TaskAssignment extends Model
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

    /** The task this assignment belongs to. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }
}
