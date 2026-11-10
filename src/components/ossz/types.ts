// Shared types mirroring the API responses.

export type Role = "ADMIN" | "MANAGER" | "INSPECTOR" | "TAILOR";

export type SessionUser = {
  id: string;
  email: string;
  name: string;
  role: Role;
  permissions: string[];
};

export type TaskStatus =
  | "UNASSIGNED"
  | "ASSIGNED"
  | "IN_PROGRESS"
  | "AWAITING_INSPECTION"
  | "CHANGES_REQUESTED"
  | "APPROVED"
  | "BLOCKED"
  | "CANCELLED";

export type OrderStatus =
  | "PENDING"
  | "CONFIRMED"
  | "IN_PRODUCTION"
  | "READY"
  | "DISPATCHED"
  | "COMPLETED"
  | "CANCELLED";

export type PaymentStatus = "UNPAID" | "DEPOSIT_PAID" | "PAID" | "REFUNDED";

export type StaffRef = { id: string; name: string; role?: Role };

export type TaskListItem = {
  id: string;
  pieceName: string;
  status: TaskStatus;
  progressPercentage: number;
  dueAt: string | null;
  assignedAt: string | null;
  assignedStaff: StaffRef | null;
  productionInstructions: string | null;
  blockedReason: string | null;
  createdAt: string;
  updatedAt: string;
  order: { id: string; reference: string; status: OrderStatus; expectedDeliveryDate: string | null };
  product: { id: string; name: string; category: string };
  overdue: boolean;
};

export type ProgressUpdate = {
  id: string;
  taskId: string;
  staffId: string;
  previousProgress: number;
  newProgress: number;
  previousStatus: TaskStatus;
  newStatus: TaskStatus;
  note: string | null;
  issueReason: string | null;
  isCorrection: boolean;
  correctionReason: string | null;
  createdAt: string;
  staff: { id: string; name: string };
};

export type InspectionRecord = {
  id: string;
  taskId: string;
  inspectorId: string;
  result: "APPROVED" | "CHANGES_REQUESTED";
  inspectionNotes: string | null;
  correctionInstructions: string | null;
  inspectedAt: string;
  inspector: { id: string; name: string };
};

export type AssignmentRecord = {
  id: string;
  taskId: string;
  previousStaffId: string | null;
  newStaffId: string;
  assignedById: string;
  reason: string | null;
  createdAt: string;
  newStaff: { id: string; name: string };
  previousStaff: { id: string; name: string } | null;
  assignedBy: { id: string; name: string };
};

export type ProductionEvent = {
  id: string;
  taskId: string;
  orderId: string;
  eventType: string;
  previousValue: string | null;
  newValue: string | null;
  actorId: string;
  reason: string | null;
  createdAt: string;
  actor: { id: string; name: string };
  task?: { id: string; pieceName: string } | null;
  order?: { id: string; reference: string } | null;
};

export type TaskDetail = {
  task: {
    id: string;
    pieceName: string;
    status: TaskStatus;
    progressPercentage: number;
    assignedStaff: StaffRef | null;
    assignedBy: StaffRef | null;
    assignedAt: string | null;
    dueAt: string | null;
    productionInstructions: string | null;
    blockedReason: string | null;
    blockedAt: string | null;
    createdAt: string;
    updatedAt: string;
    overdue: boolean;
    lastInspection: InspectionRecord | null;
  };
  order: {
    id: string;
    reference: string;
    status: OrderStatus;
    expectedDeliveryDate: string | null;
    paymentStatus?: PaymentStatus;
    totalAmount?: number;
  };
  customer: { name: string };
  item: {
    productName: string | null;
    category: string | null;
    measurements: string | null;
    instructions: string | null;
  };
  history: {
    progressUpdates: ProgressUpdate[];
    inspections: InspectionRecord[];
    assignments: AssignmentRecord[];
    events: ProductionEvent[];
  };
  viewerIsAssignedStaff: boolean;
};

export type OrderProductionSummary = {
  totalPieces: number;
  unassigned: number;
  assigned: number;
  inProgress: number;
  awaitingInspection: number;
  changesRequested: number;
  approved: number;
  blocked: number;
  cancelled: number;
  overdue: number;
  overallProgress: number;
  approvedRatio: number;
  productionComplete: boolean;
  staffIds: string[];
};

export type OrderListItem = {
  id: string;
  reference: string;
  status: OrderStatus;
  paymentStatus: PaymentStatus;
  totalAmount: number;
  expectedDeliveryDate: string | null;
  notes: string | null;
  createdAt: string;
  customer: { id: string; name: string };
  itemCount: number;
  production: OrderProductionSummary;
};

