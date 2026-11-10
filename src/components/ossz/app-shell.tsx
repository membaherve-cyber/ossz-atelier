"use client";

import { useState } from "react";
import {
  LayoutDashboard,
  ShoppingBag,
  Factory,
  Scissors,
  Package,
  Users,
  BarChart3,
  LogOut,
  Menu,
  Scissors as LogoIcon,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { cn } from "@/lib/utils";
import { useAppStore, can } from "./store";
import { useNav, type ViewId } from "./use-nav";
import { NotificationsBell } from "./notifications-bell";
import { ROLE_LABELS } from "@/lib/permissions";
import type { Role } from "./types";

type NavItem = { id: ViewId; label: string; icon: typeof LayoutDashboard; title?: string };

const NAV_ITEMS: NavItem[] = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard },
  { id: "orders", label: "Orders", icon: ShoppingBag },
  { id: "production", label: "Production", icon: Factory },
  { id: "mytasks", label: "My Tasks", icon: Scissors },
  { id: "products", label: "Products", icon: Package },
  { id: "staff", label: "Staff", icon: Users },
  { id: "reports", label: "Reports", icon: BarChart3 },
];

function NavLinks({
  items,
  activeView,
  onNavigate,
}: {
  items: NavItem[];
  activeView: ViewId;
  onNavigate: (view: ViewId) => void;
}) {
  return (
    <nav aria-label="Main navigation" className="flex flex-col gap-1 px-2">
      {items.map((item) => (
        <button
          key={item.id}
          onClick={() => onNavigate(item.id)}
          className={cn(
            "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors text-left",
            activeView === item.id || (activeView === "order" && item.id === "orders")
              ? "bg-sidebar-accent text-sidebar-accent-foreground"
              : "text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/50"
          )}
          aria-current={activeView === item.id ? "page" : undefined}
        >
          <item.icon className="size-4.5 shrink-0" />
          {item.label}
        </button>
      ))}
    </nav>
  );
}

function brandMark() {
  return (
    <div className="flex items-center gap-2.5 px-2">
      <div className="size-9 rounded-lg bg-primary flex items-center justify-center shrink-0">
        <LogoIcon className="size-4.5 text-primary-foreground" />
      </div>
      <div className="leading-tight">
        <p className="font-display text-lg font-semibold text-sidebar-foreground">OSSZ</p>
        <p className="text-[10px] uppercase tracking-[0.18em] text-sidebar-foreground/60">Atelier Backoffice</p>
      </div>
    </div>
  );
}

export function AppShell({
  children,
  title,
  subtitle,
  actions,
}: {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
  actions?: React.ReactNode;
}) {
  const user = useAppStore((s) => s.user);
  const logout = useAppStore((s) => s.logout);
  const { view, navigate } = useNav();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  if (!user) return null;

  const visibleItems = NAV_ITEMS.filter((item) => {
    switch (item.id) {
      case "orders":
        return can(user, "orders.view");
      case "production":
        return can(user, "production.view.all");
      case "staff":
        return can(user, "staff.manage") || can(user, "staff.view");
      case "reports":
        return can(user, "production.reports");
      default:
        return true;
    }
  });

  const activeView: ViewId = view ?? (user.role === "TAILOR" ? "mytasks" : "dashboard");

  const handleNavigate = (target: ViewId) => navigate({ view: target, order: null, task: null }, false);

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <div className="flex-1 flex min-h-0">
        {/* Desktop sidebar — sticky, capped at viewport height so the footer can
            stick to the viewport bottom on short pages. */}
        <aside className="hidden md:flex w-60 shrink-0 flex-col sticky top-0 max-h-screen bg-sidebar text-sidebar-foreground border-r border-sidebar-border">
          <div className="p-4 border-b border-sidebar-border">{brandMark()}</div>
          <div className="flex-1 overflow-y-auto py-4">
            <NavLinks items={visibleItems} activeView={activeView} onNavigate={handleNavigate} />
          </div>
          <div className="p-3 border-t border-sidebar-border">
            <div className="flex items-center gap-2.5 px-2">
              <Avatar className="size-8 border border-sidebar-border">
                <AvatarFallback className="bg-sidebar-accent text-sidebar-foreground text-xs font-semibold">
                  {user.name.split(/\s+/).slice(0, 2).map((p) => p[0]).join("")}
                </AvatarFallback>
              </Avatar>
              <div className="min-w-0 leading-tight">
                <p className="text-sm font-medium truncate">{user.name}</p>
                <p className="text-[11px] text-sidebar-foreground/60 truncate">
                  {ROLE_LABELS[user.role as Role] ?? user.role}
                </p>
              </div>
            </div>
          </div>
        </aside>

        {/* Main column */}
        <div className="flex-1 flex flex-col min-w-0">
          <header className="sticky top-0 z-30 flex items-center gap-3 border-b bg-background/95 backdrop-blur px-4 sm:px-6 h-16">
            {/* Mobile nav */}
            <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
              <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="md:hidden" aria-label="Open menu">
                  <Menu className="size-5" />
                </Button>
              </SheetTrigger>
              <SheetContent side="left" className="w-64 p-0 bg-sidebar text-sidebar-foreground border-sidebar-border">
                <SheetTitle className="sr-only">Navigation</SheetTitle>
                <div className="p-4 border-b border-sidebar-border">{brandMark()}</div>
                <div className="py-4">
                  <NavLinks
                    items={visibleItems}
                    activeView={activeView}
                    onNavigate={(v) => {
                      handleNavigate(v);
                      setMobileNavOpen(false);
                    }}
                  />
                </div>
              </SheetContent>
            </Sheet>

            <div className="flex-1 min-w-0">
              <h1 className="font-display text-lg sm:text-xl font-semibold truncate">{title}</h1>
              {subtitle && <p className="text-xs text-muted-foreground truncate">{subtitle}</p>}
            </div>

            <div className="flex items-center gap-1.5">
              {actions}
              <NotificationsBell />
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon" className="rounded-full" aria-label="Account menu">
                    <Avatar className="size-8">
                      <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                        {user.name.split(/\s+/).slice(0, 2).map((p) => p[0]).join("")}
                      </AvatarFallback>
                    </Avatar>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-52">
                  <DropdownMenuLabel>
                    <p>{user.name}</p>
                    <p className="text-xs font-normal text-muted-foreground">{user.email}</p>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={async () => {
                      await logout();
                    }}
                  >
                    <LogOut className="size-4" /> Sign out
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </header>

          <main className="flex-1 px-4 sm:px-6 py-6 w-full max-w-[1400px] mx-auto">{children}</main>
        </div>
      </div>

      {/* Sticky footer */}
      <footer className="mt-auto border-t bg-background">
        <div className="max-w-[1400px] mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-muted-foreground">
          <p>
            <span className="font-display font-semibold text-foreground">OSSZ Atelier</span> — Backoffice ·
            Garment production tracking
          </p>
          <p>Every piece, from cutting to approval.</p>
        </div>
      </footer>
    </div>
  );
}
