"use client";

import { create } from "zustand";
import { api } from "./api-client";
import type { SessionUser } from "./types";

type SessionStatus = "loading" | "authenticated" | "unauthenticated";

type AppState = {
  user: SessionUser | null;
  status: SessionStatus;
  bootstrap: () => Promise<void>;
  login: (email: string, password: string) => Promise<SessionUser>;
  logout: () => Promise<void>;
};

export const useAppStore = create<AppState>((set) => ({
  user: null,
  status: "loading",
  bootstrap: async () => {
    try {
      const res = await api.get<{ user: SessionUser | null }>("/api/auth/me");
      set({ user: res.user, status: res.user ? "authenticated" : "unauthenticated" });
    } catch {
      set({ user: null, status: "unauthenticated" });
    }
  },
  login: async (email, password) => {
    const res = await api.post<{ user: SessionUser }>("/api/auth/login", { email, password });
    set({ user: res.user, status: "authenticated" });
    return res.user;
  },
  logout: async () => {
    try {
      await api.post("/api/auth/logout");
    } finally {
      set({ user: null, status: "unauthenticated" });
    }
  },
}));

export function can(user: SessionUser | null, permission: string): boolean {
  return !!user && user.permissions.includes(permission);
}