export type OrderDetail = {
  order: {
    id: string;
    reference: string;
    status: OrderStatus;
    paymentStatus: PaymentStatus;
    totalAmount: number;
    expectedDeliveryDate: string | null;
    notes: string | null;
    createdAt: string;
    customer: { id: string; name: string; email: string | null; phone: string | null };
    items: Array<{
      id: string;
      productId: string;
      productName: string;
      category: string;
      quantity: number;
      unitPrice: number;
      measurements: string | null;
      instructions: string | null;
      productComponents: string[];
    }>;
  };
  production: {
    summary: OrderProductionSummary;
    tasks: Array<{
      id: string;
      pieceName: string;
      status: TaskStatus;
      progressPercentage: number;
      assignedStaff: StaffRef | null;
      assignedAt: string | null;
      dueAt: string | null;
      productionInstructions: string | null;
      blockedReason: string | null;
      createdAt: string;
      updatedAt: string;
    }>;
    staff: Array<{ id: string; name: string; role: string }>;
    recentEvents: ProductionEvent[];
  };
};

export type Product = {
  id: string;
  name: string;
  description: string | null;
  category: string;
  basePrice: number;
  imageUrl: string | null;
  isActive: boolean;
  createdAt: string;
  components: Array<{ id: string; name: string; defaultInstructions: string | null; sortOrder: number }>;
};

export type StaffUser = {
  id: string;
  email: string;
  name: string;
  role: Role;
  isActive: boolean;
  createdAt: string;
  roleLabel: string;
  activeTaskCount: number;
};

export type WorkloadEntry = {
  staffId: string;
  name: string;
  role: string;
  total: number;
  notStarted: number;
  inProgress: number;
  awaitingInspection: number;
  changesRequested: number;
  approved: number;
  blocked: number;
  active: number;
  overdue: number;
  avgProgress: number;
};

export type NotificationItem = {
  id: string;
  title: string;
  body: string | null;
  taskId: string | null;
  orderId: string | null;
  type: string | null;
  isRead: boolean;
  createdAt: string;
  order: { reference: string } | null;
  task: { pieceName: string } | null;
};

export type ProductionSummaryData = {
  summary: {
    totalPieces: number;
    unassigned: number;
    assigned: number;
    inProgress: number;
    awaitingInspection: number;
    changesRequested: number;
    approved: number;
    blocked: number;
    cancelled: number;
    overdue: number;
    ordersAwaitingProduction: number;
    totalOrders: number;
  };
  recentEvents: ProductionEvent[];
};

export type ReportsData = {
  reports: {
    totals: {
      tasks: number;
      approved: number;
      awaitingInspection: number;
      reworkTasks: number;
      reworkRate: number | null;
      overdue: number;
    };
    avgAssignmentToApprovalMs: number | null;
    avgSubmissionToInspectionMs: number | null;
    perOrder: Array<{
      orderId: string;
      reference: string;
      customer: string;
    } & OrderProductionSummary>;
    perStaff: Array<{
      staffId: string;
      name: string;
      assigned: number;
      approved: number;
      reworked: number;
      avgCycleMs: number | null;
    }>;
    overdue: Array<{
      taskId: string;
      pieceName: string;
      reference: string;
      staff: string | null;
      dueAt: string | null;
      status: TaskStatus;
    }>;
  };
};

export const TASK_STATUS_LABELS: Record<TaskStatus, string> = {
  UNASSIGNED: "Unassigned",
  ASSIGNED: "Assigned",
  IN_PROGRESS: "In Progress",
  AWAITING_INSPECTION: "Awaiting Inspection",
  CHANGES_REQUESTED: "Changes Requested",
  APPROVED: "Approved",
  BLOCKED: "Blocked",
  CANCELLED: "Cancelled",
};

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  PENDING: "Pending",
  CONFIRMED: "Confirmed",
  IN_PRODUCTION: "In Production",
  READY: "Ready",
  DISPATCHED: "Dispatched",
  COMPLETED: "Completed",
  CANCELLED: "Cancelled",
};

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
  UNPAID: "Unpaid",
  DEPOSIT_PAID: "Deposit Paid",
  PAID: "Paid",
  REFUNDED: "Refunded",
};

export const EVENT_TYPE_LABELS: Record<string, string> = {
  TASK_CREATED: "Task created",
  PIECE_ASSIGNED: "Piece assigned",
  TASK_REASSIGNED: "Task reassigned",
  PRODUCTION_STARTED: "Production started",
  PROGRESS_UPDATED: "Progress updated",
  PROGRESS_CORRECTED: "Progress corrected",
  PRODUCTION_BLOCKED: "Production blocked",
  BLOCKER_RESOLVED: "Blocker resolved",
  SUBMITTED_FOR_INSPECTION: "Submitted for inspection",
  INSPECTION_COMPLETED: "Inspection completed",
  CORRECTIONS_REQUESTED: "Corrections requested",
  PIECE_APPROVED: "Piece approved",
  TASK_REOPENED: "Task reopened",
  TASK_CANCELLED: "Task cancelled",
  DEADLINE_CHANGED: "Deadline changed",
};
