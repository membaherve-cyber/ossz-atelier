<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manually registered garment order (business reference such as
 * OSSZ-2026-001).
 *
 * 1:1 port of the Prisma `Order` model + the accounting enhancement:
 * total_value / currency / status (DRAFT | REGISTERED) and derived
 * financial helpers (paid total, outstanding balance, attributable
 * production cost, gross contribution — spec §6 / §11). Financial figures
 * are always DERIVED from the accounting tables so edits can never leave
 * stale duplicated amounts behind.
 */
class Order extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Prisma integers + datetimes + accounting decimals. */
    protected $casts = [
        'number_of_pieces' => 'integer',
        'total_value' => 'float',
        'order_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The customer who placed the order (Prisma relation `customer`).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Production tasks created for this order (Prisma relation `tasks`).
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProductionTask::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'order_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'order_id');
    }

    public function labourAllocations(): HasMany
    {
        return $this->hasMany(OrderLabourAllocation::class, 'order_id');
    }

    // ------------------------------------------------------------------
    // Derived financial figures (never stored — spec §14)
    // ------------------------------------------------------------------

    /** Sum of payments received against this order. */
    public function paidTotal(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /** Outstanding customer balance (0 when no value is recorded). */
    public function outstandingBalance(): float
    {
        return max(0.0, (float) ($this->total_value ?? 0) - $this->paidTotal());
    }

    /** Attributable recorded costs grouped by kind (spec §11). */
    public function costBreakdown(): array
    {
        $committed = Expense::COST_STATUSES;

        $materials = (float) $this->expenses()
            ->whereHas('category', fn ($q) => $q->where('group', 'MATERIALS'))
            ->whereIn('status', $committed)
            ->sum('amount');

        $transport = (float) $this->expenses()
            ->whereHas('category', fn ($q) => $q->where('group', 'TRANSPORT'))
            ->whereIn('status', $committed)
            ->sum('amount');

        $other = (float) $this->expenses()
            ->whereHas('category', fn ($q) => $q->whereNotIn('group', ['MATERIALS', 'TRANSPORT', 'SALARIES']))
            ->whereIn('status', $committed)
            ->sum('amount');

        $labour = (float) $this->labourAllocations()->sum('amount');

        return [
            'materials' => $materials,
            'transport' => $transport,
            'other' => $other,
            'labour' => $labour,
            'total' => $materials + $transport + $other + $labour,
        ];
    }

    /** Gross contribution (order value − attributable production cost). */
    public function grossContribution(): float
    {
        return (float) ($this->total_value ?? 0) - $this->costBreakdown()['total'];
    }

    /**
     * Progress summary across the order's non-cancelled tasks (piece
     * average), used by the order detail page and listings.
     */
    public function productionProgress(): int
    {
        $row = $this->tasks()
            ->where('status', '!=', 'CANCELLED')
            ->selectRaw('COUNT(*) AS n, COALESCE(AVG(progress), 0) AS avg_progress')
            ->first();

        if ($row === null || (int) $row->n === 0) {
            return 0;
        }

        return (int) round((float) $row->avg_progress);
    }

    /** Whether every non-cancelled task is delivered (order complete). */
    public function fullyDelivered(): bool
    {
        $tasks = $this->tasks()->where('status', '!=', 'CANCELLED')->get(['status']);

        return $tasks->isNotEmpty() && $tasks->every(fn ($t) => $t->status === 'DELIVERED');
    }
}
