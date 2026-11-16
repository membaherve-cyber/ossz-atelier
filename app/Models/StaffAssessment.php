<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Manual Management assessment input (0-100) per staff member per
 * period ("2026-11" month | "2026-Q4" quarter | "2026" year).
 *
 * 1:1 port of the Prisma `StaffAssessment` model: `staffId` /
 * `staffName` are snapshots (no relation in Prisma — the staff account
 * may change or vanish while the assessment must survive). Unique per
 * (staff_id, period); only `updated_at` exists.
 */
class StaffAssessment extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** Only updated_at exists on this table (Prisma has no createdAt). */
    public const CREATED_AT = null;

    /** Prisma floats + datetimes. */
    protected $casts = [
        'score' => 'float',
        'updated_at' => 'datetime',
    ];
}
