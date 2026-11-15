#!/usr/bin/env python3
"""Static verification for resources/views/incentives/index.blade.php."""
import re, glob, sys, json

ROOT = "/home/z/my-project/laravel-atelier"
VIEW = ROOT + "/resources/views/incentives/index.blade.php"
src = open(VIEW, encoding="utf-8").read()
errors = 0

# ---- 1. directive balance ----------------------------------------------------
print("== Directive balance ==")
tokens = re.findall(r"@(if|foreach|php|endif|endforeach|endphp|else|csrf|extends|section|endsection)\b", src)
counts = {}
for t in tokens:
    counts[t] = counts.get(t, 0) + 1
print(" ".join(f"@{k}={v}" for k, v in sorted(counts.items())))
if counts.get("if") != counts.get("endif"):
    print("MISMATCH @if/@endif"); errors += 1
if counts.get("foreach") != counts.get("endforeach"):
    print("MISMATCH @foreach/@endforeach"); errors += 1
if counts.get("php") != counts.get("endphp"):
    print("MISMATCH @php/@endphp"); errors += 1
if counts.get("csrf") != 6:
    print(f"UNEXPECTED @csrf count {counts.get('csrf')} (expected 6)"); errors += 1
if counts.get("section") != counts.get("endsection") + 1:
    print("NOTE @section includes one-liner title (no @endsection needed)"); errors += 1

stack = []
for t in tokens:
    if t in ("if", "foreach", "php"):
        stack.append(t)
    elif t in ("endif", "endforeach", "endphp"):
        expect = {"endif": "if", "endforeach": "foreach", "endphp": "php"}[t]
        if not stack:
            print(f"NESTING ERROR: @{t} with empty stack"); errors += 1; continue
        open_t = stack.pop()
        if open_t != expect:
            print(f"NESTING ERROR: @{t} closes @{open_t}"); errors += 1
    elif t == "else":
        if not stack or stack[-1] != "if":
            print("NESTING ERROR: @else outside @if"); errors += 1
if stack:
    print("UNCLOSED directives: " + ",".join(stack)); errors += 1
print("Balanced." if errors == 0 else "Errors above!")

# ---- 2. lang keys ------------------------------------------------------------
print("\n== Lang key existence (en+fr) ==")
def load_lang(locale):
    lang = {}
    for f in glob.glob(f"{ROOT}/lang/{locale}/*.php"):
        name = f.split("/")[-1][:-4]
        text = open(f, encoding="utf-8").read()
        # naive PHP array parse: only supports the flat/nested 'key' => 'value' + sub-arrays used here
        def parse_block(s):
            out, i, n = {}, 0, len(s)
            while i < n:
                m = re.search(r"^\s*'([^']+)'\s*=>", s[i:], re.M)
                if not m: break
                key = m.group(1); j = i + m.end()
                rest = s[j:]
                if rest.lstrip().startswith("["):
                    depth, k = 0, j
                    while k < n:
                        if s[k] == "[": depth += 1
                        elif s[k] == "]":
                            depth -= 1
                            if depth == 0: break
                        k += 1
                    out[key] = parse_block(s[j + s[j:].lstrip().find("[") + 1:k])
                    i = k + 1
                else:
                    vm = re.match(r"\s*(?:'((?:[^'\\]|\\.)*)'|\"((?:[^\"\\]|\\.)*)\")\s*,", s[j:])
                    if vm:
                        out[key] = (vm.group(1) or vm.group(2)).replace("\\'", "'").replace('\\"', '"')
                        i = j + vm.end()
                    else:
                        i = j
            return out
        # strip the `return [` ... `];` wrapper
        body = text.split("return", 1)[1]
        body = body.strip()
        assert body.startswith("[")
        depth, k = 0, 0
        while k < len(body):
            if body[k] == "[": depth += 1
            elif body[k] == "]":
                depth -= 1
                if depth == 0: break
            k += 1
        lang[name] = parse_block(body[1:k])
    return lang

LANGS = {loc: load_lang(loc) for loc in ("en", "fr")}

def lookup(key, locale):
    parts = key.split(".")
    node = LANGS[locale].get(parts[0])
    if node is None: return False
    for seg in parts[1:]:
        if not isinstance(node, dict) or seg not in node: return False
        node = node[seg]
    return isinstance(node, str)

