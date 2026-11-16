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
 * 1:1 port of the Prisma `Order` model. Priority: LOW | NORMAL | HIGH |
 * URGENT. `createdById` is a snapshot (no relation in Prisma).
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

    /** Prisma integers + datetimes. */
    protected $casts = [
        'number_of_pieces' => 'integer',
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
}
