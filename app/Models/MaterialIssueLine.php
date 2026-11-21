<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One material line of a remise (spec v1.3 §11 MaterialIssueLine).
 *
 * qty_issued is what physically left the store for the employee;
 * unit_cost is the weighted-average valuation AT ISSUE TIME (0 for
 * client-supplied materials) so later average changes never rewrite
 * history (§5.5 "conserve le lien entre l'entrée en stock, la sortie
 * physique, la consommation validée et l'imputation analytique").
 */
class MaterialIssueLine extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_issued' => 'float',
        'unit_cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(MaterialIssue::class, 'issue_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /** Employee declarations for this line (§5.7 step 3). */
    public function declarations(): HasMany
    {
        return $this->hasMany(MaterialDeclaration::class, 'issue_line_id');
    }

    /** The loss computed at closure (§5.10). */
    public function losses(): HasMany
    {
        return $this->hasMany(MaterialLoss::class, 'issue_line_id');
    }

    /** Σ approved consumed quantity. */
    public function approvedConsumed(): float
    {
        return (float) $this->declarations()
            ->where('status', 'APPROVED')
            ->sum('qty_consumed');
    }

    /** Σ approved returned quantity. */
    public function approvedReturned(): float
    {
        return (float) $this->declarations()
            ->where('status', 'APPROVED')
            ->sum('qty_returned');
    }

    /** Σ declared (any status) consumed + returned — anomaly guard (§5.10). */
    public function declaredTotal(): float
    {
        $totals = $this->declarations()
            ->selectRaw('COALESCE(SUM(qty_consumed), 0) AS total_consumed, COALESCE(SUM(qty_returned), 0) AS total_returned')
            ->first();

        return (float) ((float) ($totals->total_consumed ?? 0) + (float) ($totals->total_returned ?? 0));
    }
}
