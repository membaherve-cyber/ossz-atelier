module.exports = [
"[externals]/next/dist/compiled/next-server/app-route-turbo.runtime.dev.js [external] (next/dist/compiled/next-server/app-route-turbo.runtime.dev.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/compiled/next-server/app-route-turbo.runtime.dev.js", () => require("next/dist/compiled/next-server/app-route-turbo.runtime.dev.js"));

module.exports = mod;
}),
"[externals]/next/dist/compiled/@opentelemetry/api [external] (next/dist/compiled/@opentelemetry/api, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/compiled/@opentelemetry/api", () => require("next/dist/compiled/@opentelemetry/api"));

module.exports = mod;
}),
"[externals]/next/dist/compiled/next-server/app-page-turbo.runtime.dev.js [external] (next/dist/compiled/next-server/app-page-turbo.runtime.dev.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/compiled/next-server/app-page-turbo.runtime.dev.js", () => require("next/dist/compiled/next-server/app-page-turbo.runtime.dev.js"));

module.exports = mod;
}),
"[externals]/next/dist/server/app-render/work-unit-async-storage.external.js [external] (next/dist/server/app-render/work-unit-async-storage.external.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/server/app-render/work-unit-async-storage.external.js", () => require("next/dist/server/app-render/work-unit-async-storage.external.js"));

module.exports = mod;
}),
"[externals]/next/dist/server/app-render/work-async-storage.external.js [external] (next/dist/server/app-render/work-async-storage.external.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/server/app-render/work-async-storage.external.js", () => require("next/dist/server/app-render/work-async-storage.external.js"));

module.exports = mod;
}),
"[externals]/next/dist/shared/lib/no-fallback-error.external.js [external] (next/dist/shared/lib/no-fallback-error.external.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/shared/lib/no-fallback-error.external.js", () => require("next/dist/shared/lib/no-fallback-error.external.js"));

module.exports = mod;
}),
"[project]/src/lib/db.ts [app-route] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "db",
    ()=>db
]);
var __TURBOPACK__imported__module__$5b$externals$5d2f40$prisma$2f$client__$5b$external$5d$__$2840$prisma$2f$client$2c$__cjs$2c$__$5b$project$5d2f$node_modules$2f40$prisma$2f$client$29$__ = __turbopack_context__.i("[externals]/@prisma/client [external] (@prisma/client, cjs, [project]/node_modules/@prisma/client)");
;
const globalForPrisma = globalThis;
const db = globalForPrisma.prisma ?? new __TURBOPACK__imported__module__$5b$externals$5d2f40$prisma$2f$client__$5b$external$5d$__$2840$prisma$2f$client$2c$__cjs$2c$__$5b$project$5d2f$node_modules$2f40$prisma$2f$client$29$__["PrismaClient"]({
    log: [
        'error'
    ]
});
if ("TURBOPACK compile-time truthy", 1) globalForPrisma.prisma = db;
}),
"[externals]/next/dist/server/app-render/after-task-async-storage.external.js [external] (next/dist/server/app-render/after-task-async-storage.external.js, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("next/dist/server/app-render/after-task-async-storage.external.js", () => require("next/dist/server/app-render/after-task-async-storage.external.js"));

