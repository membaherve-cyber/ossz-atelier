#!/usr/bin/env python3
"""
Mechanically port the OSSZ ATELIER TS dictionaries
(src/components/atelier/i18n/dict/*.ts) into Laravel PHP lang files
(laravel-atelier/lang/{en,fr}/*.php).

Rules (spec §7):
  * dict key "seg1.seg2.seg3" -> lang file seg1.php, nested array
    ['seg2' => ['seg3' => value]]
  * domain-label keys (status.*, priority.*, category.*, stageState.*,
    role.*, inspection.*, incentivePeriod.*, incStatus.*, paymentStatus.*,
    notifTitle.*) go to labels.php as nested arrays
  * generic common.* keys stay in common.php
  * {param} placeholders are converted to Laravel :param placeholders so
    __('key', ['param' => value]) works (spec §8 usage)
  * EN and FR files are key-identical by construction (verified)
"""
import json
import re
import sys
from pathlib import Path

DICT_DIR = Path("/home/z/my-project/src/components/atelier/i18n/dict")
OUT_DIR = Path("/home/z/my-project/laravel-atelier/lang")

# dict file -> first segment it owns (module files)
DICT_FILES = [
    ("common.ts", "common"),
    ("shell.ts", "shell"),
    ("login.ts", "login"),
    ("dashboard.ts", "dash"),
    ("notifications.ts", "notif"),
    ("tasks.ts", "tasks"),
    ("task-create.ts", "tc"),
    ("task-detail.ts", "td"),
    ("orders.ts", "orders"),
    ("customers.ts", "cust"),
    ("performance.ts", "perf"),
    ("incentives.ts", "inc"),
    ("reports.ts", "rep"),
    ("users.ts", "users"),
    ("stages.ts", "stages"),
    ("audit.ts", "audit"),
]

LABEL_SEGMENTS = {
    "status", "priority", "category", "stageState", "role", "inspection",
    "incentivePeriod", "incStatus", "paymentStatus", "notifTitle",
}

EXPECTED_SEGMENTS = {seg for _, seg in DICT_FILES} | LABEL_SEGMENTS

TS_STRING = r'"(?:[^"\\]|\\.)*"'
ENTRY_RE = re.compile(
    r'"((?:[^"\\]|\\.)+)"\s*:\s*\{\s*en\s*:\s*(' + TS_STRING + r')\s*,\s*fr\s*:\s*(' + TS_STRING + r')\s*,?\s*\}',
    re.DOTALL,
)
KEY_HINT_RE = re.compile(r'"((?:[^"\\]|\\.)+)"\s*:\s*\{')


def unescape_js(s: str) -> str:
    out = []
    i = 0
    while i < len(s):
        c = s[i]
        if c == "\\" and i + 1 < len(s):
            n = s[i + 1]
            if n == "n":
                out.append("\n"); i += 2
            elif n == "t":
                out.append("\t"); i += 2
            elif n == "r":
                out.append("\r"); i += 2
            elif n == "b":
                out.append("\b"); i += 2
            elif n == "f":
                out.append("\f"); i += 2
            elif n == "0":
                out.append("\0"); i += 2
            elif n == "u":
                if i + 2 < len(s) and s[i + 2] == "{":
                    j = s.index("}", i + 3)
                    out.append(chr(int(s[i + 3:j], 16))); i = j + 1
                else:
                    out.append(chr(int(s[i + 2:i + 6], 16))); i += 6
            elif n in ('"', "'", "\\", "/"):
                out.append(n); i += 2
            else:
                out.append(n); i += 2
        else:
            out.append(c); i += 1
    return "".join(out)


PLACEHOLDER_RE = re.compile(r"\{([A-Za-z_][A-Za-z0-9_]*)\}")


def to_laravel_placeholders(value: str) -> str:
    return PLACEHOLDER_RE.sub(r":\1", value)


def php_sq(value: str) -> str:
    """Escape a UTF-8 string for PHP single-quoted output."""
    return value.replace("\\", "\\\\").replace("'", "\\'")


def strip(tree):
    """Convert (en, fr) tuples to locale sub-trees."""
    en_tree, fr_tree = {}, {}
    for k, v in tree.items():
        if isinstance(v, dict):
            e, f = strip(v)
            en_tree[k], fr_tree[k] = e, f
        else:
            en_tree[k], fr_tree[k] = v[0], v[1]
    return en_tree, fr_tree


def key_paths(tree, prefix=""):
    paths = []
    for k, v in tree.items():
        p = f"{prefix}{k}"
        if isinstance(v, dict):
            paths.extend(key_paths(v, p + "."))
        else:
            paths.append(p)
    return paths


