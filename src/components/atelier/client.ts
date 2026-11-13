"use client";

// OSSZ ATELIER — client-side API helper (same-origin fetch with cookies +
// Bearer token fallback for embedded contexts where cookies are blocked).

const TOKEN_KEY = "atelier_token";

export function setStoredToken(token: string | null): void {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
  } catch {
    // localStorage unavailable (private mode, etc.) — cookies still apply.
  }
}

export function getStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

export class ApiClientError extends Error {
  constructor(
    public status: number,
    message: string,
    public details?: { issues?: Array<{ path: string; message: string }> } | null
  ) {
    super(message);
  }
}

async function request<T>(method: string, url: string, body?: unknown): Promise<T> {
  const token = getStoredToken();
  const headers: Record<string, string> = {};
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (token) headers["Authorization"] = `Bearer ${token}`;

  const res = await fetch(url, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    credentials: "same-origin",
  });
  let data: Record<string, unknown> = {};
  try {
    data = await res.json();
  } catch {
    // non-JSON response
  }
  if (!res.ok) {
    // Stale/expired token: drop it so subsequent requests rely on the cookie
    // (or force a clean re-login) instead of repeating the same failure.
    if (res.status === 401 && token) setStoredToken(null);
    throw new ApiClientError(
      res.status,
      (data.error as string) || `Request failed (${res.status})`,
      data.details as ApiClientError["details"]
    );
  }
  return data as T;
}

export const api = {
  get: <T>(url: string) => request<T>("GET", url),
  post: <T>(url: string, body?: unknown) => request<T>("POST", url, body ?? {}),
  patch: <T>(url: string, body?: unknown) => request<T>("PATCH", url, body ?? {}),
  put: <T>(url: string, body?: unknown) => request<T>("PUT", url, body ?? {}),
  del: <T>(url: string, body?: unknown) => request<T>("DELETE", url, body ?? {}),
};

export function qs(
  params: Record<string, string | number | boolean | null | undefined>
): string {
  const sp = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v === null || v === undefined || v === "") continue;
    sp.set(k, String(v));
  }
  const s = sp.toString();
  return s ? `?${s}` : "";
}
