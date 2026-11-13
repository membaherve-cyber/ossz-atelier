import { createHmac, randomBytes, scryptSync, timingSafeEqual } from "crypto";
import { cookies, headers } from "next/headers";
import { db } from "@/lib/db";
import { hasPermission, permissionsFor, type Permission, type Role } from "@/lib/permissions";
import { ApiError } from "@/lib/api";
import type { SessionUser } from "@/lib/atelier/constants";

const COOKIE_NAME = "atelier_session";
const SESSION_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

// ---------------------------------------------------------------------------
// Password hashing (scrypt with per-user salt)
// ---------------------------------------------------------------------------

export function hashPassword(password: string): string {
  const salt = randomBytes(16).toString("hex");
  const hash = scryptSync(password, salt, 64).toString("hex");
  return `${salt}:${hash}`;
}

export function verifyPassword(password: string, stored: string): boolean {
  const [salt, hash] = stored.split(":");
  if (!salt || !hash) return false;
  const candidate = scryptSync(password, salt, 64);
  const expected = Buffer.from(hash, "hex");
  return candidate.length === expected.length && timingSafeEqual(candidate, expected);
}

// ---------------------------------------------------------------------------
// Stateless signed session tokens (payload + HMAC-SHA256)
// ---------------------------------------------------------------------------

function authSecret(): string {
  return process.env.AUTH_SECRET || "ossz-atelier-dev-secret-do-not-use-in-production";
}

function sign(payload: string): string {
  return createHmac("sha256", authSecret()).update(payload).digest("base64url");
}

export function createSessionToken(userId: string): string {
  const payload = Buffer.from(
    JSON.stringify({ uid: userId, iat: Date.now(), exp: Date.now() + SESSION_TTL_MS })
  ).toString("base64url");
  return `${payload}.${sign(payload)}`;
}

export function verifySessionToken(token: string): string | null {
  const [payload, sig] = token.split(".");
  if (!payload || !sig) return null;
  const expected = sign(payload);
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !timingSafeEqual(a, b)) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, "base64url").toString("utf8"));
    if (typeof data.uid !== "string" || typeof data.exp !== "number") return null;
    if (data.exp < Date.now()) return null;
    return data.uid;
  } catch {
    return null;
  }
}

export async function setSessionCookie(userId: string): Promise<void> {
  const store = await cookies();
  store.set(COOKIE_NAME, createSessionToken(userId), {
    httpOnly: true,
    // "none" + secure: required for the session cookie to survive when the
    // app is displayed inside an embedded (third-party) context such as the
    // sandbox preview iframe. Browsers drop SameSite=Lax cookies there.
    sameSite: "none",
    path: "/",
    maxAge: Math.floor(SESSION_TTL_MS / 1000),
    secure: true,
  });
}

export async function clearSessionCookie(): Promise<void> {
  const store = await cookies();
  store.delete(COOKIE_NAME);
}

// ---------------------------------------------------------------------------
// Session retrieval & guards
// ---------------------------------------------------------------------------

export async function getSessionUser(): Promise<SessionUser | null> {
  let uid: string | null = null;

  // 1) Bearer token in the Authorization header (works even when third-party
  //    cookies are blocked entirely, e.g. strict iframe privacy settings).
  try {
    const hdrs = await headers();
    const authHeader = hdrs.get("authorization");
    if (authHeader?.startsWith("Bearer ")) {
      uid = verifySessionToken(authHeader.slice(7).trim());
    }
  } catch {
    // headers() unavailable in this context — fall through to the cookie.
  }

  // 2) Session cookie (primary mechanism in normal first-party usage).
  if (!uid) {
    const store = await cookies();
    const token = store.get(COOKIE_NAME)?.value;
    if (token) uid = verifySessionToken(token);
  }

  if (!uid) return null;
  const user = await db.user.findUnique({ where: { id: uid } });
  if (!user || !user.isActive) return null;
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role as Role,
    permissions: permissionsFor(user.role),
  };
}

export async function requireUser(): Promise<SessionUser> {
  const user = await getSessionUser();
  if (!user) throw new ApiError(401, "Authentication required");
  return user;
}

export async function requirePermission(permission: Permission): Promise<SessionUser> {
  const user = await requireUser();
  if (!hasPermission(user.role, permission)) {
    throw new ApiError(403, `Your account is not authorized to perform this action (${permission}).`);
  }
  return user;
}

/** Require one of the given roles. */
export async function requireRole(...roles: Role[]): Promise<SessionUser> {
  const user = await requireUser();
  if (!roles.includes(user.role)) {
    throw new ApiError(403, "Your account is not authorized to perform this action.");
  }
  return user;
}

// ---------------------------------------------------------------------------
// Brute-force protection for login (in-memory, per instance)
// ---------------------------------------------------------------------------

const attempts = new Map<string, { count: number; lockedUntil: number }>();
const MAX_ATTEMPTS = 8;
const LOCK_MS = 10 * 60 * 1000;

export function checkLoginRateLimit(key: string): void {
  const entry = attempts.get(key);
  if (entry && entry.lockedUntil > Date.now()) {
    const mins = Math.ceil((entry.lockedUntil - Date.now()) / 60000);
    throw new ApiError(429, `Too many failed attempts. Try again in ${mins} minute(s).`);
  }
}

export function recordLoginFailure(key: string): void {
  const entry = attempts.get(key) ?? { count: 0, lockedUntil: 0 };
  entry.count += 1;
  if (entry.count >= MAX_ATTEMPTS) {
    entry.lockedUntil = Date.now() + LOCK_MS;
    entry.count = 0;
  }
  attempts.set(key, entry);
}

export function clearLoginFailures(key: string): void {
  attempts.delete(key);
}
