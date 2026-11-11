"use client";

// OSSZ ATELIER — login screen. No public registration: accounts are created
// by Management/Admin inside the application.

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Scissors, Lock, Mail, Eye, EyeOff } from "lucide-react";
import { useAppStore } from "./store";
import { ApiClientError } from "./client";

const DEMO_ACCOUNTS = [
  { label: "Management", email: "management@atelier.com" },
  { label: "Admin", email: "admin@atelier.com" },
  { label: "Staff (Hamidou)", email: "hamidou@atelier.com" },
  { label: "Staff (Lawal)", email: "lawal@atelier.com" },
];

export function LoginView() {
  const login = useAppStore((s) => s.login);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login(email.trim(), password);
    } catch (err) {
      setError(err instanceof ApiClientError ? err.message : "Unable to sign in. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex flex-col lg:flex-row bg-background">
      {/* Brand panel */}
      <div className="relative hidden lg:flex lg:w-[45%] flex-col justify-between p-10 text-sidebar-foreground bg-[url('/atelier-hero.png')] bg-cover bg-center">
        <div className="absolute inset-0 bg-stone-950/70" aria-hidden="true" />
        <div className="relative">
          <div className="flex items-center gap-3">
            <div className="rounded-xl bg-amber-500/90 p-2.5">
              <Scissors className="size-6 text-stone-950" />
            </div>
            <div>
              <p className="font-display text-2xl font-bold leading-none">OSSZ ATELIER</p>
              <p className="text-xs tracking-[0.25em] text-stone-300 mt-1">PRODUCTION MANAGEMENT</p>
            </div>
          </div>
        </div>
        <div className="relative">
          <h1 className="font-display text-4xl font-bold leading-tight max-w-md">
            From customer order to finished garment, every piece accounted for.
          </h1>
          <p className="mt-4 text-stone-300 max-w-md text-sm leading-relaxed">
            Independent production management for OSSZ: manual order registration, staff task
            allocation, checklist tracking, quality control, performance and incentives.
          </p>
        </div>
        <p className="relative text-xs text-stone-400">
          atelier.osszcollection.com — independent system, own database, own accounts.
        </p>
      </div>

      {/* Form panel */}
      <div className="flex-1 flex items-center justify-center p-6">
        <div className="w-full max-w-md">
          <div className="lg:hidden flex items-center gap-3 mb-8">
            <div className="rounded-xl bg-primary p-2.5">
              <Scissors className="size-5 text-primary-foreground" />
            </div>
            <div>
              <p className="font-display text-xl font-bold leading-none">OSSZ ATELIER</p>
              <p className="text-[10px] tracking-[0.25em] text-muted-foreground mt-1">
                PRODUCTION MANAGEMENT
              </p>
            </div>
          </div>

          <h2 className="font-display text-2xl font-bold">Sign in</h2>
          <p className="text-sm text-muted-foreground mt-1 mb-6">
            Use the account provided to you by Management.
          </p>

          <form onSubmit={handleSubmit} className="space-y-4" noValidate>
            <div className="space-y-2">
              <Label htmlFor="email">Email address</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder="you@atelier.com"
                  className="pl-9"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  autoComplete="current-password"
                  placeholder="Your password"
                  className="pl-9 pr-10"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
            </div>

            {error && (
              <p className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                {error}
              </p>
            )}

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? "Signing in…" : "Sign in"}
            </Button>
          </form>

          <div className="mt-8 rounded-lg border bg-muted/40 p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
              Demo accounts (password: Atelier@2026)
            </p>
            <div className="flex flex-wrap gap-2">
              {DEMO_ACCOUNTS.map((acc) => (
                <button
                  key={acc.email}
                  type="button"
                  onClick={() => {
                    setEmail(acc.email);
                    setPassword("Atelier@2026");
                  }}
                  className="rounded-full border bg-background px-3 py-1.5 text-xs font-medium hover:bg-accent transition-colors"
                >
                  {acc.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
