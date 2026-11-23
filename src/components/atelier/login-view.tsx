"use client";

// OSSZ ATELIER — login screen. No public registration: accounts are created
// by Management/Admin inside the application. Bilingual (EN/FR) with a
// language switch at the top of the form panel.

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Lock, Mail, Eye, EyeOff } from "lucide-react";
import { useAppStore } from "./store";
import { ApiClientError } from "./client";
import { useI18n } from "./i18n/use-i18n";
import { LanguageToggle } from "./i18n/language-toggle";

export function LoginView() {
  const login = useAppStore((s) => s.login);
  const { t } = useI18n();
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
      setError(err instanceof ApiClientError ? err.message : t("login.error"));
    } finally {
      setLoading(false);
    }
  }

  const demoAccounts = [
    { label: t("login.demoManagement"), email: "management@osszcollection.com" },
    { label: t("login.demoAdmin"), email: "admin@osszcollection.com" },
    { label: t("login.demoStaff"), email: "info@osszcollection.com" },
  ];

  return (
    <div className="relative min-h-screen flex flex-col lg:flex-row bg-background">
      {/* Brand panel */}
      <div className="relative hidden lg:flex lg:w-[45%] flex-col justify-between p-10 text-sidebar-foreground bg-[url('/atelier-hero.png')] bg-cover bg-center">
        <div className="absolute inset-0 bg-black/75" aria-hidden="true" />
        <div className="relative">
          <img
            src="/ossz-logo.png"
            alt={t("login.logoAlt")}
            className="h-14 w-auto rounded-lg ring-1 ring-white/20"
          />
        </div>
        <div className="relative">
          <h1 className="font-display text-4xl font-bold leading-tight max-w-md">
            {t("login.heroTitle")}
          </h1>
          <p className="mt-4 text-stone-300 max-w-md text-sm leading-relaxed">
            {t("login.heroBody")}
          </p>
        </div>
        <p className="relative text-xs text-stone-400">{t("login.heroFooter")}</p>
      </div>

      {/* Language switch (always visible, top-right) */}
      <div className="absolute right-4 top-4 z-10">
        <LanguageToggle />
      </div>

      {/* Form panel */}
      <div className="flex-1 flex items-center justify-center p-6">
        <div className="w-full max-w-md">
          <div className="lg:hidden flex items-center gap-3 mb-8">
            <img
              src="/ossz-logo.png"
              alt={t("login.logoAlt")}
              className="h-11 w-auto rounded-lg ring-1 ring-black/10"
            />
            <div>
              <p className="font-display text-xl font-bold leading-none">OSSZ ATELIER</p>
              <p className="text-[10px] tracking-[0.25em] text-muted-foreground mt-1">
                {t("login.brandTag")}
              </p>
            </div>
          </div>

          <h2 className="font-display text-2xl font-bold">{t("login.title")}</h2>
          <p className="text-sm text-muted-foreground mt-1 mb-6">{t("login.subtitle")}</p>

          <form onSubmit={handleSubmit} className="space-y-4" noValidate>
            <div className="space-y-2">
              <Label htmlFor="email">{t("login.email")}</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder={t("login.emailPlaceholder")}
                  className="pl-9"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">{t("login.password")}</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  autoComplete="current-password"
                  placeholder={t("login.passwordPlaceholder")}
                  className="pl-9 pr-10"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  aria-label={showPassword ? t("login.hidePassword") : t("login.showPassword")}
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
              {loading ? t("login.submitting") : t("login.submit")}
            </Button>
          </form>

          <div className="mt-8 rounded-lg border bg-muted/40 p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
              {t("login.demoTitle")}
            </p>
            <div className="flex flex-wrap gap-2">
              {demoAccounts.map((acc) => (
                <button
                  key={acc.email}
                  type="button"
                  onClick={() => {
                    setEmail(acc.email);
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
