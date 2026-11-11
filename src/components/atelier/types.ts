// OSSZ ATELIER — client-side types mirroring API payloads.

import type { Permission, Role } from "@/lib/permissions";
import type { SessionUser } from "@/lib/atelier/constants";

export type { SessionUser, Permission, Role };

// --- Tasks -------------------------------------------------------------------

export type TaskSummary = {
  id: string;
  code: string;
  orderId: string;
  orderNumber: string;
  customerId: string;
  customerName: string;
  garmentPiece: string;
  category: string;
  instructions: string | null;
  status: string;
  progress: number;
  priority: string;
  deadline: string | null;
  assignedStaffId: string | null;
  assignedStaffName: string | null;
  createdAt: string;
  updatedAt: string;
  completedAt: string | null;
  deliveredAt: string | null;
  cancelReason: string | null;
  blockedReason: string | null;
  overdue: boolean;
};

export type StageInfo = {
  id: string;
  code: string;
  name: string;
  sortOrder: number;
  weight: number;
  state: string;
};

export type AssignmentRecord = {
  id: string;
  staffId: string;
  staffName: string;
  assignedByName: string;
  type: string;
  note: string | null;
  createdAt: string;
};

export type StatusChangeRecord = {
  id: string;
  fromStatus: string;
  toStatus: string;
  reason: string | null;
  userName: string;
  createdAt: string;
};

export type StageUpdateRecord = {
  id: string;
  stageName: string;
  previousState: string;
  newState: string;
  comment: string | null;
  userName: string;
  createdAt: string;
};

export type ProgressHistoryRecord = {
  id: string;
  previousProgress: number;
  newProgress: number;
  source: string;
  note: string | null;
  userName: string;
  createdAt: string;
};

export type InspectionRecord = {
  id: string;
  decision: string;
  comments: string | null;
  inspectorName: string;
  createdAt: string;
};

export type TaskCommentRecord = {
  id: string;
  message: string;
  isIssue: boolean;
  userName: string;
  createdAt: string;
};

export type TaskDetail = {
  task: TaskSummary;
  stages: StageInfo[];
  assignments: AssignmentRecord[];
  statusChanges: StatusChangeRecord[];
  stageUpdates: StageUpdateRecord[];
  progressHistory: ProgressHistoryRecord[];
  inspections: InspectionRecord[];
  comments: TaskCommentRecord[];
};

export type TaskListResponse = {
  tasks: TaskSummary[];
  total: number;
  page: number;
  pageSize: number;
};

// --- Customers & orders --------------------------------------------------------

export type Customer = {
  id: string;
  code: string;
  name: string;
  phone: string;
  email: string | null;
  address: string | null;
  notes: string | null;
  createdAt: string;
  orderCount?: number;
};

export type OrderSummary = {
  id: string;
  orderNumber: string;
  customerId: string;
  customerName: string;
  customerPhone: string | null;
  orderDate: string;
  expectedDeliveryDate: string | null;
  description: string;
  garmentCategory: string;
  numberOfPieces: number;
  instructions: string | null;
  priority: string;
  notes: string | null;
  createdAt: string;
  taskCount: number;
  completedTaskCount: number;
  avgProgress: number;
};

// --- Users ---------------------------------------------------------------------

export type UserRecord = {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  jobTitle: string | null;
  role: string;
  isActive: boolean;
  createdAt: string;
  activeTaskCount?: number;
};

// --- Notifications ---------------------------------------------------------------

export type NotificationRecord = {
  id: string;
  type: string;
  title: string;
  message: string | null;
  taskId: string | null;
  orderId: string | null;
  isRead: boolean;
  createdAt: string;
};

// --- Dashboard --------------------------------------------------------------------

export type ManagerDashboard = {
  role: "MANAGEMENT" | "ADMIN";
  stats: {
    totalOrders: number;
    activeTasks: number;
    unassigned: number;
    ongoing: number;
    awaitingInspection: number;
    approved: number;
    readyForPickup: number;
    delivered: number;
    blocked: number;
    overdue: number;
  };
  overdueTasks: TaskSummary[];
  dueSoon: TaskSummary[];
  staffWorkload: Array<{ staffId: string; staffName: string; activeTasks: number; overdue: number; awaitingInspection: number }>;
  recentActivity: Array<{ id: string; userName: string | null; action: string; entityType: string | null; entityId: string | null; createdAt: string }>;
};

export type StaffDashboard = {
  role: "STAFF";
  stats: {
    myActive: number;
    myAwaiting: number;
    myCompletedThisMonth: number;
    myOverdue: number;
  };
  myTasks: TaskSummary[];
  upcomingDeadlines: TaskSummary[];
  recentNotifications: NotificationRecord[];
};

// --- Stages config -------------------------------------------------------------------

export type StageConfig = {
  id: string;
  code: string;
  name: string;
  sortOrder: number;
  weight: number;
  appliesTo: string;
  isActive: boolean;
};

// --- Performance ----------------------------------------------------------------------

export type PerformanceWeights = {
  productivityWeight: number;
  timelyWeight: number;
  qualityWeight: number;
  reliabilityWeight: number;
  managementWeight: number;
  pieceTarget: number;
};

export type StaffPerformanceRow = {
  staffId: string;
  staffName: string;
  staffEmail: string;
  isActive: boolean;
  metrics: {
    tasksAssigned: number;
    tasksCompleted: number;
    piecesCompleted: number;
    onTimeCompletions: number;
    overdueTasks: number;
    avgCompletionHours: number | null;
    qualityApprovalRate: number;
    correctionsReturned: number;
    blockedTasks: number;
  };
  scores: {
    productivity: number;
    timeliness: number;
    quality: number;
    reliability: number;
    management: number;
  };
  overall: number;
  managementAssessed: boolean;
};

export type PerformanceResponse = {
  range: { type: string; value: string; label: string; months: number };
  staff: StaffPerformanceRow[];
};

// --- Incentives --------------------------------------------------------------------------

export type IncentiveSchemeRecord = {
  id: string;
  name: string;
  periodType: string;
  minScore: number | null;
  amount: number | null;
  description: string | null;
  isActive: boolean;
  createdByName: string | null;
};

export type IncentiveRecordRow = {
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

// --- Audit ---------------------------------------------------------------------------------

export type AuditRecord = {
  id: string;
  userName: string | null;
  action: string;
  entityType: string | null;
  entityId: string | null;
  details: string | null;
  createdAt: string;
};