keys = set(re.findall(r"__\(\s*'([^']+)'", src)) | set(re.findall(r'__\(\s*"([^"]+)"', src))
dynamic = {k for k in keys if k.startswith("labels.") and (".$" in k or k.count(".") > 2 and k.split(".")[-1].isupper() is False)}
key_errors = 0
for key in sorted(keys):
    if ".$" in key or key.endswith("."):  # dynamic concatenation — checked below
        continue
    for loc in ("en", "fr"):
        if not lookup(key, loc):
            print(f"MISSING [{loc}] {key}"); key_errors += 1

# dynamic families
families = {
    "labels.incStatus": ["PENDING", "APPROVED", "REJECTED"],
    "labels.incentivePeriod": ["MONTHLY", "QUARTERLY", "ANNUAL", "QUALITY", "ON_TIME", "CUSTOM"],
    "labels.paymentStatus": ["PAID", "UNPAID"],
}
for prefix, vals in families.items():
    for v in vals:
        for loc in ("en", "fr"):
            if not lookup(f"{prefix}.{v}", loc):
                print(f"MISSING [{loc}] {prefix}.{v}"); key_errors += 1

print(f"All keys present in en+fr ({len(keys)} distinct keys)." if key_errors == 0 else f"{key_errors} missing keys!")
errors += key_errors

# ---- 3. @php block sanity (no PHP runtime: manual-ish check) ------------------
print("\n== @php block ==")
blocks = re.findall(r"@php(.*?)@endphp", src, re.S)
for i, b in enumerate(blocks, 1):
    print(f"block #{i}: {b.strip()[:120]}")
    if b.count("(") != b.count(")"):
        print(f"  UNBALANCED parens in @php block #{i}"); errors += 1
    if b.count("[") != b.count("]"):
        print(f"  UNBALANCED brackets in @php block #{i}"); errors += 1
    if ";" not in b.replace("(;", "("):
        pass

# ---- 4. Alpine sanity --------------------------------------------------------
print("\n== Alpine attribute sanity ==")
alpine_errors = 0
tpl = re.findall(r'<template x-if="([^"]*)"', src)
print(f"template x-if count: {len(tpl)}")
for e in tpl:
    print(f"  x-if: {e}")
clicks = re.findall(r'x-on:click="([^"]*)"', src)
for c in clicks:
    if c.count("'") % 2 != 0:
        print(f"UNBALANCED single quotes in x-on:click: {c}"); alpine_errors += 1
ph = re.findall(r':placeholder="([^"]*)"', src)
for c in ph:
    if c.count("'") % 2 != 0:
        print(f"UNBALANCED single quotes in :placeholder: {c}"); alpine_errors += 1
xd = re.findall(r'x-data="([^"]*)"', src, re.S)
for c in xd:
    if c.count("'") % 2 != 0:
        print("UNBALANCED single quotes in x-data"); alpine_errors += 1
print(f"x-data blocks: {len(xd)}")
print("Alpine quoting OK." if alpine_errors == 0 else f"{alpine_errors} alpine issues!")
errors += alpine_errors

# ---- 5. variables used -------------------------------------------------------
print("\n== PHP variables referenced ==")
used = sorted(set(re.findall(r"\$([a-zA-Z_][a-zA-Z0-9_]*)", src)))
allowed = {"schemes", "records", "filters", "staffList", "canApprove", "canPrepare",
           "canManageSchemes", "canPay", "me", "unread", "scheme", "record", "staff",
           "type", "statusBadge", "loop", "errors"}
for v in used:
    if v not in allowed:
        print(f"  UNKNOWN/verify var: ${v}")
print("vars: " + ", ".join(used))

# ---- 6. route names used -----------------------------------------------------
print("\n== Routes referenced ==")
routes = sorted(set(re.findall(r"route\('([^']+)'", src)))
print(", ".join(routes))
expected_routes = {"incentives.index", "incentives.schemes.store", "incentives.schemes.update",
                   "incentives.generate", "incentives.review", "incentives.payment"}
if set(routes) != expected_routes:
    print(f"ROUTE SET MISMATCH: expected {sorted(expected_routes)}"); errors += 1

# ---- 7. form input names -----------------------------------------------------
print("\n== Form input names ==")
names = re.findall(r'name="([a-zA-Z]+)"', src)
from collections import Counter
for n, c in sorted(Counter(names).items()):
    print(f"  {n} x{c}")

print(f"\nTOTAL ERRORS: {errors}")
sys.exit(1 if errors else 0)
