"use client";

// OSSZ ATELIER — compact EN/FR language switch button used in the login
// screen and the application header.

import { Languages } from "lucide-react";
import { cn } from "@/lib/utils";
import { useI18n } from "./use-i18n";
import { translate } from "./core";

const CHOICES = [
  { value: "en" as const, short: "EN" },
  { value: "fr" as const, short: "FR" },
];

export function LanguageToggle({ className }: { className?: string }) {
  const { lang, setLang, t } = useI18n();

  return (
    <div
      role="group"
      aria-label={t("shell.languageAria")}
      title={lang === "fr" ? translate("shell.languageTitle", "en") : translate("shell.languageTitle", "fr")}
      className={cn(
        "flex items-center gap-0.5 rounded-full border bg-background p-0.5 select-none",
        className
      )}
    >
      <Languages className="size-3.5 ml-1 text-muted-foreground" aria-hidden="true" />
      {CHOICES.map((choice) => {
        const active = lang === choice.value;
        return (
          <button
            key={choice.value}
            type="button"
            onClick={() => setLang(choice.value)}
            aria-pressed={active}
            className={cn(
              "rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide transition-colors",
              active
                ? "bg-primary text-primary-foreground"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            {choice.short}
          </button>
        );
      })}
    </div>
  );
}
