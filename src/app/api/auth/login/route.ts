import { db } from "@/lib/db";
import { json, parseBody, withApi, ApiError } from "@/lib/api";
import { loginSchema } from "@/lib/validation";
import {
  checkLoginRateLimit,
  clearLoginFailures,
  recordLoginFailure,
  setSessionCookie,
  verifyPassword,
} from "@/lib/auth";
import { permissionsFor } from "@/lib/permissions";

export const POST = withApi(async (req) => {
  const { email, password } = await parseBody(req, loginSchema);

  checkLoginRateLimit(email);

  const user = await db.user.findUnique({ where: { email } });
  if (!user || !verifyPassword(password, user.passwordHash)) {
    recordLoginFailure(email);
    throw new ApiError(401, "Invalid email or password");
  }
  if (!user.isActive) {
    throw new ApiError(403, "This account has been deactivated. Contact an administrator.");
  }

  clearLoginFailures(email);
  await setSessionCookie(user.id);

  return json({
    user: {
      id: user.id,
      email: user.email,
      name: user.name,
      role: user.role,
      permissions: permissionsFor(user.role),
    },
  });
});
