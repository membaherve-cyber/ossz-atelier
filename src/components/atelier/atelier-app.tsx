"use client";

// OSSZ ATELIER — application root: session bootstrap + view router.

import { useEffect } from "react";
import { useAppStore } from "./store";
import { LoginView } from "./login-view";
import { AppShell } from "./app-shell";
import { DashboardView } from "./views/dashboard-view";
import { TasksView } from "./views/tasks-view";
import { TaskCreateView } from "./views/task-create-view";
import { TaskDetailView } from "./views/task-detail-view";
import { CustomersView } from "./views/customers-view";
import { OrdersView } from "./views/orders-view";
import { UsersView } from "./views/users-view";
import { PerformanceView } from "./views/performance-view";
import { IncentivesView } from "./views/incentives-view";
import { ReportsView } from "./views/reports-view";
import { AuditView } from "./views/audit-view";
import { StagesView } from "./views/stages-view";
import { NotificationsView } from "./views/notifications-view";
import { Scissors } from "lucide-react";

export default function AtelierApp() {
  const status = useAppStore((s) => s.status);
  const view = useAppStore((s) => s.view);
  const bootstrap = useAppStore((s) => s.bootstrap);

  useEffect(() => {
    bootstrap();
  }, [bootstrap]);

  if (status === "loading") {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4 bg-background">
        <div className="size-16 rounded-2xl bg-primary/10 flex items-center justify-center">
          <Scissors className="size-7 text-primary" />
        </div>
        <p className="font-display text-lg font-semibold">OSSZ Atelier</p>
        <p className="text-sm text-muted-foreground">Loading…</p>
      </div>
    );
  }

  if (status === "unauthenticated") {
    return <LoginView />;
  }

  return <AppShell>{renderView(view)}</AppShell>;
}

function renderView(view: string) {
  switch (view) {
    case "tasks":
      return <TasksView />;
    case "task-create":
      return <TaskCreateView />;
    case "task-detail":
      return <TaskDetailView />;
    case "orders":
      return <OrdersView />;
    case "customers":
      return <CustomersView />;
    case "users":
      return <UsersView />;
    case "performance":
      return <PerformanceView />;
    case "incentives":
      return <IncentivesView />;
    case "reports":
      return <ReportsView />;
    case "audit":
      return <AuditView />;
    case "stages":
      return <StagesView />;
    case "notifications":
      return <NotificationsView />;
    default:
      return <DashboardView />;
  }
}
