<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single global performance scoring configuration.
 *
 * 1:1 port of the Prisma `PerformanceConfig` model: the primary key is
 * the fixed string 'main' (NOT a UUID — the only such table), weights
 * must total 100 and only `updated_at` exists (Prisma has no
 * createdAt). Productivity weight 30 / timely 25 / quality 30 /
 * reliability 10 / management 5; pieceTarget defaults to 20 per month.
 */
class PerformanceConfig extends Model
{
    /** Fixed string key 'main' — not a UUID, so no HasUuid trait. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Only updated_at exists on this table (Prisma has no createdAt). */
    public const CREATED_AT = null;

    /** Prisma floats/integers + datetimes. */
    protected $casts = [
        'productivity_weight' => 'float',
        'timely_weight' => 'float',
        'quality_weight' => 'float',
        'reliability_weight' => 'float',
        'management_weight' => 'float',
        'piece_target' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Find (or lazily create) the single 'main' configuration row.
     *
     * Mirrors the Prisma @default("main") singleton usage — the row is
     * created with the migration column defaults (30/25/30/10/5 and a
     * pieceTarget of 20) when it does not exist yet.
     */
    public static function ensure(): static
    {
        $config = static::query()->find('main');

        if ($config === null) {
            $config = static::forceCreate(['id' => 'main']);
        }

        return $config;
    }
}
