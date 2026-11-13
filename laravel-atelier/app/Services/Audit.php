<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OSSZ ATELIER — permanent audit trail (Management-only visibility).
 *
 * Ported from src/lib/atelier/audit.ts (logAudit). The audit trail is
 * append-only: entries can never be edited or deleted. Writing an entry is
 * best-effort — a failure is logged but never breaks the main flow.
 *
 * Action codes are the keys of audit.action.* in lang/{en,fr}/audit.php
 * (= App\Support\Atelier::AUDIT_ACTIONS). Details are stored as a JSON
 * string.
 */
final class Audit
{
    /**
     * Append an immutable audit entry.
     *
     * @param  string  $action      one of Atelier::AUDIT_ACTIONS
     * @param  string|null  $entityType  e.g. "TASK", "ORDER", "USER"
     * @param  string|null  $entityId    UUID of the affected row (when applicable)
     * @param  array<string, mixed>|null  $details  JSON-encoded into the details column
     */
    public static function log(?User $user, string $action, ?string $entityType = null, ?string $entityId = null, ?array $details = null): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details === null ? null : json_encode($details),
            ]);
        } catch (Throwable $e) {
            Log::error('[audit] failed to write entry', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
