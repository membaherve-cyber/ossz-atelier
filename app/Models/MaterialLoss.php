<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material loss computed at issue closure (spec v1.3 §5.10/§11).
 *
 * qty_lost = qty_issued − Σ approved consumed − Σ approved returned —
 * never manually entered. Valued at the issue line's unit cost (§7.4
 * "les matières perdues sont valorisées au même coût que les matières
 * consommées"). The optional justification is for abnormal gaps (§5.10).
 */
class MaterialLoss extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_lost' => 'float',
        'value' => 'float',
        'computed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(MaterialIssueLine::class, 'issue_line_id');
    }
}
