<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee-to-employee material transfer (spec v1.3 §5.11).
 *
 * Records the chain of custody (who hands over, who receives, for
 * which order / garment / operation, quantity, date) WITHOUT creating
 * a consumption or a new acquisition (§10 "un transfert entre employés
 * ne crée aucune consommation supplémentaire") — the quantity simply
 * passes under the receiver's responsibility.
 */
class MaterialTransfer extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fromStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_staff_id');
    }

    public function toStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_staff_id');
    }

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

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
