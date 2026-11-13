"use client";

import { useEffect } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useAppStore } from "./store";
import { LoginView } from "./login-view";
import { AppShell } from "./app-shell";
import { useNav } from "./use-nav";
import { DashboardView } from "./dashboard-view";
import { OrdersView } from "./orders-view";
import { OrderDetailView } from "./order-detail-view";
import { ProductionView } from "./production-view";
import { MyTasksView } from "./my-tasks-view";
import { ProductsView } from "./products-view";
import { StaffView } from "./staff-view";
import { ReportsView } from "./reports-view";
import { TaskDetailDialog } from "./task-detail-dialog";
import type { ViewId } from "./use-nav";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 15_000,
      retry: (failureCount, error) => {
        const status = (error as { status?: number }).status;
        if (status && status >= 400 && status < 500) return false;
        return failureCount < 2;
      },
    },
  },
});

function AuthenticatedApp() {
  const user = useAppStore((s) => s.user)!;
  const { view, get } = useNav();

  const defaultView: ViewId = user.role === "TAILOR" ? "mytasks" : "dashboard";

  // Server-side enforcement remains authoritative; this guard keeps the
  // navigation coherent when a URL points to an unauthorized view.
  const viewPermission: Partial<Record<ViewId, string>> = {
    orders: "orders.view",
    order: "orders.view",
    production: "production.view.all",
    staff: "staff.view",
    reports: "production.reports",
  };
  const requested = view ?? defaultView;
  const activeView: ViewId =
    viewPermission[requested] && !user.permissions.includes(viewPermission[requested]!)
      ? defaultView
      : requested;

  const orderId = get("order");
  const taskId = get("task");

  let content: React.ReactNode;
  let title = "Dashboard";
  let subtitle: string | undefined;

  switch (activeView) {
    case "orders":
      content = <OrdersView />;
      title = "Customer Orders";
      subtitle = "Order intake, fulfilment and production tracking";
      break;
    case "order":
      content = <OrderDetailView />;
      title = "Order Details";
      subtitle = "Order information and production tracking";
      break;
    case "production":
      content = <ProductionView />;
      title = "Production Management";
      subtitle = "Task allocation, inspection and progress across all orders";
      break;
    case "mytasks":
      content = <MyTasksView />;
      title = "My Production Tasks";
      subtitle = "Garment pieces assigned to your account";
      break;
    case "products":
      content = <ProductsView />;
      title = "Products & Garment Components";
      subtitle = "Catalogue of garments produced by the atelier";
      break;
    case "staff":
      content = <StaffView />;
      title = "Staff Accounts";
      subtitle = "Team members and their access";
      break;
    case "reports":
      content = <ReportsView />;
      title = "Production Reports";
      subtitle = "Performance metrics computed from recorded events";
      break;
    default:
      content = <DashboardView />;
      title = "Dashboard";
      subtitle = "Atelier overview";
  }

  return (
    <AppShell title={title} subtitle={subtitle}>
      {content}
      {taskId && <TaskDetailDialog />}
      {/* keep orderId referenced for deep-linking */}
      <span className="hidden">{orderId}</span>
    </AppShell>
  );
}

export default function OsszApp() {
  const status = useAppStore((s) => s.status);
  const bootstrap = useAppStore((s) => s.bootstrap);

  useEffect(() => {
    bootstrap();
  }, [bootstrap]);

  return (
    <QueryClientProvider client={queryClient}>
      {status === "loading" ? (
        <div className="min-h-screen flex flex-col items-center justify-center gap-4 bg-background">
          <div className="size-16 rounded-2xl bg-primary/10 flex items-center justify-center">
            <span className="font-display text-3xl font-bold text-primary">O</span>
          </div>
          <p className="text-sm text-muted-foreground">Loading OSSZ backoffice…</p>
        </div>
      ) : status === "unauthenticated" ? (
        <LoginView />
      ) : (
        <AuthenticatedApp />
      )}
    </QueryClientProvider>
  );
}
