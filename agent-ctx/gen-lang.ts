#!/usr/bin/env bun
// OSSZ ATELIER — generator that ports the 16 TS i18n dictionaries
// (src/components/atelier/i18n/dict/*.ts) into Laravel PHP lang files
// under laravel-atelier/lang/{en,fr}/.
//
// Mapping rule (per laravel-spec.md §7):
//   dict key "seg1.seg2.seg3"  ->  lang/{locale}/seg1.php  ['seg2' => ['seg3' => 'text']]
//   Domain-label prefixes (status., priority., category., stageState., role.,
//   inspection., incentivePeriod., incStatus., paymentStatus., notifTitle.)
//   -> lang/{locale}/labels.php as nested arrays instead.
//
// Run: bun run gen-lang.ts

import { mkdirSync, writeFileSync } from "node:fs";
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
  ["common", common],
  ["shell", shell],
  ["login", login],
  ["dashboard", dashboard],
  ["notifications", notifications],
  ["tasks", tasks],
  ["task-create", taskCreate],
  ["task-detail", taskDetail],
  ["orders", orders],
  ["customers", customers],
  ["performance", performance],
  ["incentives", incentives],
  ["reports", reports],
  ["users", users],
  ["stages", stages],
  ["audit", audit],
];

const LABEL_PREFIXES = new Set([
  "status",
  "priority",
  "category",
  "stageState",
  "role",
  "inspection",
  "incentivePeriod",
  "incStatus",
  "paymentStatus",
  "notifTitle",
]);

const OUT_ROOT = "/home/z/my-project/laravel-atelier/lang";

// target file -> { en: tree, fr: tree } (plain nested objects, insertion order kept)
type Tree = Record<string, unknown>;
const trees = new Map<string, { en: Tree; fr: Tree }>();

// full dotted key -> source dict file, to detect cross-file duplicates
const keySource = new Map<string, string>();
const problems: string[] = [];
const labelSources = new Set<string>();
const fileTargets = new Map<string, Set<string>>();

function treeFor(file: string): { en: Tree; fr: Tree } {
  let t = trees.get(file);
  if (!t) {
    t = { en: {}, fr: {} };
    trees.set(file, t);
  }
  return t;
}

function setPath(tree: Tree, path: string[], value: string, fullKey: string): void {
  let cur: Record<string, unknown> = tree;
  for (const seg of path.slice(0, -1)) {
    const next = cur[seg];
    if (next === undefined) {
      cur[seg] = {};
    } else if (typeof next === "string") {
      problems.push(`structural conflict: "${fullKey}" descends through string leaf "${seg}"`);
      return;
    }
    cur = cur[seg] as Record<string, unknown>;
  }
  const last = path[path.length - 1]!;
  const existing = cur[last];
  if (existing !== undefined) {
    problems.push(`duplicate leaf key: "${fullKey}" (existing: ${JSON.stringify(existing)})`);
    return;
  }
  cur[last] = value;
}

for (const [name, entries] of DICTS) {
  fileTargets.set(name, new Set());
  for (const [key, val] of Object.entries(entries)) {
    const segs = key.split(".");
    if (segs.length < 2) {
      problems.push(`[${name}] key "${key}" has fewer than 2 segments`);
      continue;
    }
    const isLabel = LABEL_PREFIXES.has(segs[0]!);
    const target = isLabel ? "labels" : segs[0]!;
    fileTargets.get(name)!.add(target);

    const prev = keySource.get(key);
    if (prev !== undefined && prev !== name) {
      problems.push(`cross-file duplicate key "${key}" in ${prev} and ${name}`);
    }
    keySource.set(key, name);
    if (isLabel) labelSources.add(name);

    if (typeof val.en !== "string" || typeof val.fr !== "string") {
      problems.push(`[${name}] "${key}" missing en/fr string`);
      continue;
    }
    if (val.en.length === 0) problems.push(`[${name}] "${key}" has EMPTY en value`);
    if (val.fr.length === 0) problems.push(`[${name}] "${key}" has EMPTY fr value`);
    if (/[\r\n]/.test(val.en) || /[\r\n]/.test(val.fr)) {
      problems.push(`[${name}] "${key}" contains a newline (unsupported)`);
    }

    const t = treeFor(target);
    // For labels.php keep the prefix segment (labels => ['status' => [...]]).
    const path = isLabel ? segs : segs.slice(1);
    setPath(t.en, path, val.en, key);
    setPath(t.fr, path, val.fr, key);
  }
}

