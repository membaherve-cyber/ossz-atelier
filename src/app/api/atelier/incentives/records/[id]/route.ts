// OSSZ ATELIER — review (approve / reject) and payment recording for a single
// incentive record.
// PATCH /api/atelier/incentives/records/[id]
//   { action: "REVIEW",  status: "APPROVED" | "REJECTED", ... }  — Management only
//   { action: "PAYMENT", paymentStatus: "PAID", ... }            — Management only

import { z } from "zod";
import { db } from "@/lib/db";
import { ApiError, json, parseBody, withApi } from "@/lib/api";
import { requirePermission, requireUser } from "@/lib/auth";
import { logAudit } from "@/lib/atelier/audit";

type RecordPayload = {
  id: string;
  schemeId: string;
  schemeName: string;
  staffId: string;
  staffName: string;
  period: string;
  performanceScore: number;
  eligible: boolean;
  proposedAmount: number;
  approvedAmount: number | null;
  status: string;
  approvedByName: string | null;
  approvalComments: string | null;
  managementComments: string | null;
  paymentStatus: string;
  paymentDate: string | null;
};

type RecordRow = {
  id: string;
  schemeId: string;
  schemeName: string;
  staffId: string;
  staffName: string;
  period: string;
  performanceScore: number;
  eligible: boolean;
  proposedAmount: number;
  approvedAmount: number | null;
  status: string;
  approvedById: string | null;
  approvedByName: string | null;
  approvalComments: string | null;
  managementComments: string | null;
  paymentStatus: string;
  paymentDate: Date | null;
};

function serializeRecord(r: RecordRow): RecordPayload {
  return {
    id: r.id,
    schemeId: r.schemeId,
    schemeName: r.schemeName,
    staffId: r.staffId,
    staffName: r.staffName,
    period: r.period,
    performanceScore: r.performanceScore,
    eligible: r.eligible,
    proposedAmount: r.proposedAmount,
    approvedAmount: r.approvedAmount,
    status: r.status,
    approvedByName: r.approvedByName,
    approvalComments: r.approvalComments,
    managementComments: r.managementComments,
    paymentStatus: r.paymentStatus,
    paymentDate: r.paymentDate ? r.paymentDate.toISOString() : null,
  };
}

const reviewSchema = z.object({
  action: z.literal("REVIEW"),
  status: z.enum(["APPROVED", "REJECTED"]),
  approvedAmount: z.number().min(0).optional(),
  approvalComments: z.string().trim().max(2000).optional(),
  managementComments: z.string().trim().max(2000).optional(),
});

const paymentSchema = z.object({
  action: z.literal("PAYMENT"),
  paymentStatus: z.literal("PAID"),
  paymentDate: z
    .string()
    .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid payment date")
    .optional(),
});

const patchSchema = z.discriminatedUnion("action", [reviewSchema, paymentSchema]);

export const PATCH = withApi(
  async (req: Request, { params }: { params: Promise<{ id: string }> }) => {
    const { id } = await params;
    const body = await parseBody(req, patchSchema);

    const record = await db.incentiveRecord.findUnique({ where: { id } });
    if (!record) throw new ApiError(404, "Incentive record not found.");

    if (body.action === "REVIEW") {
      // Approve / reject — Management only (Admin cannot override).
      const user = await requireUser();
      if (user.role !== "MANAGEMENT") {
        throw new ApiError(403, "Only Management can approve or reject incentive payments.");
      }

      if (record.status !== "PENDING") {
        throw new ApiError(
          400,
          `This record has already been ${record.status.toLowerCase()}. Only pending records can be reviewed.`
        );
      }

      if (body.status === "APPROVED") {
        const amount = body.approvedAmount ?? record.proposedAmount;
        const updated = await db.incentiveRecord.update({
          where: { id },
          data: {
            status: "APPROVED",
            approvedAmount: amount,
            approvedById: user.id,
            approvedByName: user.name,
            approvalComments: body.approvalComments ?? null,
            managementComments: body.managementComments ?? null,
          },
        });
        await logAudit(user, "INCENTIVE_APPROVED", "INCENTIVE_RECORD", id, {
          recordId: id,
          amount,
          staffName: updated.staffName,
          period: updated.period,
        });
        return json({ record: serializeRecord(updated) });
      }

      const updated = await db.incentiveRecord.update({
        where: { id },
        data: {
          status: "REJECTED",
          approvedById: user.id,
          approvedByName: user.name,
          approvalComments: body.approvalComments ?? null,
          managementComments: body.managementComments ?? null,
        },
      });
      await logAudit(user, "INCENTIVE_REJECTED", "INCENTIVE_RECORD", id, {
        recordId: id,
        amount: record.proposedAmount,
        staffName: updated.staffName,
        period: updated.period,
      });
      return json({ record: serializeRecord(updated) });
    }

    // Payment recording — Management only.
    const user = await requirePermission("incentives.payment");
    if (record.status !== "APPROVED") {
      throw new ApiError(400, "Payment can only be recorded for approved incentive records.");
    }
    if (record.paymentStatus === "PAID") {
      throw new ApiError(400, "This incentive has already been marked as paid.");
    }

    const paymentDate = body.paymentDate ? new Date(body.paymentDate) : new Date();
    const updated = await db.incentiveRecord.update({
      where: { id },
      data: { paymentStatus: "PAID", paymentDate },
    });

    await logAudit(user, "INCENTIVE_PAYMENT_RECORDED", "INCENTIVE_RECORD", id, {
      recordId: id,
      staffName: updated.staffName,
      amount: updated.approvedAmount,
      paymentDate: paymentDate.toISOString(),
    });

    return json({ record: serializeRecord(updated) });
  }
);
