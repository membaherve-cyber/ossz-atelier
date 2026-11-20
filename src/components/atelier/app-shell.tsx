"use client";

// OSSZ ATELIER — application shell: sidebar (desktop), drawer (mobile),
// notifications bell with polling, user menu and sticky footer. Fully
// bilingual (EN/FR) with a language switch in the header.

import { useCallback, useEffect, useRef, useState } from "react";
import { api } from "./client";
import { useAppStore, type View } from "./store";
import { useI18n } from "./i18n/use-i18n";
import { notifTitle, roleLabel } from "./i18n/core";
import { LanguageToggle } from "./i18n/language-toggle";
import type { NotificationRecord, SessionUser } from "./types";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";
import { initials, timeAgo } from "@/lib/format";
import {
  Bell,
  LayoutDashboard,
  Shirt,
  ClipboardList,
  Package,
  Users,
  FolderKanban,
  TrendingUp,
  Gift,
  FileBarChart,
  Settings2,
  ScrollText,
  Menu,
  LogOut,
  CheckCheck,
  Circle,
} from "lucide-react";

type NavItem = { view: View; labelKey: string; icon: React.ComponentType<{ className?: string }> };
type NavSection = { titleKey: string; items: NavItem[] };

function navFor(user: SessionUser): NavSection[] {
  if (user.role === "STAFF") {
    return [
      {
        titleKey: "shell.section.workspace",
        items: [
          { view: "dashboard", labelKey: "shell.nav.myDashboard", icon: LayoutDashboard },
          { view: "tasks", labelKey: "shell.nav.myTasks", icon: ClipboardList },
          { view: "notifications", labelKey: "shell.nav.notifications", icon: Bell },
        ],
      },
    ];
  }
  const sections: NavSection[] = [
    {
      titleKey: "shell.section.overview",
      items: [{ view: "dashboard", labelKey: "shell.nav.dashboard", icon: LayoutDashboard }],
    },
    {
      titleKey: "shell.section.production",
      items: [
        // Fixed PRODUCTION-section order: Customers → Orders → Production →
        // Create Tasks (matches the atelier workflow: register a customer,
        // place their order, break it into production tasks).
        { view: "customers", labelKey: "shell.nav.customers", icon: Users },
        { view: "orders", labelKey: "shell.nav.orders", icon: Package },
        { view: "tasks", labelKey: "shell.nav.tasks", icon: Shirt },
        { view: "task-create", labelKey: "shell.nav.taskCreate", icon: ClipboardList },
      ],
    },
    {
      titleKey: "shell.section.insights",
      items: [
        { view: "performance", labelKey: "shell.nav.performance", icon: TrendingUp },
        { view: "incentives", labelKey: "shell.nav.incentives", icon: Gift },
        { view: "reports", labelKey: "shell.nav.reports", icon: FileBarChart },
      ],
    },
  ];
  if (user.role === "MANAGEMENT") {
    sections.push({
      titleKey: "shell.section.administration",
      items: [
        { view: "users", labelKey: "shell.nav.users", icon: FolderKanban },
        { view: "stages", labelKey: "shell.nav.stages", icon: Settings2 },
        { view: "audit", labelKey: "shell.nav.audit", icon: ScrollText },
      ],
    });
  } else {
    sections.push({
      titleKey: "shell.section.administration",
      items: [
        { view: "users", labelKey: "shell.nav.staffAccounts", icon: FolderKanban },
        { view: "stages", labelKey: "shell.nav.stages", icon: Settings2 },
      ],
    });
  }
  return sections;
}

function viewTitleKey(view: View, role: string): string {
  if (view === "users" && role !== "MANAGEMENT") return "shell.view.staffAccounts";
  const map: Record<View, string> = {
    dashboard: "shell.view.dashboard",
    tasks: "shell.view.tasks",
    "task-create": "shell.view.taskCreate",
    "task-detail": "shell.view.taskDetail",
    orders: "shell.view.orders",
    customers: "shell.view.customers",
    performance: "shell.view.performance",
    incentives: "shell.view.incentives",
    reports: "shell.view.reports",
    users: "shell.view.users",
    audit: "shell.view.audit",
    stages: "shell.view.stages",
    notifications: "shell.view.notifications",
  };
  return map[view];
}

function NavList({
  sections,
  view,
  navigate,
  onNavigate,
}: {
  sections: NavSection[];
  view: View;
  navigate: (view: View) => void;
  onNavigate?: () => void;
}) {
  const { t } = useI18n();
  return (
    <nav className="flex flex-col gap-5 px-3 py-4" aria-label={t("shell.navAria")}>
      {sections.map((section) => (
        <div key={section.titleKey}>
          <p className="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">
            {t(section.titleKey)}
          </p>
          <div className="flex flex-col gap-0.5">
            {section.items.map((item) => {
              const active = view === item.view;
              return (
                <button
                  key={item.view}
                  onClick={() => {
                    navigate(item.view);
                    onNavigate?.();
                  }}
                  aria-current={active ? "page" : undefined}
                  className={cn(
                    "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-left",
                    active
                      ? "bg-sidebar-primary/20 text-sidebar-primary"
                      : "text-stone-300 hover:bg-white/5 hover:text-white"
                  )}
                >
                  <item.icon className="size-4 shrink-0" />
                  <span className="truncate">{t(item.labelKey)}</span>
                </button>
              );
            })}
          </div>
        </div>
      ))}
    </nav>
  );
}

