<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the stock ledger (spec v1.3 §5.5/§5.9/§10).
 *
 * Purchases feed stock (PURCHASE_IN, positive quantity, unit cost
 * refreshes the weighted average); issues to employees drain it
 * (ISSUE_OUT, negative); approved restitutions feed it back
 * (RETURN_IN); ADJUSTMENT fixes physical counts either way. Consumption
 * itself never touches stock — the material already left at issue time
 * (§10 "une sortie vers un employé n'est pas une consommation").
 */
class MaterialStockMovement extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'value' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
