<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal notification addressed to a staff account.
 *
 * 1:1 port of the Prisma `Notification` model. Type: TASK_ASSIGNED |
 * TASK_REASSIGNED | DEADLINE_CHANGED | NEW_INSTRUCTIONS |
 * CORRECTIONS_REQUESTED | BLOCK_RESOLVED | TASK_APPROVED |
 * TASK_REOPENED | SUBMITTED_FOR_INSPECTION | TASK_OVERDUE |
 * ISSUE_REPORTED | TASK_BLOCKED | READY_FOR_PICKUP | TASK_DELIVERED.
 * `taskId` / `orderId` are nullable snapshot references (no relations
 * in Prisma). History table with `created_at` only.
 */
class Notification extends Model
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
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** The recipient account. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The task this notification refers to (nullable snapshot). */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The order this notification refers to (nullable snapshot). */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Notifications that have not been read yet. */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
