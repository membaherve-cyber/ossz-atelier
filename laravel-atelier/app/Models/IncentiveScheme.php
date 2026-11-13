<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Incentive scheme definition.
 *
 * 1:1 port of the Prisma `IncentiveScheme` model. periodType: MONTHLY |
 * QUARTERLY | ANNUAL | QUALITY | ON_TIME | CUSTOM. `createdById` /
 * `createdByName` are snapshots (no relation in Prisma).
 */
class IncentiveScheme extends Model
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
        'min_score' => 'float',
        'amount' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
