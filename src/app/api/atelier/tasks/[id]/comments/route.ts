import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";
import { notifyManagement } from "@/lib/atelier/notify";
import { canAccessTask, taskInclude } from "@/lib/atelier/tasks";
import { TERMINAL_STATUSES } from "@/lib/atelier/constants";

type Params = { params: Promise<{ id: string }> };

async function loadAuthorizedTask(id: string, userId: string, role: string) {
  const task = await db.productionTask.findUnique({
    where: { id },
    include: taskInclude,
  });
  if (!task) throw new ApiError(404, "Task not found.");
  if (!canAccessTask({ id: userId, role } as never, { assignedStaffId: task.assignedStaffId })) {
    throw new ApiError(403, "You are not authorized to access this task.");
  }
  return task;
}

function serializeComments(
  rows: Array<{
    id: string;
    message: string;
    isIssue: boolean;
    userName: string;
    createdAt: Date;
  }>
) {
  return rows.map((c) => ({
    id: c.id,
    message: c.message,
    isIssue: c.isIssue,
    userName: c.userName,
    createdAt: c.createdAt.toISOString(),
  }));
}

/** GET /api/atelier/tasks/[id]/comments — production notes & reported issues. */
export const GET = withApi(async (_req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  await loadAuthorizedTask(id, user.id, user.role);
  const comments = await db.taskComment.findMany({
    where: { taskId: id },
    orderBy: { createdAt: "desc" },
  });
  return json({ comments: serializeComments(comments) });
});

const commentSchema = z.object({
  message: z.string().trim().min(1, "A message is required").max(1000),
  isIssue: z.boolean().optional().default(false),
});

/** POST /api/atelier/tasks/[id]/comments — add a note or report a problem. */
export const POST = withApi(async (req: Request, { params }: Params) => {
  const user = await requireUser();
  const { id } = await params;
  const body = await parseBody(req, commentSchema);

  const task = await loadAuthorizedTask(id, user.id, user.role);
  if (TERMINAL_STATUSES.includes(task.status as never)) {
    throw new ApiError(400, "This task is finished; comments are closed.");
  }

  const comment = await db.taskComment.create({
    data: {
      taskId: id,
      userId: user.id,
      userName: user.name,
      message: body.message,
      isIssue: body.isIssue,
    },
  });

  if (body.isIssue) {
    await notifyManagement(
      "ISSUE_REPORTED",
      "Production issue reported",
      `${user.name} reported an issue on ${task.code} — ${task.garmentPiece} (${task.order.orderNumber}): ${body.message}`,
      id,
      task.orderId
    );
    await logAudit(user, "ISSUE_REPORTED", "TASK", id, { message: body.message });
  } else {
    await logAudit(user, "COMMENT_ADDED", "TASK", id, {});
  }

  return json(
    {
      comment: {
        id: comment.id,
        message: comment.message,
        isIssue: comment.isIssue,
        userName: comment.userName,
        createdAt: comment.createdAt.toISOString(),
      },
    },
    201
  );
});
