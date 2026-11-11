import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission, requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { notifyUsers } from "@/lib/atelier/notify";
import {
  getApplicableStages,
  nextTaskCode,
  serializeTask,
  taskInclude,
} from "@/lib/atelier/tasks";
import {
  isPriority,
  isTaskCategory,
  TASK_STATUSES,
  PRIORITIES,
} from "@/lib/atelier/constants";

// ---------------------------------------------------------------------------
// GET /api/atelier/tasks — role-scoped production task list.
// Staff only ever see their own tasks (enforced server-side).
// ---------------------------------------------------------------------------

const isoDate = z
  .string()
  .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid date");

const listQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(100).default(20),
  search: z.string().trim().max(120).optional(),
  orderId: z.string().optional(),
  staffId: z.string().optional(),
  status: z.string().optional(),
  priority: z.string().optional(),
  category: z.string().optional(),
  overdue: z
    .string()
    .optional()
    .transform((v) => v === "true" || v === "1"),
  from: isoDate.optional(),
  to: isoDate.optional(),
  sort: z.enum(["updated", "deadline", "progress", "created"]).default("updated"),
});

export const GET = withApi(async (req: Request) => {
  const user = await requireUser();
  const params = Object.fromEntries(new URL(req.url).searchParams.entries());
  const q = listQuerySchema.parse(params);

  const where: Record<string, unknown> = {};

  // Server-side scoping: staff only see their own assigned tasks.
  if (user.role === "STAFF") {
    where.assignedStaffId = user.id;
  } else if (q.staffId) {
    if (q.staffId === "unassigned") where.assignedStaffId = null;
    else where.assignedStaffId = q.staffId;
  }
  if (q.orderId) where.orderId = q.orderId;
  if (q.status && (TASK_STATUSES as readonly string[]).includes(q.status)) where.status = q.status;
  if (q.priority && (PRIORITIES as readonly string[]).includes(q.priority)) where.priority = q.priority;
  if (q.category) where.category = q.category;
  if (q.search) {
    const s = q.search;
    where.OR = [
      { code: { contains: s } },
      { garmentPiece: { contains: s } },
      { order: { orderNumber: { contains: s } } },
      { order: { customer: { name: { contains: s } } } },
    ];
  }
  if (q.from || q.to || q.overdue) {
    where.deadline = {
      ...(q.from ? { gte: new Date(q.from) } : {}),
      ...(q.to ? { lte: new Date(q.to) } : {}),
      ...(q.overdue ? { lt: new Date() } : {}),
    };
  }
  if (q.overdue && !where.status) {
    where.status = { notIn: ["DELIVERED", "CANCELLED"] };
  }

  const orderBy =
    q.sort === "deadline"
      ? [{ deadline: "asc" as const }, { updatedAt: "desc" as const }]
      : q.sort === "progress"
        ? { progress: "asc" as const }
        : q.sort === "created"
          ? { createdAt: "desc" as const }
          : { updatedAt: "desc" as const };

  const [total, tasks] = await db.$transaction([
    db.productionTask.count({ where }),
    db.productionTask.findMany({
      where,
      include: taskInclude,
      orderBy,
      skip: (q.page - 1) * q.pageSize,
      take: q.pageSize,
    }),
  ]);

  const rows = tasks.map(serializeTask);

  return json({ tasks: rows, total, page: q.page, pageSize: q.pageSize });
});

// ---------------------------------------------------------------------------
// POST /api/atelier/tasks — break an order into production tasks (garment
// pieces). Runs in a transaction; duplicate piece names per order are
// rejected. Task codes are system generated (TASK-0001...).
// ---------------------------------------------------------------------------

const createTaskSchema = z.object({
  orderId: z.string().min(1),
  tasks: z
    .array(
      z.object({
        garmentPiece: z.string().trim().min(1, "Garment piece name is required").max(100),
        category: z.string().default("FULL"),
        instructions: z.string().trim().max(2000).optional().nullable(),
        deadline: isoDate.optional().nullable(),
        priority: z.string().default("NORMAL"),
        assignedStaffId: z.string().optional().nullable(),
      })
    )
    .min(1, "At least one garment piece is required")
    .max(50),
});

