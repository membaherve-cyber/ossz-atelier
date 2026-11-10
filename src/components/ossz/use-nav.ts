"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useCallback } from "react";

export type ViewId =
  | "dashboard"
  | "orders"
  | "order"
  | "production"
  | "mytasks"
  | "products"
  | "staff"
  | "reports";

/**
 * Navigation within the single `/` route: views are encoded in search params
 * (e.g. /?view=orders&order=abc) so every screen stays deep-linkable.
 */
export function useNav() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const view = (searchParams.get("view") as ViewId | null) ?? null;
  const get = useCallback((key: string) => searchParams.get(key), [searchParams]);

  const navigate = useCallback(
    (patch: Record<string, string | null | undefined>, keepOthers = true) => {
      const params = new URLSearchParams(searchParams.toString());
      for (const [k, v] of Object.entries(patch)) {
        if (v === null || v === undefined || v === "") params.delete(k);
        else params.set(k, v);
      }
      if (!keepOthers) {
        // Start from a clean param set, then apply the patch.
        const fresh = new URLSearchParams();
        for (const [k, v] of Object.entries(patch)) {
          if (v) fresh.set(k, v);
        }
        router.push(`/?${fresh.toString()}`.replace(/\?$/, ""), { scroll: false });
        return;
      }
      router.push(`/?${params.toString()}`.replace(/\?$/, ""), { scroll: false });
    },
    [router, searchParams]
  );

  return { view, get, navigate, searchParams };
}
