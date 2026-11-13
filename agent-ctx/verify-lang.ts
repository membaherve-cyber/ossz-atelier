#!/usr/bin/env bun
// OSSZ ATELIER — independent verifier for laravel-atelier/lang/{en,fr}/*.php.
// 1. Re-imports the 16 TS dictionaries (source of truth) and builds the expected
//    key->value map per target PHP file (label prefixes -> labels.php).
// 2. Parses every generated PHP file with a small recursive-descent parser
//    (no regex-only heuristics) and flattens it back to dotted keys.
// 3. Diffs: missing keys, extra keys, value mismatches — per file, per locale.
// 4. Checks EN/FR structural parity and the on-disk file set (17 per locale).
//
// Run: bun run verify-lang.ts

import { readFileSync, readdirSync } from "node:fs";
import { join } from "node:path";

import common from "../src/components/atelier/i18n/dict/common";
import shell from "../src/components/atelier/i18n/dict/shell";
import login from "../src/components/atelier/i18n/dict/login";
import dashboard from "../src/components/atelier/i18n/dict/dashboard";
import notifications from "../src/components/atelier/i18n/dict/notifications";
import tasks from "../src/components/atelier/i18n/dict/tasks";
import taskCreate from "../src/components/atelier/i18n/dict/task-create";
import taskDetail from "../src/components/atelier/i18n/dict/task-detail";
import orders from "../src/components/atelier/i18n/dict/orders";
import customers from "../src/components/atelier/i18n/dict/customers";
import performance from "../src/components/atelier/i18n/dict/performance";
import incentives from "../src/components/atelier/i18n/dict/incentives";
import reports from "../src/components/atelier/i18n/dict/reports";
import users from "../src/components/atelier/i18n/dict/users";
import stages from "../src/components/atelier/i18n/dict/stages";
import audit from "../src/components/atelier/i18n/dict/audit";

type Val = { en: string; fr: string };
const DICTS: Array<[string, Record<string, Val>]> = [
  ["common", common], ["shell", shell], ["login", login], ["dashboard", dashboard],
  ["notifications", notifications], ["tasks", tasks], ["task-create", taskCreate],
  ["task-detail", taskDetail], ["orders", orders], ["customers", customers],
  ["performance", performance], ["incentives", incentives], ["reports", reports],
  ["users", users], ["stages", stages], ["audit", audit],
];

const LABEL_PREFIXES = new Set([
  "status", "priority", "category", "stageState", "role", "inspection",
  "incentivePeriod", "incStatus", "paymentStatus", "notifTitle",
]);

const LANG_ROOT = "/home/z/my-project/laravel-atelier/lang";
const EXPECTED_FILES = [
  "common", "shell", "login", "dash", "notif", "tasks", "tc", "td",
  "orders", "cust", "perf", "inc", "rep", "users", "stages", "audit", "labels",
];

// ---- expected: file -> locale -> dottedKey -> value -------------------------
const expected = new Map<string, { en: Map<string, string>; fr: Map<string, string> }>();
for (const [name, entries] of DICTS) {
  for (const [key, val] of Object.entries(entries)) {
    const segs = key.split(".");
    const target = LABEL_PREFIXES.has(segs[0]!) ? "labels" : segs[0]!;
    const path = target === "labels" ? segs : segs.slice(1);
    let perFile = expected.get(target);
    if (!perFile) {
      perFile = { en: new Map(), fr: new Map() };
      expected.set(target, perFile);
    }
    perFile.en.set(path.join("."), val.en);
    perFile.fr.set(path.join("."), val.fr);
  }
}

// ---- PHP parser --------------------------------------------------------------
function parsePhpLang(src: string): Record<string, unknown> {
  // strip the opening tag and // comments
  const cleaned = src
    .replace(/^<\?php/, "")
    .split("\n")
    .filter((l) => !l.trimStart().startsWith("//"))
    .join("\n");
  let i = 0;
  const s = cleaned;
  const err = (m: string) => new Error(`${m} at offset ${i}: ...${JSON.stringify(s.slice(Math.max(0, i - 20), i + 30))}`);
  const ws = () => { while (i < s.length && /\s/.test(s[i]!)) i++; };
  ws();
  if (s.startsWith("return", i)) i += "return".length;
  const expect = (ch: string) => { ws(); if (s[i] !== ch) throw err(`expected "${ch}"`); i++; };
  const parseString = (): string => {
    ws();
    if (s[i] !== "'") throw err("expected opening single quote");
    i++;
    let out = "";
    while (i < s.length) {
      const c = s[i]!;
      if (c === "\\") {
        const n = s[i + 1];
        if (n === "'" || n === "\\") { out += n!; i += 2; continue; }
        out += c; i++; continue;
      }
      if (c === "'") { i++; return out; }
      out += c; i++;
    }
    throw err("unterminated string");
  };
  const parseValue = (): unknown => { ws(); return s[i] === "[" ? parseArray() : parseString(); };
  const parseArray = (): Record<string, unknown> => {
    expect("[");
    const out: Record<string, unknown> = {};
    ws();
    while (s[i] !== "]") {
      const key = parseString();
      ws();
      expect("=");
      expect(">"); // "=>" split so the parser error points at the right char
      out[key] = parseValue();
      ws();
      if (s[i] === ",") { i++; ws(); }
      else if (s[i] !== "]") throw err("expected , or ]");
    }
    expect("]");
    return out;
  };
  const tree = parseArray();
  ws();
  if (s[i] === ";") { i++; ws(); }
  if (i !== s.length) throw err("trailing content after array");
  return tree;
}