export function AppShell({ children }: { children: React.ReactNode }) {
  const user = useAppStore((s) => s.user);
  const view = useAppStore((s) => s.view);
  const navigate = useAppStore((s) => s.navigate);
  const logout = useAppStore((s) => s.logout);
  const unreadCount = useAppStore((s) => s.unreadCount);
  const setUnreadCount = useAppStore((s) => s.setUnreadCount);
  const openTask = useAppStore((s) => s.openTask);
  const { t, lang } = useI18n();

  const [notifications, setNotifications] = useState<NotificationRecord[]>([]);
  const [bellOpen, setBellOpen] = useState(false);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);

  const refreshBell = useCallback(async () => {
    try {
      const res = await api.get<{ notifications: NotificationRecord[]; unreadCount: number }>(
        "/api/atelier/notifications?limit=15"
      );
      // Defensive: a malformed (empty) response must never set state to
      // undefined, which would crash the bell dropdown render.
      setNotifications(Array.isArray(res?.notifications) ? res.notifications : []);
      setUnreadCount(typeof res?.unreadCount === "number" ? res.unreadCount : 0);
    } catch {
      // ignore polling failures — the badge is non-critical
    }
  }, [setUnreadCount]);

  useEffect(() => {
    const initial = setTimeout(refreshBell, 50);
    timer.current = setInterval(refreshBell, 30000);
    return () => {
      clearTimeout(initial);
      if (timer.current) clearInterval(timer.current);
    };
  }, [refreshBell]);

  async function markAllRead() {
    try {
      await api.patch("/api/atelier/notifications", { all: true });
      await refreshBell();
    } catch {
      // ignore
    }
  }

  async function markOneRead(id: string) {
    try {
      await api.patch("/api/atelier/notifications", { id });
      await refreshBell();
    } catch {
      // ignore
    }
  }

  if (!user) return null;
  const sections = navFor(user);

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <div className="flex flex-1 min-h-0">
        {/* Desktop sidebar */}
        <aside className="hidden lg:flex w-60 shrink-0 flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border sticky top-0 h-screen">
          <div className="flex items-center justify-center px-5 h-16 border-b border-sidebar-border">
            <div className="text-center min-w-0">
              <p className="font-display font-bold leading-none">OSSZ ATELIER</p>
              <p className="text-[9px] tracking-[0.2em] text-stone-400 mt-1.5">{t("shell.brandTag")}</p>
            </div>
          </div>
          <div className="flex-1 overflow-y-auto">
            <NavList sections={sections} view={view} navigate={navigate} />
          </div>
          <div className="p-4 border-t border-sidebar-border text-[11px] text-stone-500">
            {t("shell.independent")}
          </div>
        </aside>

        {/* Main column */}
        <div className="flex-1 flex flex-col min-w-0">
          <header className="sticky top-0 z-30 relative flex items-center gap-2 sm:gap-3 h-16 px-3 sm:px-6 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            {/* Mobile nav */}
            <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
              <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="lg:hidden" aria-label={t("shell.openMenu")}>
                  <Menu className="size-5" />
                </Button>
              </SheetTrigger>
              <SheetContent
                side="left"
                className="w-72 p-0 bg-sidebar text-sidebar-foreground border-sidebar-border"
              >
                <SheetTitle className="sr-only">{t("shell.navigation")}</SheetTitle>
                <div className="flex items-center justify-center px-5 h-16 border-b border-sidebar-border">
                  <div className="text-center min-w-0">
                    <p className="font-display font-bold leading-none">OSSZ ATELIER</p>
                    <p className="text-[9px] tracking-[0.2em] text-stone-400 mt-1.5">
                      {t("shell.brandTag")}
                    </p>
                  </div>
                </div>
                <NavList
                  sections={sections}
                  view={view}
                  navigate={navigate}
                  onNavigate={() => setMobileNavOpen(false)}
                />
              </SheetContent>
            </Sheet>

            {/* Brand logo — centered at the top of the dashboard. Clicking it
                returns to the dashboard view. Absolutely centered on >=360px
                viewports; on very narrow screens it flows between the menu and
                the header actions so it can never overlap them. */}
            <button
              type="button"
              onClick={() => navigate("dashboard")}
              aria-label={t("shell.logoAria")}
              className={cn(
                "absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10 shrink-0 rounded-md",
                "max-[359px]:static max-[359px]:translate-x-0 max-[359px]:translate-y-0 max-[359px]:mx-auto"
              )}
            >
              <img
                src="/ossz-logo.png"
                alt=""
                className="h-7 sm:h-9 w-auto rounded-md ring-1 ring-black/10"
              />
            </button>

            <h1 className="hidden sm:block font-display text-lg font-semibold truncate min-w-0 max-w-[calc(50%-6.5rem)]">
              {t(viewTitleKey(view, user.role))}
            </h1>

            <div className="ml-auto max-[359px]:ml-0 flex items-center gap-1 sm:gap-1.5">
              {/* Language switch */}
              <LanguageToggle />

              {/* Notifications bell */}
              <Popover open={bellOpen} onOpenChange={setBellOpen}>
                <PopoverTrigger asChild>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-8 w-8 sm:h-9 sm:w-9"
                    aria-label={t("shell.bellAria")}
                  >
                    <Bell className="size-4 sm:size-5" />
                    {unreadCount > 0 && (
                      <span className="absolute -top-0.5 -right-0.5 min-w-4.5 h-4.5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">
                        {unreadCount > 99 ? "99+" : unreadCount}
                      </span>
                    )}
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-80 sm:w-96 p-0">
                  <div className="flex items-center justify-between px-4 py-3 border-b">
                    <p className="text-sm font-semibold">{t("shell.bell.title")}</p>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-7 text-xs"
                        onClick={() => navigate("notifications")}
                      >
                        {t("shell.bell.viewAll")}
                      </Button>
                      {unreadCount > 0 && (
                        <Button variant="ghost" size="sm" className="h-7 text-xs" onClick={markAllRead}>
                          <CheckCheck className="size-3.5 mr-1" />
                          {t("shell.bell.markAllRead")}
                        </Button>
                      )}
                    </div>
                  </div>
                  <ScrollArea className="max-h-96">
                    {notifications.length === 0 ? (
                      <p className="text-sm text-muted-foreground text-center py-8">
                        {t("shell.bell.empty")}
                      </p>
                    ) : (
                      <div className="divide-y">
                        {notifications.slice(0, 12).map((n) => (
                          <button
                            key={n.id}
                            className={cn(
                              "w-full text-left px-4 py-3 hover:bg-muted/60 transition-colors flex gap-2.5",
                              !n.isRead && "bg-accent/60"
                            )}
                            onClick={() => {
                              markOneRead(n.id);
                              if (n.taskId) {
                                openTask(n.taskId);
                                setBellOpen(false);
                              } else {
                                setBellOpen(false);
                              }
                            }}
                          >
                            <div className="mt-0.5 shrink-0">
                              {n.isRead ? (
                                <Circle className="size-2.5 text-transparent" />
                              ) : (
                                <Circle className="size-2.5 fill-primary text-primary" />
                              )}
                            </div>
                            <div className="min-w-0">
                              <p className="text-sm font-medium leading-snug">
                                {notifTitle(n.type, n.title, lang)}
                              </p>
                              {n.message && (
                                <p className="text-xs text-muted-foreground line-clamp-2 mt-0.5">{n.message}</p>
                              )}
                              <p className="text-[11px] text-muted-foreground mt-1">{timeAgo(n.createdAt)}</p>
                            </div>
                          </button>
                        ))}
                      </div>
                    )}
                  </ScrollArea>
                </PopoverContent>
              </Popover>

              {/* User menu */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button
                    className="flex items-center gap-2 rounded-full hover:bg-muted p-0 sm:p-1 sm:pr-2 transition-colors"
                    aria-label={t("shell.accountMenu")}
                  >
                    <Avatar className="size-8">
                      <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                        {initials(user.name)}
                      </AvatarFallback>
                    </Avatar>
                    <div className="hidden sm:block text-left">
                      <p className="text-sm font-medium leading-none">{user.name}</p>
                      <p className="text-[11px] text-muted-foreground mt-0.5">
                        {user.code ? `${user.code} · ` : ""}{roleLabel(user.role, lang)}
                      </p>
                    </div>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>
                    <p className="text-sm font-medium">{user.name}</p>
                    <p className="text-xs text-muted-foreground font-normal">{user.email}</p>
                    <p className="text-xs text-muted-foreground font-normal mt-0.5">
                      {roleLabel(user.role, lang)}
                    </p>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={() => {
                      logout();
                    }}
                    className="text-red-700 focus:text-red-700"
                  >
                    <LogOut className="size-4 mr-2" />
                    {t("shell.user.signOut")}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </header>

          <main className="flex-1 px-4 sm:px-6 py-6 w-full max-w-[1400px] mx-auto">{children}</main>

          {/* Sticky footer */}
          <footer className="mt-auto border-t bg-background">
            <div className="max-w-[1400px] mx-auto px-4 sm:px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-1 text-xs text-muted-foreground">
              <p>{t("shell.footer.left", { year: new Date().getFullYear() })}</p>
              <p>{t("shell.footer.right")}</p>
            </div>
          </footer>
        </div>
      </div>
    </div>
  );
}
