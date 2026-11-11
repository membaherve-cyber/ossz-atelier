import { json, withApi } from "@/lib/api";
import { clearSessionCookie, getSessionUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";

export const POST = withApi(async () => {
  const user = await getSessionUser();
  if (user) {
    await logAudit(user, "LOGOUT", "USER", user.id);
  }
  await clearSessionCookie();
  return json({ ok: true });
});
