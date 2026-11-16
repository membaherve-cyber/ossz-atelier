<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quality control inspection record.
 *
 * 1:1 port of the Prisma `InspectionRecord` model. Decision: APPROVED |
 * CHANGES_REQUESTED | REOPENED. `inspectorId` / `inspectorName` are
 * snapshots (no relation in Prisma). History table with `created_at`
 * only.
 */
class InspectionRecord extends Model
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

    /** The task that was inspected. */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The inspector (snapshot reference, no FK). */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
