"use client";

// OSSZ ATELIER — personal notifications inbox (all roles). Unread items are
// highlighted with a warm amber tint; clicking an item marks it read and jumps
// to the related task when there is one.

import { useCallback, useEffect, useMemo, useState } from "react";
import { api, ApiClientError } from "../client";
import { useAppStore } from "../store";
import type { NotificationRecord } from "../types";
import { EmptyState, PageHeader } from "../ui-bits";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useToast } from "@/hooks/use-toast";
import { timeAgo } from "@/lib/format";
import { AlertTriangle, Bell, CheckCheck } from "lucide-react";

type NotificationsResponse = {
  notifications: NotificationRecord[];
  unreadCount: number;
};

export function NotificationsView() {
  const { toast } = useToast();
  const openTask = useAppStore((s) => s.openTask);
  const setUnreadCount = useAppStore((s) => s.setUnreadCount);
  const [data, setData] = useState<NotificationsResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState<"all" | "unread">("all");
  const [markingAll, setMarkingAll] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get<NotificationsResponse>("/api/atelier/notifications?limit=100");
      setData(res);
      setUnreadCount(res.unreadCount);
      setError(null);
    } catch (err) {
      setError(err instanceof ApiClientError ? err.message : "Unexpected error.");
    }
  }, [setUnreadCount]);

  useEffect(() => {
    load();
  }, [load]);

  const notifications = data?.notifications ?? [];
  const unreadCount = data?.unreadCount ?? 0;
  const visible = useMemo(
    () => (tab === "unread" ? notifications.filter((n) => !n.isRead) : notifications),
    [tab, notifications]
  );

  const applyRead = (id: string) => {
    setData((d) =>
      d
        ? {
            notifications: d.notifications.map((n) => (n.id === id ? { ...n, isRead: true } : n)),
            unreadCount: Math.max(0, d.unreadCount - 1),
          }
        : d
    );
    setUnreadCount(Math.max(0, unreadCount - 1));
  };

  const open = async (n: NotificationRecord) => {
    if (!n.isRead) {
      // Fire and forget: the row highlights immediately, errors surface as toast.
      try {
        await api.patch("/api/atelier/notifications", { id: n.id });
        applyRead(n.id);
      } catch (err) {
        toast({
          title: "Could not mark the notification as read",
          description: err instanceof ApiClientError ? err.message : "Unexpected error.",
          variant: "destructive",
        });
      }
    }
    if (n.taskId) openTask(n.taskId);
  };

  const markAll = async () => {
    setMarkingAll(true);
    try {
      const res = await api.patch<{ unreadCount: number }>("/api/atelier/notifications", {
        all: true,
      });
      setData((d) =>
        d
          ? { notifications: d.notifications.map((n) => ({ ...n, isRead: true })), unreadCount: 0 }
          : d
      );
      setUnreadCount(res.unreadCount);
      toast({ title: "All notifications marked as read" });
    } catch (err) {
      toast({
        title: "Could not mark notifications as read",
        description: err instanceof ApiClientError ? err.message : "Unexpected error.",
        variant: "destructive",
      });
    } finally {
      setMarkingAll(false);
    }
  };

  if (error) {
    return (
      <EmptyState
        icon={AlertTriangle}
        title="Could not load your notifications"
        description={error}
        action={
          <Button variant="outline" size="sm" onClick={load}>
            Try again
          </Button>
        }
      />
    );
  }

  return (
    <div>
      <PageHeader title="Notifications" description="Production alerts for your account.">
        <Button variant="outline" onClick={markAll} disabled={markingAll || unreadCount === 0}>
          <CheckCheck className="size-4 mr-1.5" />
          {markingAll ? "Marking…" : "Mark all as read"}
        </Button>
      </PageHeader>

      <Tabs value={tab} onValueChange={(v) => setTab(v as "all" | "unread")} className="mb-4">
        <TabsList>
          <TabsTrigger value="all">All ({notifications.length})</TabsTrigger>
          <TabsTrigger value="unread">
            Unread{unreadCount > 0 ? ` (${unreadCount})` : ""}
          </TabsTrigger>
        </TabsList>
      </Tabs>

      {data === null ? (
        <div className="space-y-2">
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
          <Skeleton className="h-16 w-full" />
        </div>
      ) : visible.length === 0 ? (
        <EmptyState
          icon={Bell}
          title="You're all caught up."
          description={
            tab === "unread"
              ? "No unread notifications — new alerts will appear here."
              : "No notifications yet — production alerts will appear here."
          }
        />
      ) : (
        <div className="rounded-lg border divide-y max-h-[60vh] overflow-y-auto">
          {visible.map((n) => (
            <button
              key={n.id}
              onClick={() => open(n)}
              className={`w-full flex items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/50 ${
                n.isRead ? "" : "bg-amber-50/60"
              }`}
            >
              <span className="mt-2 shrink-0" aria-hidden>
                <span
                  className={`block size-2 rounded-full ${
                    n.isRead ? "bg-transparent" : "bg-amber-500"
                  }`}
                />
              </span>
              <span className="min-w-0 flex-1">
                <span className="flex flex-wrap items-center gap-2">
                  <span
                    className={`text-sm truncate ${n.isRead ? "font-medium" : "font-semibold"}`}
                  >
                    {n.title}
                  </span>
                  <Badge
                    variant="outline"
                    className="font-mono text-[10px] px-1.5 py-0 border-stone-300 bg-stone-50 text-stone-600"
                  >
                    {n.type}
                  </Badge>
                </span>
                {n.message && (
                  <span className="block text-sm text-muted-foreground mt-0.5 line-clamp-2">
                    {n.message}
                  </span>
                )}
              </span>
              <span className="text-xs text-muted-foreground whitespace-nowrap shrink-0 mt-1">
                {timeAgo(n.createdAt)}
              </span>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
