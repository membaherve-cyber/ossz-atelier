import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import {
  checkLoginRateLimit,
  clearLoginFailures,
  createSessionToken,
  recordLoginFailure,
  setSessionCookie,
  verifyPassword,
} from "@/lib/auth";
import { permissionsFor } from "@/lib/permissions";
import { logAudit } from "@/lib/atelier/audit";

const loginSchema = z.object({
  email: z.string().trim().toLowerCase().min(3).max(200),
  password: z.string().min(1).max(200),
});

export const POST = withApi(async (req: Request) => {
  const { email, password } = await parseBody(req, loginSchema);

  checkLoginRateLimit(email);

  const user = await db.user.findUnique({ where: { email } });
  if (!user || !verifyPassword(password, user.passwordHash)) {
    recordLoginFailure(email);
    await logAudit(null, "LOGIN_FAILED", "USER", undefined, { email });
    throw new ApiError(401, "Invalid email or password.");
  }
  if (!user.isActive) {
    await logAudit(null, "LOGIN_BLOCKED_INACTIVE", "USER", user.id, { email });
    throw new ApiError(403, "This account has been deactivated. Contact Management.");
  }

  clearLoginFailures(email);
  await setSessionCookie(user.id);
  const token = createSessionToken(user.id);
  await logAudit(
    { id: user.id, code: user.code, name: user.name, email: user.email, role: user.role as never, permissions: [] },
    "LOGIN_SUCCESS",
    "USER",
    user.id
  );

  return json({
    // The token mirrors the session cookie. The client keeps it in localStorage
    // and sends it as a Bearer header so authentication keeps working in
    // embedded/iframe contexts where third-party cookies are blocked.
    token,
    user: {
      id: user.id,
      code: user.code,
      name: user.name,
      email: user.email,
      role: user.role,
      permissions: permissionsFor(user.role),
    },
  });
});
