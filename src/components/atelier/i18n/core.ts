// OSSZ ATELIER — bilingual i18n core (English / French).
// Plain types, dictionary lookup and domain label helpers. No React code,
// so it can be imported from any client module (including non-hook utilities).

export type Lang = "en" | "fr";

export type DictEntries = Record<string, { en: string; fr: string }>;

const LANG_STORAGE_KEY = "atelier_lang";

export function isLang(value: string | null): value is Lang {
  return value === "en" || value === "fr";
}

/** Current language, persisted in localStorage. Defaults to English. */
export function currentLang(): Lang {
  if (typeof window === "undefined") return "en";
  try {
    return window.localStorage.getItem(LANG_STORAGE_KEY) === "fr" ? "fr" : "en";
  } catch {
    return "en";
  }
}

/** Persist the chosen language and mirror it on <html lang>. */
export function persistLang(lang: Lang): void {
  try {
    window.localStorage.setItem(LANG_STORAGE_KEY, lang);
    document.documentElement.lang = lang;
  } catch {
    // storage unavailable — keep in-memory only
  }
}

// ---------------------------------------------------------------------------
// Dictionary lookup
// ---------------------------------------------------------------------------

import { merged as mergedDict } from "./dict";

function lookup(key: string, lang: Lang): string | null {
  const entry = mergedDict[key];
  if (!entry) return null;
  const text = lang === "fr" ? entry.fr || entry.en : entry.en;
  return text ?? null;
}

function interpolate(text: string, params?: Record<string, string | number>): string {
  if (!params) return text;
  let out = text;
  for (const [name, value] of Object.entries(params)) {
    out = out.split(`{${name}}`).join(String(value));
  }
  return out;
}

/** Translate a dictionary key in the given language (defaults to stored lang). */
export function translate(
  key: string,
  lang: Lang = currentLang(),
  params?: Record<string, string | number>
): string {
  const text = lookup(key, lang);
  if (text == null) {
    if (process.env.NODE_ENV !== "production") {
      console.warn(`[i18n] missing key "${key}" (${lang})`);
    }
    return interpolate(key, params);
  }
  return interpolate(text, params);
}

// ---------------------------------------------------------------------------
// Domain label helpers (statuses, priorities, roles, …)
// ---------------------------------------------------------------------------

export function statusLabel(status: string, lang: Lang = currentLang()): string {
  return translate(`status.${status}`, lang);
}

export function priorityLabel(priority: string, lang: Lang = currentLang()): string {
  return translate(`priority.${priority}`, lang);
}

export function categoryLabel(category: string, lang: Lang = currentLang()): string {
  return translate(`category.${category}`, lang);
}

export function stageStateLabel(state: string, lang: Lang = currentLang()): string {
  return translate(`stageState.${state}`, lang);
}

export function roleLabel(role: string, lang: Lang = currentLang()): string {
  return translate(`role.${role}`, lang);
}

export function inspectionLabel(decision: string, lang: Lang = currentLang()): string {
  return translate(`inspection.${decision}`, lang);
}

export function incentivePeriodLabel(periodType: string, lang: Lang = currentLang()): string {
  return translate(`incentivePeriod.${periodType}`, lang);
}

export function incentiveStatusLabel(status: string, lang: Lang = currentLang()): string {
  return translate(`incStatus.${status}`, lang);
}

export function paymentStatusLabel(status: string, lang: Lang = currentLang()): string {
  return translate(`paymentStatus.${status}`, lang);
}

/**
 * Display title for a notification. Falls back to the stored (English) title
 * when the notification type has no translated entry — e.g. for legacy or
 * free-form rows.
 */
export function notifTitle(type: string, fallback: string, lang: Lang = currentLang()): string {
  const text = lookup(`notifTitle.${type}`, lang);
  return text ?? fallback;
}
