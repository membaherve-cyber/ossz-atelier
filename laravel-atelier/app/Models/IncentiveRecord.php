<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Generated incentive recommendation and its approval / payment
 * lifecycle.
 *
 * 1:1 port of the Prisma `IncentiveRecord` model. status: PENDING |
 * APPROVED | REJECTED ; paymentStatus: UNPAID | PAID. `schemeId` /
 * `staffId` / `approvedById` (plus their name columns) are snapshots —
 * no relations in Prisma, hence no foreign keys.
 */
class IncentiveRecord extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Prisma floats/booleans + datetimes. */
    protected $casts = [
        'performance_score' => 'float',
        'eligible' => 'boolean',
        'proposed_amount' => 'float',
        'approved_amount' => 'float',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