def render_array(tree: dict, indent: int = 1) -> str:
    pad = "    " * indent
    lines = []
    for k, v in tree.items():
        if isinstance(v, dict):
            lines.append(f"{pad}'{php_sq(k)}' => [")
            lines.append(render_array(v, indent + 1))
            lines.append(f"{pad}],")
        else:
            lines.append(f"{pad}'{php_sq(k)}' => '{php_sq(v)}',")
    return "\n".join(lines)


def main():
    all_entries = {}
    placeholder_count = 0

    for fname, expected_seg in DICT_FILES:
        src = (DICT_DIR / fname).read_text(encoding="utf-8")
        matches = ENTRY_RE.findall(src)
        hints = KEY_HINT_RE.findall(src)
        if len(matches) != len(hints):
            print(f"!! {fname}: {len(hints)} keys detected but only {len(matches)} parsed — investigate", file=sys.stderr)
            sys.exit(1)
        for key_raw, en_raw, fr_raw in matches:
            key = unescape_js(key_raw)
            if key != key_raw:
                print(f"!! {fname}: escaped chars in key {key_raw!r}", file=sys.stderr)
                sys.exit(1)
            en = to_laravel_placeholders(unescape_js(en_raw))
            fr = to_laravel_placeholders(unescape_js(fr_raw))
            segs = key.split(".")
            first = segs[0]
            if first not in EXPECTED_SEGMENTS:
                print(f"!! {fname}: unexpected first segment {first!r} in {key!r}", file=sys.stderr)
                sys.exit(1)
            if first in LABEL_SEGMENTS:
                target = "labels"
                rest = segs
            elif first == "common":
                target = "common"
                rest = segs
            else:
                target = first
                rest = segs
                if first != expected_seg:
                    print(f"!! {fname}: first segment {first!r} != expected {expected_seg!r}", file=sys.stderr)
                    sys.exit(1)
            if PLACEHOLDER_RE.search(en) or PLACEHOLDER_RE.search(fr):
                print(f"!! unconverted braces remain in {key!r}", file=sys.stderr)
                sys.exit(1)
            if "{" in en or "}" in en or "{" in fr or "}" in fr:
                print(f"!! leftover brace in {key!r}: en={en!r} fr={fr!r}", file=sys.stderr)
                sys.exit(1)
            if re.search(r":[A-Za-z_]", en) or re.search(r":[A-Za-z_]", fr):
                placeholder_count += 1
            all_entries.setdefault(target, []).append((rest, key, en, fr))

    # Build per-file trees for both locales.
    stats = {}
    for target, entries in all_entries.items():
        tree = {}
        for rest, key, en, fr in entries:
            node = tree
            for seg in rest[:-1]:
                node = node.setdefault(seg, {})
                if not isinstance(node, dict):
                    raise SystemExit(f"Leaf/branch conflict at {key}")
            if rest[-1] in node:
                raise SystemExit(f"Duplicate leaf {key}")
            node[rest[-1]] = (en, fr)
        en_tree, fr_tree = strip(tree)
        assert key_paths(en_tree) == key_paths(fr_tree), f"EN/FR mismatch in {target}"
        stats[target] = len(key_paths(en_tree))

        header = (
            "<?php\n\n"
            "/*\n"
            "|----------------------------------------------------------------------\n"
            f"| OSSZ ATELIER language file — {target}\n"
            "|----------------------------------------------------------------------\n"
            "|\n"
        )
        if target == "labels":
            header += (
                "| Domain labels (status/priority/category/stageState/role/\n"
                "| inspection/incentivePeriod/incStatus/paymentStatus/notifTitle)\n"
                "| ported from src/components/atelier/i18n/dict/common.ts.\n"
                "| Usage: __('labels.status.ONGOING').\n"
                "|\n"
            )
        else:
            src_file = next(f for f, s in DICT_FILES if s == target)
            header += (
                f"| Ported mechanically from src/components/atelier/i18n/dict/{src_file}\n"
                f"| (keys preserved verbatim; {{param}} placeholders converted to\n"
                f"| Laravel :param placeholders). Usage: __('{target}.…').\n"
                "|\n"
            )
        header += "*/\n\n"

        for locale, tree_locale in (("en", en_tree), ("fr", fr_tree)):
            out = OUT_DIR / locale / f"{target}.php"
            out.parent.mkdir(parents=True, exist_ok=True)
            body = "return [\n" + render_array(tree_locale) + "\n];\n"
            out.write_text(header + body, encoding="utf-8")

    grand = sum(stats.values())
    print(json.dumps({"per_file": stats, "total_keys_per_locale": grand,
                      "strings_with_placeholders": placeholder_count}, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
