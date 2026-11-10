"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Scissors, AlertCircle, Loader2 } from "lucide-react";
import { useAppStore } from "./store";
import { ApiClientError } from "./api-client";

const DEMO_ACCOUNTS = [
  { label: "Administrator", email: "admin@ossz.com", password: "ossz-admin1" },
  { label: "Production Manager", email: "manager@ossz.com", password: "ossz-manager1" },
  { label: "Inspector", email: "inspector@ossz.com", password: "ossz-inspect1" },
  { label: "Production Staff (Hamidou)", email: "hamidou@ossz.com", password: "ossz-tailor1" },
  { label: "Production Staff (Lawal)", email: "lawal@ossz.com", password: "ossz-tailor1" },
];

export function LoginView() {
  const login = useAppStore((s) => s.login);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login(email, password);
    } catch (err) {
      setError(err instanceof ApiClientError ? err.message : "Unable to sign in. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  async function quickFill(demo: (typeof DEMO_ACCOUNTS)[number]) {
    setEmail(demo.email);
    setPassword(demo.password);
    setError(null);
    setLoading(true);
    try {
      await login(demo.email, demo.password);
    } catch (err) {
      setError(err instanceof ApiClientError ? err.message : "Unable to sign in.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <main className="flex-1 grid lg:grid-cols-2">
        {/* Brand panel */}
        <div className="relative hidden lg:block overflow-hidden bg-stone-950">
          <img
            src="/atelier-hero.png"
            alt="OSSZ Atelier — bespoke tailoring workbench with premium fabrics"
            className="absolute inset-0 h-full w-full object-cover opacity-80"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-stone-950/90 via-stone-950/30 to-transparent" />
          <div className="relative h-full flex flex-col justify-end p-12 text-stone-100">
            <div className="flex items-center gap-3 mb-6">
              <div className="size-12 rounded-xl bg-primary/90 flex items-center justify-center">
                <Scissors className="size-6 text-primary-foreground" />
              </div>
              <span className="font-display text-3xl font-semibold tracking-wide">OSSZ</span>
            </div>
            <h1 className="font-display text-4xl font-medium leading-tight max-w-md">
              Maison de couture — backoffice
            </h1>
            <p className="mt-3 text-stone-300 max-w-md">
              Order management, garment-by-garment production tracking, task allocation and
              completion control for the atelier.
            </p>
          </div>
        </div>

        {/* Login form */}
        <div className="flex items-center justify-center p-6 sm:p-12">
          <div className="w-full max-w-md">
            <div className="lg:hidden flex items-center gap-3 mb-8">
              <div className="size-11 rounded-xl bg-primary flex items-center justify-center">
                <Scissors className="size-5 text-primary-foreground" />
              </div>
              <span className="font-display text-2xl font-semibold">OSSZ Atelier</span>
            </div>

            <h2 className="font-display text-2xl font-semibold">Sign in to the backoffice</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Use your OSSZ staff account to continue.
            </p>

            <form onSubmit={handleSubmit} className="mt-8 space-y-4">
              {error && (
                <Alert variant="destructive">
                  <AlertCircle className="size-4" />
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder="you@ossz.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  autoComplete="current-password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>
              <Button type="submit" className="w-full" disabled={loading}>
                {loading ? <Loader2 className="size-4 animate-spin" /> : null}
                Sign in
              </Button>
            </form>

            <Card className="mt-8 bg-muted/40 border-dashed">
              <CardContent className="p-4">
                <p className="text-xs font-medium text-muted-foreground mb-2">
                  Demo accounts — click to sign in instantly:
                </p>
                <div className="flex flex-wrap gap-1.5">
                  {DEMO_ACCOUNTS.map((d) => (
                    <Button
                      key={d.email}
                      type="button"
                      variant="secondary"
                      size="sm"
                      className="h-7 text-xs"
                      disabled={loading}
                      onClick={() => quickFill(d)}
                    >
                      {d.label}
                    </Button>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    </div>
  );
}
