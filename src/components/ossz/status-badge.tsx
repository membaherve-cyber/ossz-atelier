"use client";

import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
  ORDER_STATUS_LABELS,
  PAYMENT_STATUS_LABELS,
  TASK_STATUS_LABELS,
  type OrderStatus,
  type PaymentStatus,
  type TaskStatus,
} from "./types";

const TASK_BADGE_STYLES: Record<TaskStatus, string> = {
  UNASSIGNED: "bg-stone-500/12 text-stone-600 border-stone-500/25 dark:text-stone-300",
  ASSIGNED: "bg-amber-600/12 text-amber-700 border-amber-600/30 dark:text-amber-300",
  IN_PROGRESS: "bg-amber-600/90 text-white border-amber-700",
  AWAITING_INSPECTION: "bg-teal-600/12 text-teal-700 border-teal-600/30 dark:text-teal-300",
  CHANGES_REQUESTED: "bg-orange-600/12 text-orange-700 border-orange-600/30 dark:text-orange-300",
  APPROVED: "bg-green-600/90 text-white border-green-700",
  BLOCKED: "bg-red-600/90 text-white border-red-700",
  CANCELLED: "bg-stone-400/15 text-stone-500 line-through border-stone-400/25 dark:text-stone-400",
};

export function TaskStatusBadge({ status, className }: { status: TaskStatus; className?: string }) {
  return (
    <Badge variant="outline" className={cn("font-medium border", TASK_BADGE_STYLES[status], className)}>
      {TASK_STATUS_LABELS[status]}
    </Badge>
  );
}

const ORDER_BADGE_STYLES: Record<OrderStatus, string> = {
  PENDING: "bg-stone-500/12 text-stone-600 border-stone-500/25 dark:text-stone-300",
  CONFIRMED: "bg-amber-600/12 text-amber-700 border-amber-600/30 dark:text-amber-300",
  IN_PRODUCTION: "bg-primary/90 text-primary-foreground border-primary",
  READY: "bg-teal-600/12 text-teal-700 border-teal-600/30 dark:text-teal-300",
  DISPATCHED: "bg-violet-600/12 text-violet-700 border-violet-600/30 dark:text-violet-300",
  COMPLETED: "bg-green-600/90 text-white border-green-700",
  CANCELLED: "bg-red-600/12 text-red-700 border-red-600/30 dark:text-red-300",
};

export function OrderStatusBadge({ status, className }: { status: OrderStatus; className?: string }) {
  return (
    <Badge variant="outline" className={cn("font-medium border", ORDER_BADGE_STYLES[status], className)}>
      {ORDER_STATUS_LABELS[status]}
    </Badge>
  );
}

const PAYMENT_BADGE_STYLES: Record<PaymentStatus, string> = {
  UNPAID: "bg-stone-500/10 text-stone-600 border-stone-500/25 dark:text-stone-300",
  DEPOSIT_PAID: "bg-amber-600/10 text-amber-700 border-amber-600/30 dark:text-amber-300",
  PAID: "bg-green-600/10 text-green-700 border-green-600/30 dark:text-green-300",
  REFUNDED: "bg-red-600/10 text-red-700 border-red-600/30 dark:text-red-300",
};

export function PaymentStatusBadge({ status, className }: { status: PaymentStatus; className?: string }) {
  return (
    <Badge variant="outline" className={cn("font-medium border", PAYMENT_BADGE_STYLES[status], className)}>
      {PAYMENT_STATUS_LABELS[status]}
    </Badge>
  );
}
