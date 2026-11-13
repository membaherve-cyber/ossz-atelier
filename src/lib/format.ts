import { format, formatDistanceToNow, isValid } from "date-fns";
import { fr } from "date-fns/locale";
import { currentLang } from "@/components/atelier/i18n/core";

/** Locale matching the active UI language (English by default). */
function dateLocale() {
  return currentLang() === "fr" ? fr : undefined;
}

/** Format an amount in Central African CFA francs. */
export function formatFCFA(amount: number | null | undefined): string {
  if (amount == null) return "—";
  const locale = currentLang() === "fr" ? "fr-FR" : "en-US";
  return `${new Intl.NumberFormat(locale).format(amount)} FCFA`;
}

function toDate(value: string | Date | null | undefined): Date | null {
  if (value == null) return null;
  const d = typeof value === "string" ? new Date(value) : value;
  return isValid(d) ? d : null;
}

export function formatDate(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? format(d, "dd MMM yyyy", { locale: dateLocale() }) : "—";
}

export function formatDateTime(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? format(d, "dd MMM yyyy, HH:mm", { locale: dateLocale() }) : "—";
}

export function timeAgo(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? formatDistanceToNow(d, { addSuffix: true, locale: dateLocale() }) : "—";
}

export function initials(name: string | null | undefined): string {
  if (!name) return "?";
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]!.toUpperCase())
    .join("");
}

/** Validate that a string is a parseable date, returning a Date or null. */
export function safeDate(value: string): Date | null {
  const d = new Date(value);
  return isValid(d) ? d : null;
}
