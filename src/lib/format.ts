import { format, formatDistanceToNow, isValid } from "date-fns";

/** Format an amount in Central African CFA francs. */
export function formatFCFA(amount: number | null | undefined): string {
  if (amount == null) return "—";
  return `${new Intl.NumberFormat("en-US").format(amount)} FCFA`;
}

function toDate(value: string | Date | null | undefined): Date | null {
  if (value == null) return null;
  const d = typeof value === "string" ? new Date(value) : value;
  return isValid(d) ? d : null;
}

export function formatDate(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? format(d, "dd MMM yyyy") : "—";
}

export function formatDateTime(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? format(d, "dd MMM yyyy, HH:mm") : "—";
}

export function timeAgo(value: string | Date | null | undefined): string {
  const d = toDate(value);
  return d ? formatDistanceToNow(d, { addSuffix: true }) : "—";
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
