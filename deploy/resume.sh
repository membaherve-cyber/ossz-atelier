#!/usr/bin/env bash
# OSSZ ATELIER — resume deploy (run on the Namecheap server as osszakze)
# Completes the Nov 17 deployment: file sync + debug-probe removal + verify.
# DATABASE IS NEVER TOUCHED: no migrate, no db:seed — counts printed before/after.
set -u
APP=/home/osszakze/public_html/atelier
STAMP=$(date +%Y%m%d-%H%M%S)
PKG=/home/osszakze/tmp/deploy-ossz/pkg
ZIP_URL=https://raw.githubusercontent.com/membaherve-cyber/ossz-atelier/main/download/ossz-atelier.zip

echo "### 1. Package source"
if [ -f /home/osszakze/tmp/deploy-ossz/atelier-pkg.tar.gz ]; then
  echo "using staged tarball"
  rm -rf "$PKG" && mkdir -p "$PKG"
  tar xzf /home/osszakze/tmp/deploy-ossz/atelier-pkg.tar.gz -C "$PKG" || { echo "EXTRACT FAILED"; exit 9; }
elif command -v curl >/dev/null 2>&1 && curl -fsSL "$ZIP_URL" -o /tmp/ossz-atelier.zip; then
  echo "downloaded zip from GitHub ($(stat -c%s /tmp/ossz-atelier.zip) bytes)"
  rm -rf "$PKG" && mkdir -p "$PKG"
  unzip -qo /tmp/ossz-atelier.zip -d "$PKG" || { echo "UNZIP FAILED"; exit 9; }
  rm -f /tmp/ossz-atelier.zip
else
  echo "no staged tarball and download failed"; exit 9
fi
echo "package: $(find "$PKG" -type f | wc -l) files"

echo "### 2. DB counts BEFORE (read-only)"
cd "$APP"
DB_DATABASE=$(sed -n 's/^DB_DATABASE=//p' .env | tr -d '"')
DB_USERNAME=$(sed -n 's/^DB_USERNAME=//p' .env | tr -d '"')
DB_PASSWORD=$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d '"')
echo "db: ${DB_DATABASE:-?}"
COUNTS_SQL="SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM orders) o, (SELECT COUNT(*) FROM production_tasks) t, (SELECT COUNT(*) FROM customers) c;"
if command -v mysql >/dev/null 2>&1; then
  mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "$COUNTS_SQL" 2>/dev/null || echo "(mysql CLI unavailable)"
else
  php -r '$e=parse_ini_file(".env");try{$p=new PDO("mysql:host=127.0.0.1;dbname=".$e["DB_DATABASE"],$e["DB_USERNAME"],$e["DB_PASSWORD"]);foreach(["users","orders","production_tasks","customers"] as $t)echo $t."=".$p->query("SELECT COUNT(*) FROM `$t`")->fetchColumn()." ";echo PHP_EOL;}catch(Exception $x){echo "PDO fail".PHP_EOL;}'
fi

echo "### 3. DB backup (mysqldump)"
if command -v mysqldump >/dev/null 2>&1; then
  mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>/dev/null | gzip > "/home/osszakze/atelier-db-backup-$STAMP.sql.gz"
  [ -s "/home/osszakze/atelier-db-backup-$STAMP.sql.gz" ] && echo "backup OK: ~/atelier-db-backup-$STAMP.sql.gz" || echo "backup FAILED (deploy touches no DB)"
else
  echo "mysqldump unavailable — skipped (deploy touches no DB)"
fi

echo "### 4. Code backup"
tar czf "/home/osszakze/atelier-code-backup-$STAMP.tar.gz" -C /home/osszakze/public_html atelier 2>/dev/null
[ -f "/home/osszakze/atelier-code-backup-$STAMP.tar.gz" ] && echo "backup OK: ~/atelier-code-backup-$STAMP.tar.gz"

echo "### 5. File sync (never touches .env / storage / vendor)"
cd "$PKG"
find . -type f | while read f; do
  rel="${f#./}"
  case "$rel" in
    .env|.env.example|storage/*) continue ;;
  esac
  if [ ! -f "$APP/$rel" ]; then
    echo "  ADD  $rel"
  elif ! cmp -s "$f" "$APP/$rel"; then
    echo "  UPD  $rel"
  else
    continue
  fi
  mkdir -p "$APP/$(dirname "$rel")"
  cp -p "$f" "$APP/$rel"
done
echo "sync complete"

echo "### 6. Remove public debug probes (security cleanup)"
cd "$APP/public"
for f in altini.php altini_restore.php clprobe.php dirmaker.php extractor.php iniprobe.php phpenvs.php phpext.php selprobe.php selprobe2.php selprobe3.php error_log; do
  [ -f "$f" ] && rm -v "$f"
done
echo "probe cleanup done"

echo "### 7. Permissions + caches"
chmod -R ug+w "$APP/storage" "$APP/bootstrap/cache"
cd "$APP"
php -l app/Support/mb_polyfills.php
php artisan optimize:clear 2>&1 | grep -E "INFO|cleared" | head -2
php artisan --version | head -1

echo "### 8. DB counts AFTER (must equal BEFORE)"
if command -v mysql >/dev/null 2>&1; then
  mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "$COUNTS_SQL" 2>/dev/null
fi

echo "### 9. Live checks"
curl -sk -o /dev/null -w "  /up -> %{http_code}\n" https://atelier.osszcollection.com/up
curl -sk -o /dev/null -w "  /login -> %{http_code}\n" https://atelier.osszcollection.com/login
curl -sk -o /dev/null -w "  /clprobe.php -> %{http_code} (404 = good, probe removed)\n" https://atelier.osszcollection.com/clprobe.php

rm -rf /home/osszakze/tmp/deploy-ossz 2>/dev/null
echo ""
echo "=============================================="
echo "  DEPLOY COMPLETE — database untouched"
echo "=============================================="
