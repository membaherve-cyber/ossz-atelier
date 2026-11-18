<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Production task — one garment piece / activity per task.
 *
 * 1:1 port of the Prisma `ProductionTask` model. Status: UNASSIGNED |
 * ASSIGNED | ONGOING | AWAITING_INSPECTION | CHANGES_REQUESTED |
 * APPROVED | READY_FOR_PICKUP | DELIVERED | BLOCKED | CANCELLED.
 * Category: FULL | CUTTING | SEWING | FINISHING | IRONING | OTHER.
 * `createdById` is a snapshot (no relation in Prisma).
 */
class ProductionTask extends Model
{
    use HasUuid;

    /** Statuses that end the production lifecycle. */
    public const TERMINAL_STATUSES = ['DELIVERED', 'CANCELLED'];

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Prisma integers + datetimes. */
    protected $casts = [
        'progress' => 'integer',
        'quantity' => 'integer',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations (Prisma: order, assignedStaff "TaskAssignee")
    |--------------------------------------------------------------------------
    */

    /** The order this task belongs to (Prisma relation `order`). */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** The staff member the task is assigned to (nullable). */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /** Assignment / reassignment history. */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }

    /** Current checklist stage states. */
    public function stageStates(): HasMany
    {
        return $this->hasMany(TaskStageState::class, 'task_id');
    }

    /** Immutable stage state change log. */
    public function stageUpdates(): HasMany
    {
        return $this->hasMany(TaskStageUpdate::class, 'task_id');
    }

    /** Numerical progress history. */
    public function progressHistories(): HasMany
    {
        return $this->hasMany(TaskProgressHistory::class, 'task_id');
    }

    /** Status workflow history. */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(TaskStatusChange::class, 'task_id');
    }

    /** Quality inspection records. */
    public function inspections(): HasMany
    {
        return $this->hasMany(InspectionRecord::class, 'task_id');
    }

    /** Production notes & reported issues. */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Tasks that are still live (not DELIVERED / CANCELLED). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    /** Live tasks whose deadline has passed. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    /*
    |--------------------------------------------------------------------------
    | Computed helpers
    |--------------------------------------------------------------------------
    */

    /** True when the deadline has passed and the task is not terminal. */
    public function isOverdue(): bool
    {
        return $this->deadline !== null
            && $this->deadline->isPast()
            && ! $this->isTerminal();
    }

    /** True for DELIVERED / CANCELLED tasks. */
    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
