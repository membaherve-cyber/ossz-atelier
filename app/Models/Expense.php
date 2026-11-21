<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Services\Codes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single workshop cost ledger row (spec §7).
 *
 * Every cost exists here exactly once: manual entries, paid supplier
 * purchases (source_type=PURCHASE) and paid incentives
 * (source_type=INCENTIVE_RECORD). Only APPROVED + PAID rows count as
 * costs in dashboards and reports.
 */
class Expense extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    /** Statuses that count as committed costs. */
    public const COST_STATUSES = ['APPROVED', 'PAID'];

    protected $casts = [
        'amount' => 'float',
        'date_incurred' => 'date',
        'date_paid' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_staff_id');
    }

    /** Optional supplier link on a current expense (spec v1.3 §4.3). */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Book one ledger row for a system source (incentive payment, paid
     * purchase) — idempotent via the unique (source_type, source_id)
     * constraint; an existing row is returned untouched. The system
     * reference EXP-#### is generated here (the reference column is NOT
     * NULL + unique).
     */
    public static function bookForSource(
        string $sourceType,
        string $sourceId,
        array $attributes,
    ): ?self {
        $existing = self::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return Codes::unique('expense', fn (string $reference) => self::query()->create([
                'reference' => $reference,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                ...$attributes,
            ]));
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return self::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->first();
        }
    }
}
