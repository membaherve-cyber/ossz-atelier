<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Permanent audit trail entry (Management/Admin visibility).
 *
 * 1:1 port of the Prisma `AuditLog` model: `userId` / `userName` are
 * snapshots (no relation in Prisma), `details` is a JSON document
 * stored as text, append-only with `created_at`. Action codes are the
 * `audit.action.*` keys (LOGIN_SUCCESS, USER_CREATED, TASK_ASSIGNED,
 * INCENTIVE_APPROVED, …).
 */
class AuditLog extends Model
{
    use HasUuid;

    /** Prisma `String @id @default(cuid())` — string UUID key. */
    protected $keyType = 'string';

    /** The primary key is not auto-incrementing. */
    public $incrementing = false;

    /** Mass assignment is open for every attribute except the key. */
    protected $guarded = ['id'];

    /** History table: only created_at exists. */
    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    /** Prisma datetimes. */
    protected $casts = [
        'created_at' => 'datetime',
    ];
}
