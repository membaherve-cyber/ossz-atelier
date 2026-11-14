<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manually registered atelier customer.
 *
 * 1:1 port of the Prisma `Customer` model: UUID primary key, unique
 * code (CUST-0001), contact details, free-text address/notes and the
 * `createdById` snapshot (no relation in Prisma).
 */
class Customer extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Prisma datetimes. */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Orders placed by this customer (Prisma relation `orders`).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
