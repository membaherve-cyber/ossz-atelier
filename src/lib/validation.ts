import { z } from "zod";
import { ROLES } from "@/lib/permissions";
import { TASK_STATUSES } from "@/lib/production";

// Primitives -----------------------------------------------------------------

const isoDate = z
  .string()
  .min(1)
  .refine((v) => !Number.isNaN(new Date(v).getTime()), "Invalid date");

const optionalText = (max: number) =>
  z.string().max(max).optional().nullable().transform((v) => (v ? v : null));

// Auth ------------------------------------------------------------------------

export const loginSchema = z.object({
  email: z.string().trim().toLowerCase().min(3).max(200),
  password: z.string().min(1).max(200),
});

// Users -----------------------------------------------------------------------

export const roleEnum = z.enum(ROLES);

export const createUserSchema = z.object({
  name: z.string().trim().min(2).max(120),
  email: z.string().trim().toLowerCase().min(3).max(200),
  password: z.string().min(8).max(200),
  role: roleEnum,
});

export const updateUserSchema = z
  .object({
    name: z.string().trim().min(2).max(120).optional(),
    role: roleEnum.optional(),
    isActive: z.boolean().optional(),
    password: z.string().min(8).max(200).optional(),
  })
  .refine((v) => Object.keys(v).length > 0, "At least one field is required");

// Products --------------------------------------------------------------------

export const productComponentSchema = z.object({
  name: z.string().trim().min(1).max(80),
  defaultInstructions: optionalText(2000),
});

export const productSchema = z.object({
  name: z.string().trim().min(2).max(140),
  category: z.string().trim().min(2).max(80),
  description: optionalText(2000),
  basePrice: z.number().int().min(0).max(100_000_000),
  imageUrl: optionalText(500),
  isActive: z.boolean().optional(),
  components: z.array(productComponentSchema).max(30).optional(),
});

export const productUpdateSchema = productSchema.partial().refine(
  (v) => Object.keys(v).length > 0,
  "At least one field is required"
);

// Orders ----------------------------------------------------------------------

const measurementValue = z.union([z.string().max(200), z.number()]);
export const measurementsSchema = z
  .record(z.string().max(60), measurementValue)
  .optional()
  .nullable();

export const orderItemInputSchema = z.object({
  productId: z.string().min(1),
  quantity: z.number().int().min(1).max(99),
  unitPrice: z.number().int().min(0).max(100_000_000).optional(),
  measurements: measurementsSchema,
  instructions: optionalText(2000),
});

export const createOrderSchema = z.object({
  customerId: z.string().optional(),
  customer: z
    .object({
      name: z.string().trim().min(2).max(120),
      email: z.union([z.string().trim().toLowerCase().max(200), z.literal("")]).optional(),
      phone: z.union([z.string().trim().max(40), z.literal("")]).optional(),
    })
    .optional(),
  items: z.array(orderItemInputSchema).min(1).max(50),
  expectedDeliveryDate: isoDate.optional().nullable(),
  notes: optionalText(4000),
  paymentStatus: z.enum(["UNPAID", "DEPOSIT_PAID", "PAID"]).optional(),
});

export const orderStatusEnum = z.enum([
  "PENDING",
  "CONFIRMED",
  "IN_PRODUCTION",
  "READY",
  "DISPATCHED",
  "COMPLETED",
  "CANCELLED",
]);

export const paymentStatusEnum = z.enum(["UNPAID", "DEPOSIT_PAID", "PAID", "REFUNDED"]);

export const updateOrderSchema = z
  .object({
    status: orderStatusEnum.optional(),
    paymentStatus: paymentStatusEnum.optional(),
    expectedDeliveryDate: isoDate.nullable().optional(),
    notes: z.string().max(4000).nullable().optional(),
  })
  .refine((v) => Object.keys(v).length > 0, "At least one field is required");

// Production breakdown ---------------------------------------------------------

export const pieceInputSchema = z.object({
  name: z.string().trim().min(1).max(80),
  productionInstructions: optionalText(2000),
});

export const createBreakdownSchema = z.object({
  items: z
    .array(
      z.object({
        orderItemId: z.string().min(1),
        pieces: z.array(pieceInputSchema).min(1).max(30),
      })
    )
    .min(1)
    .max(50)
    .optional(),
});

// Production workflow -----------------------------------------------------------

const progressValue = z.number().int().min(0).max(100);

export const assignSchema = z.object({
  staffId: z.string().min(1),
  dueAt: isoDate.nullable().optional(),
  productionInstructions: optionalText(2000),
  reason: optionalText(1000),
});

export const progressSchema = z.object({
  newProgress: progressValue,
  expectedProgress: progressValue,
  note: optionalText(2000),
  issueReason: optionalText(2000),
  correctionReason: optionalText(2000),
});

export const submitSchema = z.object({
  note: optionalText(2000),
});

export const blockSchema = z.object({
  reason: z.string().trim().min(3).max(2000),
});

export const resolveSchema = z.object({
  note: optionalText(2000),
});

export const inspectSchema = z.object({
  result: z.enum(["APPROVED", "CHANGES_REQUESTED"]),
  inspectionNotes: optionalText(2000),
  correctionInstructions: z
    .string()
    .max(2000)
    .optional()
    .nullable()
    .transform((v) => (v ? v : null)),
});

export const reopenSchema = z.object({
  reason: z.string().trim().min(3).max(2000),
});

export const cancelSchema = z.object({
  reason: z.string().trim().min(3).max(2000),
});

export const deadlineSchema = z.object({
  dueAt: isoDate.nullable(),
  reason: optionalText(1000),
});

// Queries ------------------------------------------------------------------------

export const statusEnum = z.enum(TASK_STATUSES);

const intQuery = z.coerce.number().int();

export const taskListQuerySchema = z.object({
  mine: z.stringbool().optional(),
  staffId: z.string().optional(),
  status: statusEnum.optional(),
  orderId: z.string().optional(),
  garment: z.string().max(80).optional(),
  q: z.string().max(120).optional(),
  progressMin: intQuery.min(0).max(100).optional(),
  progressMax: intQuery.min(0).max(100).optional(),
  overdue: z.stringbool().optional(),
  assignedFrom: isoDate.optional(),
  assignedTo: isoDate.optional(),
  dueFrom: isoDate.optional(),
  dueTo: isoDate.optional(),
  sort: z
    .enum(["updatedAt_desc", "updatedAt_asc", "dueAt_asc", "dueAt_desc", "progress_asc", "progress_desc", "createdAt_desc"])
    .optional(),
  page: intQuery.min(1).max(10000).optional(),
  pageSize: intQuery.min(1).max(100).optional(),
});

export const orderListQuerySchema = z.object({
  q: z.string().max(120).optional(),
  status: orderStatusEnum.optional(),
  page: intQuery.min(1).max(10000).optional(),
  pageSize: intQuery.min(1).max(100).optional(),
});

export const notificationsReadSchema = z.object({
  ids: z.array(z.string().min(1)).max(200).optional(),
  all: z.boolean().optional(),
});
