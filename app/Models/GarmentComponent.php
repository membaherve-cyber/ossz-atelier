<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garment / piece of an order (spec v1.3 §5.2).
 *
 * Each garment has a readable, order-derived code
 * ({ORDER_NUMBER}-V01, -V02, …) plus the explicit order relation, so
 * "la veste n° 1 de CMD-2026-0042" is identifiable at first glance and
 * the piece → order link is enforced by the database. Codes are unique
 * and never reused (§5.2 rules).
 */
class GarmentComponent extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The order this garment belongs to (§5.2 explicit relation). */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Production tasks working on this exact piece (§5.1). */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProductionTask::class, 'garment_id');
    }

    /** Material issues targeting this piece (§5.4). */
    public function materialIssues(): HasMany
    {
        return $this->hasMany(MaterialIssue::class, 'garment_id');
    }

    /**
     * Next readable code for an order's garment: order number + "-V"
     * + zero-padded sequence (§5.2 — never a bare global sequence).
     */
    public static function nextCodeFor(Order $order): string
    {
        $max = $order->garments()
            ->pluck('code')
            ->map(static function (string $code): int {
                $pos = strrpos($code, '-V');

                return $pos === false ? 0 : (int) substr($code, $pos + 2);
            })
            ->max() ?: 0;

        return $order->order_number.'-V'.str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }
}