export const POST = withApi(async (req: Request) => {
  const user = await requirePermission("production.create.tasks");
  const body = await parseBody(req, createTaskSchema);

  const order = await db.order.findUnique({
    where: { id: body.orderId },
    include: { customer: true },
  });
  if (!order) throw new ApiError(404, "Order not found.");

  // Validation
  const seen = new Set<string>();
  for (const t of body.tasks) {
    const key = t.garmentPiece.trim().toLowerCase();
    if (seen.has(key)) {
      throw new ApiError(400, `Duplicate garment piece "${t.garmentPiece}" in this request.`);
    }
    seen.add(key);
    if (!isTaskCategory(t.category)) throw new ApiError(400, `Invalid category: ${t.category}`);
    if (!isPriority(t.priority)) throw new ApiError(400, `Invalid priority: ${t.priority}`);
  }

  const existing = await db.productionTask.findMany({
    where: { orderId: order.id },
    select: { garmentPiece: true, status: true },
  });
  for (const t of body.tasks) {
    const clash = existing.find(
      (e) =>
        e.garmentPiece.trim().toLowerCase() === t.garmentPiece.trim().toLowerCase() &&
        e.status !== "CANCELLED"
    );
    if (clash) {
      throw new ApiError(
        409,
        `A task for "${t.garmentPiece}" already exists on order ${order.orderNumber}.`
      );
    }
  }

  // Resolve assigned staff accounts
  const staffIds = [...new Set(body.tasks.map((t) => t.assignedStaffId).filter(Boolean))] as string[];
  const staff = staffIds.length
    ? await db.user.findMany({ where: { id: { in: staffIds }, isActive: true } })
    : [];
  const staffMap = new Map(staff.map((s) => [s.id, s]));
  for (const t of body.tasks) {
    if (t.assignedStaffId && !staffMap.has(t.assignedStaffId)) {
      throw new ApiError(400, "One of the selected staff accounts is invalid or inactive.");
    }
  }

  // Pre-compute codes and applicable stages BEFORE opening the transaction
  // (reads outside, writes inside — avoids SQLite interactive-transaction
  // timeouts from mixing clients).
  const taskCount = await db.productionTask.count();
  const stageCache = new Map<string, Awaited<ReturnType<typeof getApplicableStages>>>();
  for (const t of body.tasks) {
    if (!stageCache.has(t.category)) {
      stageCache.set(t.category, await getApplicableStages(t.category));
    }
  }

  let counter = taskCount;
  const created = await db.$transaction(async (tx) => {
    const results: Array<{ id: string; code: string; garmentPiece: string }> = [];
    for (const t of body.tasks) {
      counter += 1;
      const code = `TASK-${String(counter).padStart(4, "0")}`;
      const assigned = t.assignedStaffId ? staffMap.get(t.assignedStaffId)! : null;
      const task = await tx.productionTask.create({
        data: {
          code,
          orderId: order.id,
          garmentPiece: t.garmentPiece.trim(),
          category: t.category,
          instructions: t.instructions || null,
          deadline: t.deadline ? new Date(t.deadline) : order.expectedDeliveryDate,
          priority: t.priority,
          status: assigned ? "ASSIGNED" : "UNASSIGNED",
          progress: 0,
          assignedStaffId: assigned?.id ?? null,
          createdById: user.id,
        },
      });
      // Initialize checklist stage states (all NOT_STARTED) for applicable stages.
      const stages = stageCache.get(t.category)!;
      if (stages.length > 0) {
        await tx.taskStageState.createMany({
          data: stages.map((s) => ({ taskId: task.id, stageId: s.id, state: "NOT_STARTED" })),
        });
      }
      if (assigned) {
        await tx.taskAssignment.create({
          data: {
            taskId: task.id,
            staffId: assigned.id,
            staffName: assigned.name,
            assignedById: user.id,
            assignedByName: user.name,
            type: "ASSIGN",
          },
        });
        await tx.taskStatusChange.create({
          data: {
            taskId: task.id,
            userId: user.id,
            userName: user.name,
            fromStatus: "UNASSIGNED",
            toStatus: "ASSIGNED",
          },
        });
      }
      results.push({ id: task.id, code: task.code, garmentPiece: task.garmentPiece });
    }
    return results;
  });

  // Notifications after the transaction committed.
  for (const t of body.tasks) {
    const assigned = t.assignedStaffId ? staffMap.get(t.assignedStaffId) : null;
    if (assigned) {
      const createdTask = created.find((c) => c.garmentPiece === t.garmentPiece.trim());
      await notifyUsers(
        [assigned.id],
        "TASK_ASSIGNED",
        "New task assigned",
        `You were assigned ${createdTask?.code} — ${t.garmentPiece} (${order.orderNumber}, ${order.customer.name}).`,
        createdTask?.id,
        order.id
      );
    }
  }

  await logAudit(user, "TASKS_CREATED", "ORDER", order.id, {
    orderNumber: order.orderNumber,
    tasks: created.map((t) => ({ code: t.code, piece: t.garmentPiece })),
  });

  const withRelations = await db.productionTask.findMany({
    where: { id: { in: created.map((t) => t.id) } },
    include: taskInclude,
  });

  return json({ tasks: withRelations.map(serializeTask) }, 201);
});
