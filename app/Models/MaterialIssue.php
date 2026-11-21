<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Material issue / remise (spec v1.3 §5.5-§5.10) — THE moment a
 * material is analytically assigned to production.
 *
 * The issue carries the four traceability dimensions (§5.1): order,
 * garment (optional), task/operation (optional) and the beneficiary
 * employee. Status flow: OPEN (declarations may still arrive) → CLOSED
 * by the manager (loss computed, §5.10) or CANCELLED (mistake —
 * quantities return to stock). Before closure the unexplained balance
 * reads "à rapprocher", never "perte définitive" (§5.10 rules).
 */
class MaterialIssue extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function garment(): BelongsTo
    {
        return $this->belongsTo(GarmentComponent::class, 'garment_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class, 'task_id');
    }

    /** The beneficiary employee (receives the materials, §5.1). */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /** The per-material lines of the remise. */
    public function lines(): HasMany
    {
        return $this->hasMany(MaterialIssueLine::class, 'issue_id');
    }
}