function flatten(node: Record<string, unknown>, prefix = ""): Map<string, string> {
  const out = new Map<string, string>();
  for (const [k, v] of Object.entries(node)) {
    const dotted = prefix ? `${prefix}.${k}` : k;
    if (typeof v === "string") out.set(dotted, v);
    else for (const [dk, dv] of flatten(v as Record<string, unknown>, dotted)) out.set(dk, dv);
  }
  return out;
}

// ---- run checks ---------------------------------------------------------------
let failures = 0;
const fail = (msg: string) => { console.error(`  ✗ ${msg}`); failures++; };

console.log("1) On-disk file sets");
for (const locale of ["en", "fr"] as const) {
  const onDisk = readdirSync(join(LANG_ROOT, locale)).filter((f) => f.endsWith(".php")).map((f) => f.replace(/\.php$/, "")).sort();
  const want = [...EXPECTED_FILES].sort();
  const same = JSON.stringify(onDisk) === JSON.stringify(want);
  console.log(`  lang/${locale}: ${onDisk.length} files — ${same ? "exactly the expected set" : "MISMATCH"}`);
  if (!same) {
    fail(`lang/${locale} file set differs: extra=[${onDisk.filter((f) => !want.includes(f))}] missing=[${want.filter((f) => !onDisk.includes(f))}]`);
  }
}

console.log("\n2) Per-file diff (TS dictionaries = source of truth)");
let totEn = 0, totFr = 0;
for (const file of EXPECTED_FILES) {
  const exp = expected.get(file);
  if (!exp) { fail(`no expected entries for ${file}.php`); continue; }
  const rows: string[] = [];
  for (const locale of ["en", "fr"] as const) {
    let phpFlat: Map<string, string>;
    try {
      phpFlat = flatten(parsePhpLang(readFileSync(join(LANG_ROOT, locale, `${file}.php`), "utf8")));
    } catch (e) {
      fail(`lang/${locale}/${file}.php failed to parse: ${(e as Error).message}`);
      continue;
    }
    const expMap = locale === "en" ? exp.en : exp.fr;
    const missing = [...expMap.keys()].filter((k) => !phpFlat.has(k));
    const extra = [...phpFlat.keys()].filter((k) => !expMap.has(k));
    const wrong = [...expMap.keys()].filter((k) => phpFlat.has(k) && phpFlat.get(k) !== expMap.get(k));
    if (missing.length || extra.length || wrong.length) {
      rows.push(`    ${locale}: ${missing.length} missing, ${extra.length} extra, ${wrong.length} value mismatches`);
      for (const k of missing) rows.push(`      missing: ${k}`);
      for (const k of extra) rows.push(`      extra:   ${k}`);
      for (const k of wrong) rows.push(`      value:   ${k}\n        TS : ${JSON.stringify(expMap.get(k))}\n        PHP: ${JSON.stringify(phpFlat.get(k))}`);
      failures++;
    }
    if (locale === "en") totEn += phpFlat.size; else totFr += phpFlat.size;
  }
  const status = rows.length === 0 ? "OK" : "FAIL";
  console.log(`  ${file}.php  en=${exp.en.size} fr=${exp.fr.size}  ${status}`);
  for (const r of rows) console.log(r);
}

console.log("\n3) EN/FR structural parity (from the PHP files themselves)");
for (const file of EXPECTED_FILES) {
  let ok = true;
  let enKeys: Set<string> | null = null;
  for (const locale of ["en", "fr"] as const) {
    try {
      const keys = new Set(flatten(parsePhpLang(readFileSync(join(LANG_ROOT, locale, `${file}.php`), "utf8"))).keys());
      if (enKeys === null) enKeys = keys;
      else {
        const onlyEn = [...enKeys].filter((k) => !keys.has(k));
        const onlyFr = [...keys].filter((k) => !enKeys!.has(k));
        if (onlyEn.length || onlyFr.length) {
          ok = false;
          fail(`${file}.php EN/FR parity: only-EN=[${onlyEn}] only-FR=[${onlyFr}]`);
        }
      }
    } catch (e) {
      ok = false;
      fail(`lang/${locale}/${file}.php parse error: ${(e as Error).message}`);
    }
  }
  console.log(`  ${file}.php  ${ok ? "parity OK" : "PARITY FAIL"}`);
}

console.log(`\nTotals: en=${totEn} fr=${totFr} entries across ${EXPECTED_FILES.length} files x 2 locales`);
console.log(failures === 0 ? "\nRESULT: ZERO missing keys, ZERO extra keys, ZERO value mismatches, EN/FR parity confirmed." : `\nRESULT: ${failures} FAILURE GROUP(S) — see above.`);
process.exit(failures === 0 ? 0 : 1);
