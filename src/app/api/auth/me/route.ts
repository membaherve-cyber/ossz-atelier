import { db } from "@/lib/db";
import { json, withApi } from "@/lib/api";
import { getSessionUser } from "@/lib/auth";
import { permissionsFor } from "@/lib/permissions";

export const GET = withApi(async () => {
  const user = await getSessionUser();
  if (!user) return json({ user: null }, 200);
  const dbUser = await db.user.findUnique({ where: { id: user.id } });
  if (!dbUser || !dbUser.isActive) return json({ user: null }, 200);
  return json({
    user: {
      id: dbUser.id,
      email: dbUser.email,
      name: dbUser.name,
      role: dbUser.role,
      permissions: permissionsFor(dbUser.role),
    },
  });
});
