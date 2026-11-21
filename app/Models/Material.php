<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Material master (spec v1.3 §5.4) — tissus, doublures, boutons,
 * fermetures, fils, entoilages, élastiques et accessoires.
 *
 * stock_qty / avg_cost are denormalized from the material_stock_movements
 * ledger for fast reads (the ledger stays the source of truth). Materials
 * marked is_client_supplied (§5.15) remain fully traceable but never
 * enter stock valuation or production cost (avg_cost stays 0).
 */
class Material extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'stock_qty' => 'float',
        'avg_cost' => 'float',
        'is_client_supplied' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The stock ledger (§5.5 — every in/out with its value). */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(MaterialStockMovement::class, 'material_id');
    }

    /** Issue lines that ever handed this material to an employee. */
    public function issueLines(): HasMany
    {
        return $this->hasMany(MaterialIssueLine::class, 'material_id');
    }

    /** Restrict to active materials. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Current stock value (XAF) — 0 for client-supplied materials.
     */
    public function stockValue(): float
    {
        return $this->is_client_supplied ? 0.0 : round($this->stock_qty * $this->avg_cost, 2);
    }
}
