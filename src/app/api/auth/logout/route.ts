import { json, withApi } from "@/lib/api";
import { clearSessionCookie } from "@/lib/auth";

export const POST = withApi(async () => {
  await clearSessionCookie();
  return json({ ok: true });
});