// ---------------------------------------------------------------- PHP rendering

function phpEscape(s: string): string {
  return s.replace(/\\/g, "\\\\").replace(/'/g, "\\'");
}

function renderTree(node: Tree, depth: number): string {
  const pad = "    ".repeat(depth);
  const lines: string[] = [];
  for (const [k, v] of Object.entries(node)) {
    if (typeof v === "string") {
      lines.push(`${pad}'${phpEscape(k)}' => '${phpEscape(v)}',`);
    } else {
      lines.push(`${pad}'${phpEscape(k)}' => [`);
      lines.push(renderTree(v as Tree, depth + 1));
      lines.push(`${pad}],`);
    }
  }
  return lines.join("\n");
}

function countLeaves(node: Tree): number {
  let n = 0;
  for (const v of Object.values(node)) {
    n += typeof v === "string" ? 1 : countLeaves(v as Tree);
  }
  return n;
}

const EXPECTED_FILES = [
  "common", "shell", "login", "dash", "notif", "tasks", "tc", "td",
  "orders", "cust", "perf", "inc", "rep", "users", "stages", "audit", "labels",
];

const actualFiles = [...trees.keys()].sort();
const expectedSorted = [...EXPECTED_FILES].sort();
if (JSON.stringify(actualFiles) !== JSON.stringify(expectedSorted)) {
  problems.push(
    `target file set mismatch:\n  expected: ${expectedSorted.join(", ")}\n  actual:   ${actualFiles.join(", ")}`
  );
}

let totalEn = 0;
let totalFr = 0;
const report: Array<{ file: string; en: number; fr: number }> = [];

for (const file of EXPECTED_FILES) {
  const t = trees.get(file);
  if (!t) {
    problems.push(`target file "${file}" never produced (empty dictionary?)`);
    continue;
  }
  const enCount = countLeaves(t.en);
  const frCount = countLeaves(t.fr);
  totalEn += enCount;
  totalFr += frCount;
  report.push({ file, en: enCount, fr: frCount });

  const source =
    file === "labels"
      ? "domain-label keys of common.ts"
      : `the "${file}*" keys of the dict files (see ${[...fileTargets.entries()]
          .filter(([, targets]) => targets.has(file))
          .map(([src]) => `${src}.ts`)
          .join(", ")})`;

  for (const locale of ["en", "fr"] as const) {
    const body = renderTree(t[locale], 1);
    const php =
      `<?php\n\n` +
      `// OSSZ ATELIER — ${locale.toUpperCase()} lines, ported from ${source}.\n` +
      `// Accessed via __('${file}.key').\n\n` +
      `return [\n${body}\n];\n`;
    const dir = join(OUT_ROOT, locale);
    mkdirSync(dir, { recursive: true });
    writeFileSync(join(dir, `${file}.php`), php, "utf8");
  }
}

// ----------------------------------------------------------------------- report

console.log("Source-dict → target-file mapping:");
for (const [name, targets] of fileTargets) {
  console.log(`  ${name}.ts (${Object.keys(DICTS.find(([n]) => n === name)![1]!).length} keys) -> ${[...targets].sort().join(", ")}`);
}
if (labelSources.size > 0 && !(labelSources.size === 1 && labelSources.has("common"))) {
  problems.push(`label-prefixed keys found outside common.ts: ${[...labelSources].join(", ")}`);
}

console.log("\nGenerated files (leaf counts):");
for (const r of report) console.log(`  ${r.file}.php  en=${r.en}  fr=${r.fr}`);
console.log(`\nTOTAL: en=${totalEn} fr=${totalFr} files=${report.length}`);

if (problems.length > 0) {
  console.error(`\n!! ${problems.length} PROBLEM(S):`);
  for (const p of problems) console.error(`  - ${p}`);
  process.exit(1);
}
console.log("\nOK — no duplicate keys, no structural conflicts, no empty/newline values.");
