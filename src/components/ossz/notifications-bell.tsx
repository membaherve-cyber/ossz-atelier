"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { api } from "./api-client";
import { timeAgo } from "@/lib/format";
import type { NotificationItem } from "./types";

export function NotificationsBell() {
  const queryClient = useQueryClient();

  const { data } = useQuery({
    queryKey: ["notifications"],
    queryFn: () => api.get<{ notifications: NotificationItem[]; unreadCount: number }>("/api/notifications"),
    refetchInterval: 30_000,
  });

  const markRead = useMutation({
    mutationFn: (payload: { ids?: string[]; all?: boolean }) =>
      api.post("/api/notifications", payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });

  const unread = data?.unreadCount ?? 0;
  const notifications = data?.notifications ?? [];

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="relative" aria-label={`Notifications (${unread} unread)`}>
          <Bell className="size-5" />
          {unread > 0 && (
            <span className="absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 rounded-full bg-red-600 text-[10px] font-bold text-white flex items-center justify-center">
              {unread > 99 ? "99+" : unread}
            </span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-80 sm:w-96 p-0">
        <div className="flex items-center justify-between px-4 py-3 border-b">
          <p className="font-semibold text-sm">Notifications</p>
          {unread > 0 && (
            <Button
              variant="ghost"
              size="sm"
              className="h-7 text-xs"
              onClick={() => markRead.mutate({ all: true })}
              disabled={markRead.isPending}
            >
              Mark all read
            </Button>
          )}
        </div>
        <div className="max-h-96 overflow-y-auto">
          {notifications.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-10 px-4">
              No notifications yet.
            </p>
          ) : (
            notifications.map((n) => (
              <button
                key={n.id}
                className={cn(
                  "w-full text-left px-4 py-3 border-b last:border-0 hover:bg-muted/60 transition-colors flex gap-3",
                  !n.isRead && "bg-primary/[0.06]"
                )}
                onClick={() => {
                  if (!n.isRead) markRead.mutate({ ids: [n.id] });
                }}
              >
                <span
                  className={cn(
                    "mt-1.5 size-2 shrink-0 rounded-full",
                    n.isRead ? "bg-transparent border border-border" : "bg-primary"
                  )}
                />
                <span className="min-w-0">
                  <span className="block text-sm font-medium leading-snug">{n.title}</span>
                  {n.body && (
                    <span className="block text-xs text-muted-foreground mt-0.5 line-clamp-2">{n.body}</span>
                  )}
                  <span className="block text-[11px] text-muted-foreground/70 mt-1">
                    {(n.order?.reference ?? "") + (n.task?.pieceName ? ` · ${n.task.pieceName}` : "")} ·{" "}
                    {timeAgo(n.createdAt)}
                  </span>
                </span>
              </button>
            ))
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}