module.exports = mod;
}),
"[project]/src/lib/api.ts [app-route] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "ApiError",
    ()=>ApiError,
    "json",
    ()=>json,
    "parseBody",
    ()=>parseBody,
    "parseQuery",
    ()=>parseQuery,
    "withApi",
    ()=>withApi
]);
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/next/server.js [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$zod$2f$v4$2f$classic$2f$errors$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/zod/v4/classic/errors.js [app-route] (ecmascript)");
;
;
class ApiError extends Error {
    status;
    details;
    constructor(status, message, details){
        super(message), this.status = status, this.details = details;
    }
}
function json(data, status = 200) {
    return __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["NextResponse"].json(data, {
        status
    });
}
async function parseBody(req, schema) {
    let raw;
    try {
        raw = await req.json();
    } catch  {
        throw new ApiError(400, "Invalid JSON body");
    }
    const result = schema.safeParse(raw);
    if (!result.success) {
        throw new ApiError(400, "Validation failed", {
            issues: result.error.issues.map((i)=>({
                    path: i.path.join("."),
                    message: i.message
                }))
        });
    }
    return result.data;
}
function parseQuery(req, schema) {
    const params = {};
    new URL(req.url).searchParams.forEach((value, key)=>{
        if (value !== "") params[key] = value;
    });
    const result = schema.safeParse(params);
    if (!result.success) {
        throw new ApiError(400, "Invalid query parameters", {
            issues: result.error.issues.map((i)=>({
                    path: i.path.join("."),
                    message: i.message
                }))
        });
    }
    return result.data;
}
function withApi(handler) {
    return async (req, ...args)=>{
        try {
            return await handler(req, ...args);
        } catch (err) {
            if (err instanceof ApiError) {
                return __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["NextResponse"].json({
                    error: err.message,
                    details: err.details ?? null
                }, {
                    status: err.status
                });
            }
            if (err instanceof __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$zod$2f$v4$2f$classic$2f$errors$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["ZodError"]) {
                return __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["NextResponse"].json({
                    error: "Validation failed"
                }, {
                    status: 400
                });
            }
            // Missing table/column (Prisma P2021/P2022) — happens transiently while
            // the database schema is being (re)created at startup. Report it as a
            // retryable state instead of a scary 500.
            if (err instanceof Error && "code" in err && (err.code === "P2021" || err.code === "P2022")) {
                return __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["NextResponse"].json({
                    error: "The database is being initialised. Please retry in a few seconds."
                }, {
                    status: 503
                });
            }
            console.error("[api]", err);
            return __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$server$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["NextResponse"].json({
                error: "Internal server error"
            }, {
                status: 500
            });
        }
    };
}
}),
"[externals]/crypto [external] (crypto, cjs)", ((__turbopack_context__, module, exports) => {

const mod = __turbopack_context__.x("crypto", () => require("crypto"));

module.exports = mod;
}),
"[project]/src/lib/permissions.ts [app-route] (ecmascript)", ((__turbopack_context__) => {
"use strict";

// OSSZ ATELIER — role & permission matrix for the independent production
// management system. Server-side authorization is enforced in every API route
// via requirePermission(). Staff (production workers) have no global
// permissions: their access is strictly scoped to their own assigned tasks.
__turbopack_context__.s([
    "ASSIGNABLE_ROLES",
    ()=>ASSIGNABLE_ROLES,
    "PERMISSIONS",
    ()=>PERMISSIONS,
    "ROLES",
    ()=>ROLES,
    "ROLE_DESCRIPTIONS",
    ()=>ROLE_DESCRIPTIONS,
    "ROLE_LABELS",
    ()=>ROLE_LABELS,
    "hasPermission",
    ()=>hasPermission,
    "permissionsFor",
    ()=>permissionsFor
]);
const ROLES = [
    "MANAGEMENT",
    "ADMIN",
    "STAFF"
];
const ROLE_LABELS = {
    MANAGEMENT: "Management",
    ADMIN: "Admin",
    STAFF: "Staff"
};
const ROLE_DESCRIPTIONS = {
    MANAGEMENT: "Full operational and supervisory access, final authority on incentives and critical decisions.",
    ADMIN: "Operational management of customers, orders, production and staff accounts. Cannot override Management-level controls.",
    STAFF: "Individual production worker: sees and updates only the tasks assigned to their own account."
};
const PERMISSIONS = [
    "customers.view",
    "customers.manage",
    "orders.view",
    "orders.manage",
    "users.view",
    "users.manage.staff",
    "users.manage.all",
    "production.view.all",
    "production.create.tasks",
    "production.assign",
    "production.inspect",
    "production.cancel",
    "production.manage.details",
    "production.stages.configure",
    "production.reports",
    "performance.view",
    "performance.configure",
    "performance.assess",
    "incentives.view",
    "incentives.prepare",
    "incentives.manage.schemes",
    "incentives.approve",
    "incentives.payment",
    "audit.view"
];
const ADMIN_PERMISSIONS = [
    "customers.view",
    "customers.manage",
    "orders.view",
    "orders.manage",
    "users.view",
    "users.manage.staff",
    "production.view.all",
    "production.create.tasks",
    "production.assign",
    "production.inspect",
    "production.cancel",
    "production.manage.details",
    "production.stages.configure",
    "production.reports",
    "performance.view",
    "incentives.view",
    "incentives.prepare"
];
const MATRIX = {
    MANAGEMENT: PERMISSIONS,
    ADMIN: ADMIN_PERMISSIONS,
    STAFF: []
};
function hasPermission(role, permission) {
    const list = MATRIX[role];
    return !!list && list.includes(permission);
}
function permissionsFor(role) {
    return [
        ...MATRIX[role] ?? []
    ];
}
const ASSIGNABLE_ROLES = [
    "MANAGEMENT",
    "ADMIN",
    "STAFF"
];
}),
"[project]/src/lib/auth.ts [app-route] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "checkLoginRateLimit",
    ()=>checkLoginRateLimit,
    "clearLoginFailures",
    ()=>clearLoginFailures,
    "clearSessionCookie",
    ()=>clearSessionCookie,
    "createSessionToken",
    ()=>createSessionToken,
    "getSessionUser",
    ()=>getSessionUser,
    "hashPassword",
    ()=>hashPassword,
    "recordLoginFailure",
    ()=>recordLoginFailure,
    "requirePermission",
    ()=>requirePermission,
    "requireRole",
    ()=>requireRole,
    "requireUser",
    ()=>requireUser,
    "setSessionCookie",
    ()=>setSessionCookie,
    "verifyPassword",
    ()=>verifyPassword,
    "verifySessionToken",
    ()=>verifySessionToken
]);
var __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__ = __turbopack_context__.i("[externals]/crypto [external] (crypto, cjs)");
var __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$headers$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/node_modules/next/headers.js [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$db$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/db.ts [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$permissions$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/permissions.ts [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/api.ts [app-route] (ecmascript)");
;
;
;
;
;
const COOKIE_NAME = "atelier_session";
const SESSION_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 days
function hashPassword(password) {
    const salt = (0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["randomBytes"])(16).toString("hex");
    const hash = (0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["scryptSync"])(password, salt, 64).toString("hex");
    return `${salt}:${hash}`;
}
function verifyPassword(password, stored) {
    const [salt, hash] = stored.split(":");
    if (!salt || !hash) return false;
    const candidate = (0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["scryptSync"])(password, salt, 64);
    const expected = Buffer.from(hash, "hex");
    return candidate.length === expected.length && (0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["timingSafeEqual"])(candidate, expected);
}
// ---------------------------------------------------------------------------
// Stateless signed session tokens (payload + HMAC-SHA256)
// ---------------------------------------------------------------------------
function authSecret() {
    return process.env.AUTH_SECRET || "ossz-atelier-dev-secret-do-not-use-in-production";
}
function sign(payload) {
    return (0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["createHmac"])("sha256", authSecret()).update(payload).digest("base64url");
}
function createSessionToken(userId) {
    const payload = Buffer.from(JSON.stringify({
        uid: userId,
        iat: Date.now(),
        exp: Date.now() + SESSION_TTL_MS
    })).toString("base64url");
    return `${payload}.${sign(payload)}`;
}
function verifySessionToken(token) {
    const [payload, sig] = token.split(".");
    if (!payload || !sig) return null;
    const expected = sign(payload);
    const a = Buffer.from(sig);
    const b = Buffer.from(expected);
    if (a.length !== b.length || !(0, __TURBOPACK__imported__module__$5b$externals$5d2f$crypto__$5b$external$5d$__$28$crypto$2c$__cjs$29$__["timingSafeEqual"])(a, b)) return null;
    try {
        const data = JSON.parse(Buffer.from(payload, "base64url").toString("utf8"));
        if (typeof data.uid !== "string" || typeof data.exp !== "number") return null;
        if (data.exp < Date.now()) return null;
        return data.uid;
    } catch  {
        return null;
    }
}
async function setSessionCookie(userId) {
    const store = await (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$headers$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["cookies"])();
    store.set(COOKIE_NAME, createSessionToken(userId), {
        httpOnly: true,
        // "none" + secure: required for the session cookie to survive when the
        // app is displayed inside an embedded (third-party) context such as the
        // sandbox preview iframe. Browsers drop SameSite=Lax cookies there.
        sameSite: "none",
        path: "/",
        maxAge: Math.floor(SESSION_TTL_MS / 1000),
        secure: true
    });
}
async function clearSessionCookie() {
    const store = await (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$headers$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["cookies"])();
    store.delete(COOKIE_NAME);
}
async function getSessionUser() {
    let uid = null;
    // 1) Bearer token in the Authorization header (works even when third-party
    //    cookies are blocked entirely, e.g. strict iframe privacy settings).
    try {
        const hdrs = await (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$headers$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["headers"])();
        const authHeader = hdrs.get("authorization");
        if (authHeader?.startsWith("Bearer ")) {
            uid = verifySessionToken(authHeader.slice(7).trim());
        }
    } catch  {
    // headers() unavailable in this context — fall through to the cookie.
    }
    // 2) Session cookie (primary mechanism in normal first-party usage).
    if (!uid) {
        const store = await (0, __TURBOPACK__imported__module__$5b$project$5d2f$node_modules$2f$next$2f$headers$2e$js__$5b$app$2d$route$5d$__$28$ecmascript$29$__["cookies"])();
        const token = store.get(COOKIE_NAME)?.value;
        if (token) uid = verifySessionToken(token);
    }
    if (!uid) return null;
    const user = await __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$db$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["db"].user.findUnique({
        where: {
            id: uid
        }
    });
    if (!user || !user.isActive) return null;
    return {
        id: user.id,
        code: user.code,
        name: user.name,
        email: user.email,
        role: user.role,
        permissions: (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$permissions$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["permissionsFor"])(user.role)
    };
}
async function requireUser() {
    const user = await getSessionUser();
    if (!user) throw new __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["ApiError"](401, "Authentication required");
    return user;
}
async function requirePermission(permission) {
    const user = await requireUser();
    if (!(0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$permissions$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["hasPermission"])(user.role, permission)) {
        throw new __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["ApiError"](403, `Your account is not authorized to perform this action (${permission}).`);
    }
    return user;
}
async function requireRole(...roles) {
    const user = await requireUser();
    if (!roles.includes(user.role)) {
        throw new __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["ApiError"](403, "Your account is not authorized to perform this action.");
    }
    return user;
}
// ---------------------------------------------------------------------------
// Brute-force protection for login (in-memory, per instance)
// ---------------------------------------------------------------------------
const attempts = new Map();
const MAX_ATTEMPTS = 8;
const LOCK_MS = 10 * 60 * 1000;
function checkLoginRateLimit(key) {
    const entry = attempts.get(key);
    if (entry && entry.lockedUntil > Date.now()) {
        const mins = Math.ceil((entry.lockedUntil - Date.now()) / 60000);
        throw new __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["ApiError"](429, `Too many failed attempts. Try again in ${mins} minute(s).`);
    }
}
function recordLoginFailure(key) {
    const entry = attempts.get(key) ?? {
        count: 0,
        lockedUntil: 0
    };
    entry.count += 1;
    if (entry.count >= MAX_ATTEMPTS) {
        entry.lockedUntil = Date.now() + LOCK_MS;
        entry.count = 0;
    }
    attempts.set(key, entry);
}
function clearLoginFailures(key) {
    attempts.delete(key);
}
}),
"[project]/src/app/api/atelier/auth/me/route.ts [app-route] (ecmascript)", ((__turbopack_context__) => {
"use strict";

__turbopack_context__.s([
    "GET",
    ()=>GET
]);
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$db$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/db.ts [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/api.ts [app-route] (ecmascript)");
var __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$auth$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__ = __turbopack_context__.i("[project]/src/lib/auth.ts [app-route] (ecmascript)");
;
;
;
const GET = (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["withApi"])(async ()=>{
    const user = await (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$auth$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["getSessionUser"])();
    if (!user) return (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["json"])({
        user: null
    });
    const unreadCount = await __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$db$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["db"].notification.count({
        where: {
            userId: user.id,
            isRead: false
        }
    });
    return (0, __TURBOPACK__imported__module__$5b$project$5d2f$src$2f$lib$2f$api$2e$ts__$5b$app$2d$route$5d$__$28$ecmascript$29$__["json"])({
        user,
        unreadCount
    });
});
}),
];

//# sourceMappingURL=%5Broot-of-the-server%5D__e432ba4a._.js.map