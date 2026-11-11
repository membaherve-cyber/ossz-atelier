// OSSZ ATELIER — permanent audit trail. Best-effort: never breaks the main flow.

import { db } from "@/lib/db";
import type { SessionUser } from "@/lib/atelier/constants";

export async function logAudit(
  user: SessionUser | null,
  action: string,
  entityType?: string,
  entityId?: string,
  details?: unknown
): Promise<void> {
  try {
    await db.auditLog.create({
      data: {
        userId: user?.id ?? null,
        userName: user?.name ?? "System",
        action,
        entityType: entityType ?? null,
        entityId: entityId ?? null,
        details: details === undefined ? null : JSON.stringify(details),
      },
    });
  } catch (err) {
    console.error("[audit] failed to write entry", action, err);
  }
}
