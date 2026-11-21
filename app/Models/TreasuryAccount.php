<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Treasury account (spec v1.3 §4.4) — caisse physique, compte bancaire,
 * MTN Mobile Money, Orange Money or any other configured account.
 *
 * Each account serves one payment method (Atelier::PAYMENT_METHODS), so
 * recorded payments/expenses route to the right account automatically.
 * The balance is always DERIVED: opening_balance + Σ IN movements −
 * Σ OUT movements (never stored, §1.2 principle).
 */
class TreasuryAccount extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'opening_balance' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The account's money movements (cash journal, §4.4). */
    public function movements(): HasMany
    {
        return $this->hasMany(TreasuryMovement::class, 'account_id');
    }

    /** Restrict to active accounts. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Derived balance (XAF): opening + Σ IN − Σ OUT (§4.4).
     */
    public function balance(): float
    {
        $totals = $this->movements()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'IN' THEN amount ELSE 0 END), 0) AS total_in, "
                ."COALESCE(SUM(CASE WHEN direction = 'OUT' THEN amount ELSE 0 END), 0) AS total_out")
            ->first();

        return round($this->opening_balance
            + (float) ($totals->total_in ?? 0)
            - (float) ($totals->total_out ?? 0), 2);
    }
}
