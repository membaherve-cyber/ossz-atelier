"use client";

// OSSZ ATELIER — application shell: sidebar (desktop), drawer (mobile),
// notifications bell with polling, user menu and sticky footer.

import { useCallback, useEffect, useRef, useState } from "react";
import { api } from "./client";
import { useAppStore, type View } from "./store";
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
import { ROLE_LABELS } from "@/lib/permissions";
import {
  Bell,
  Scissors,
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

type NavItem = { view: View; label: string; icon: React.ComponentType<{ className?: string }> };
type NavSection = { title: string; items: NavItem[] };

function navFor(user: SessionUser): NavSection[] {
  if (user.role === "STAFF") {
    return [
      {
        title: "Workspace",
        items: [
          { view: "dashboard", label: "My Dashboard", icon: LayoutDashboard },
          { view: "tasks", label: "My Tasks", icon: ClipboardList },
          { view: "notifications", label: "Notifications", icon: Bell },
        ],
      },
    ];
  }
  const sections: NavSection[] = [
    {
      title: "Overview",
      items: [{ view: "dashboard", label: "Dashboard", icon: LayoutDashboard }],
    },
    {
      title: "Production",
      items: [
        { view: "tasks", label: "Production", icon: Shirt },
        { view: "task-create", label: "Create Tasks", icon: ClipboardList },
        { view: "orders", label: "Orders", icon: Package },
        { view: "customers", label: "Customers", icon: Users },
      ],
    },
    {
      title: "Insights",
      items: [
        { view: "performance", label: "Performance", icon: TrendingUp },
        { view: "incentives", label: "Incentives", icon: Gift },
        { view: "reports", label: "Reports", icon: FileBarChart },
      ],
    },
  ];
  if (user.role === "MANAGEMENT") {
    sections.push({
      title: "Administration",
      items: [
        { view: "users", label: "Users & Roles", icon: FolderKanban },
        { view: "stages", label: "Production Stages", icon: Settings2 },
        { view: "audit", label: "Audit History", icon: ScrollText },
      ],
    });
  } else {
    sections.push({
      title: "Administration",
      items: [
        { view: "users", label: "Staff Accounts", icon: FolderKanban },
        { view: "stages", label: "Production Stages", icon: Settings2 },
      ],
    });
  }
  return sections;
}

const VIEW_TITLES: Record<View, string> = {
  dashboard: "Dashboard",
  tasks: "Production",
  "task-create": "Create Production Tasks",
  "task-detail": "Task Detail",
  orders: "Orders",
  customers: "Customers",
  performance: "Staff Performance",
  incentives: "Incentive Management",
  reports: "Reports",
  users: "Users & Roles",
  audit: "Audit History",
  stages: "Production Stages",
  notifications: "Notifications",
};

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
  return (
    <nav className="flex flex-col gap-5 px-3 py-4" aria-label="Main navigation">
      {sections.map((section) => (
        <div key={section.title}>
          <p className="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-stone-400">
            {section.title}
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
                      ? "bg-amber-500/15 text-amber-300"
                      : "text-stone-300 hover:bg-stone-800/60 hover:text-stone-100"
                  )}
                >
                  <item.icon className="size-4 shrink-0" />
                  <span className="truncate">{item.label}</span>
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

  const [notifications, setNotifications] = useState<NotificationRecord[]>([]);
  const [bellOpen, setBellOpen] = useState(false);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);

  const refreshBell = useCallback(async () => {
    try {
      const res = await api.get<{ notifications: NotificationRecord[]; unreadCount: number }>(
        "/api/atelier/notifications?limit=15"
      );
      setNotifications(res.notifications);
      setUnreadCount(res.unreadCount);
    } catch {
      // ignore polling failures
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
          <div className="flex items-center gap-3 px-5 h-16 border-b border-sidebar-border">
            <div className="rounded-lg bg-amber-500 p-1.5">
              <Scissors className="size-4 text-stone-950" />
            </div>
            <div className="min-w-0">
              <p className="font-display font-bold leading-none">OSSZ ATELIER</p>
              <p className="text-[9px] tracking-[0.2em] text-stone-400 mt-1">PRODUCTION MGMT</p>
            </div>
          </div>
          <div className="flex-1 overflow-y-auto">
            <NavList sections={sections} view={view} navigate={navigate} />
          </div>
          <div className="p-4 border-t border-sidebar-border text-[11px] text-stone-500">
            Independent production system
          </div>
        </aside>

        {/* Main column */}
        <div className="flex-1 flex flex-col min-w-0">
          <header className="sticky top-0 z-30 flex items-center gap-3 h-16 px-4 sm:px-6 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            {/* Mobile nav */}
            <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
              <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="lg:hidden" aria-label="Open menu">
                  <Menu className="size-5" />
                </Button>
              </SheetTrigger>
              <SheetContent
                side="left"
                className="w-72 p-0 bg-sidebar text-sidebar-foreground border-sidebar-border"
              >
                <SheetTitle className="sr-only">Navigation</SheetTitle>
                <div className="flex items-center gap-3 px-5 h-16 border-b border-sidebar-border">
                  <div className="rounded-lg bg-amber-500 p-1.5">
                    <Scissors className="size-4 text-stone-950" />
                  </div>
                  <p className="font-display font-bold">OSSZ ATELIER</p>
                </div>
                <NavList
                  sections={sections}
                  view={view}
                  navigate={navigate}
                  onNavigate={() => setMobileNavOpen(false)}
                />
              </SheetContent>
            </Sheet>

            <h1 className="font-display text-lg font-semibold truncate">{VIEW_TITLES[view]}</h1>

            <div className="ml-auto flex items-center gap-2">
              {/* Notifications bell */}
              <Popover open={bellOpen} onOpenChange={setBellOpen}>
                <PopoverTrigger asChild>
                  <Button variant="ghost" size="icon" className="relative" aria-label="Notifications">
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                      <span className="absolute -top-0.5 -right-0.5 min-w-4.5 h-4.5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">
                        {unreadCount > 99 ? "99+" : unreadCount}
                      </span>
                    )}
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-80 sm:w-96 p-0">
                  <div className="flex items-center justify-between px-4 py-3 border-b">
                    <p className="text-sm font-semibold">Notifications</p>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-7 text-xs"
                        onClick={() => navigate("notifications")}
                      >
                        View all
                      </Button>
                      {unreadCount > 0 && (
                        <Button variant="ghost" size="sm" className="h-7 text-xs" onClick={markAllRead}>
                          <CheckCheck className="size-3.5 mr-1" />
                          Mark all read
                        </Button>
                      )}
                    </div>
                  </div>
                  <ScrollArea className="max-h-96">
                    {notifications.length === 0 ? (
                      <p className="text-sm text-muted-foreground text-center py-8">
                        No notifications yet.
                      </p>
                    ) : (
                      <div className="divide-y">
                        {notifications.slice(0, 12).map((n) => (
                          <button
                            key={n.id}
                            className={cn(
                              "w-full text-left px-4 py-3 hover:bg-muted/60 transition-colors flex gap-2.5",
                              !n.isRead && "bg-amber-50/60"
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
                                <Circle className="size-2.5 fill-amber-500 text-amber-500" />
                              )}
                            </div>
                            <div className="min-w-0">
                              <p className="text-sm font-medium leading-snug">{n.title}</p>
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
                    className="flex items-center gap-2 rounded-full hover:bg-muted p-1 pr-2 transition-colors"
                    aria-label="Account menu"
                  >
                    <Avatar className="size-8">
                      <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                        {initials(user.name)}
                      </AvatarFallback>
                    </Avatar>
                    <div className="hidden sm:block text-left">
                      <p className="text-sm font-medium leading-none">{user.name}</p>
                      <p className="text-[11px] text-muted-foreground mt-0.5">
                        {ROLE_LABELS[user.role]}
                      </p>
                    </div>
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>
                    <p className="text-sm font-medium">{user.name}</p>
                    <p className="text-xs text-muted-foreground font-normal">{user.email}</p>
                    <p className="text-xs text-muted-foreground font-normal mt-0.5">
                      {ROLE_LABELS[user.role]}
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
                    Sign out
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </header>

          <main className="flex-1 px-4 sm:px-6 py-6 w-full max-w-[1400px] mx-auto">{children}</main>

          {/* Sticky footer */}
          <footer className="mt-auto border-t bg-background">
            <div className="max-w-[1400px] mx-auto px-4 sm:px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-1 text-xs text-muted-foreground">
              <p>© {new Date().getFullYear()} OSSZ Atelier — Independent Production Management System</p>
              <p>Own database · Own accounts · atelier.osszcollection.com</p>
            </div>
          </footer>
        </div>
      </div>
    </div>
  );
}
