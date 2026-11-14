"use client";

// OSSZ ATELIER — React binding for the bilingual i18n core. Components use
// useI18n() to read the active language and translate keys; the language
// switcher updates the store which re-renders every subscribed view.

import { useCallback } from "react";
import { useAppStore } from "../store";
import { translate, type Lang } from "./core";

export type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

export function useI18n(): {
  t: TranslateFn;
  lang: Lang;
  setLang: (lang: Lang) => void;
} {
  const lang = useAppStore((s) => s.lang);
  const setLang = useAppStore((s) => s.setLang);
  const t = useCallback<TranslateFn>(
    (key, params) => translate(key, lang, params),
    [lang]
  );
  return { t, lang, setLang };
}
