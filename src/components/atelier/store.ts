"use client";

// OSSZ ATELIER — global client state (session + view router + notification
// badge + UI language).

import { create } from "zustand";
import { api, setStoredToken } from "./client";
import { currentLang, persistLang, type Lang } from "./i18n/core";
import type { SessionUser } from "./types";

export type View =
  | "dashboard"
  | "tasks"
  | "task-create"
  | "task-detail"
  | "orders"
  | "customers"
  | "performance"
  | "incentives"
  | "reports"
  | "users"
  | "audit"
  | "stages"
  | "notifications";

type SessionStatus = "loading" | "authenticated" | "unauthenticated";

type AppState = {
  user: SessionUser | null;
  status: SessionStatus;
  view: View;
  viewParams: Record<string, string>;
  unreadCount: number;
  lang: Lang;
  bootstrap: () => Promise<void>;
  login: (email: string, password: string) => Promise<SessionUser>;
  logout: () => Promise<void>;
  navigate: (view: View, params?: Record<string, string>) => void;
  openTask: (taskId: string) => void;
  setUnreadCount: (n: number) => void;
  setLang: (lang: Lang) => void;
  applyStoredLang: () => void;
};

export const useAppStore = create<AppState>((set) => ({
  user: null,
  status: "loading",
  view: "dashboard",
  viewParams: {},
  unreadCount: 0,
  // Starts as English so SSR output and first client render always match;
  // applyStoredLang() restores the persisted choice right after mount.
  lang: "en",

  bootstrap: async () => {
    try {
      const res = await api.get<{ user: SessionUser | null; unreadCount: number }>(
        "/api/atelier/auth/me"
      );
      set({
        user: res.user,
        unreadCount: res.unreadCount ?? 0,
        status: res.user ? "authenticated" : "unauthenticated",
      });
    } catch {
      set({ user: null, status: "unauthenticated" });
    }
  },

  login: async (email, password) => {
    const res = await api.post<{ user: SessionUser; token: string }>(
      "/api/atelier/auth/login",
      { email, password }
    );
    // Persist the Bearer token so API calls keep working even when the
    // browser blocks the session cookie (embedded preview iframe).
    setStoredToken(res.token);
    set({ user: res.user, status: "authenticated", view: "dashboard", viewParams: {} });
    try {
      const me = await api.get<{ unreadCount: number }>("/api/atelier/notifications?limit=1");
      set({ unreadCount: me.unreadCount });
    } catch {
      // ignore
    }
    return res.user;
  },

  logout: async () => {
    try {
      await api.post("/api/atelier/auth/logout");
    } finally {
      setStoredToken(null);
      set({ user: null, status: "unauthenticated", view: "dashboard", viewParams: {}, unreadCount: 0 });
    }
  },

  navigate: (view, params = {}) => set({ view, viewParams: params }),

  openTask: (taskId) => set({ view: "task-detail", viewParams: { taskId } }),

  setUnreadCount: (n) => set({ unreadCount: n }),

  setLang: (lang) => {
    set({ lang });
    persistLang(lang);
  },

  applyStoredLang: () => {
    const stored = currentLang();
    set((state) => (state.lang === stored ? state : { lang: stored }));
  },
}));

export function can(user: SessionUser | null, permission: string): boolean {
  return !!user && user.permissions.includes(permission as never);
}

export function isManager(user: SessionUser | null): boolean {
  return user?.role === "MANAGEMENT";
}

export function isSupervisor(user: SessionUser | null): boolean {
  return user?.role === "MANAGEMENT" || user?.role === "ADMIN";
}
