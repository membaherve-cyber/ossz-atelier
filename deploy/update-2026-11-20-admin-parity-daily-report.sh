#!/usr/bin/env bash
# OSSZ ATELIER — update deploy (run on the Namecheap server as osszakze)
# Ships TWO changes on top of the Nov 20 enhancement build:
#   1. Daily business-health e-mail (atelier:daily-report + settings page)
#   2. ADMIN account full access to every feature (incl. accounting)
#
# What it does to the DATABASE: runs `php artisan migrate --force` — the
# ONLY pending migration is 2024_01_01_000032_create_daily_report_tables
# (ADDITIVE: creates daily_report_settings / daily_report_recipients /
# daily_report_logs and seeds info@osszcollection.com as the default
# active recipient). No existing table is altered, no row is rewritten.
# A mysqldump backup is taken first; the migration alone is reversible
# (php artisan migrate:rollback --step=1 drops only the 3 new tables).
set -u
APP=/home/osszakze/public_html/atelier
STAMP=$(date +%Y%m%d-%H%M%S)
PKG=/home/osszakze/tmp/deploy-ossz/pkg
ZIP_URL=https://raw.githubusercontent.com/membaherve-cyber/ossz-atelier/main/download/ossz-atelier.zip

echo "### 1. Package source"
if curl -fsSL "$ZIP_URL" -o /tmp/ossz-atelier.zip; then
  echo "downloaded zip from GitHub ($(stat -c%s /tmp/ossz-atelier.zip) bytes)"
  rm -rf "$PKG" && mkdir -p "$PKG"
  unzip -qo /tmp/ossz-atelier.zip -d "$PKG" || { echo "UNZIP FAILED"; exit 9; }
  rm -f /tmp/ossz-atelier.zip
else
  echo "download failed"; exit 9
fi
echo "package: $(find "$PKG" -type f | wc -l) files"

echo "### 2. DB backup (mysqldump)"
cd "$APP"
DB_DATABASE=$(sed -n 's/^DB_DATABASE=//p' .env | tr -d '"')
DB_USERNAME=$(sed -n 's/^DB_USERNAME=//p' .env | tr -d '"')
DB_PASSWORD=$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d '"')
echo "db: ${DB_DATABASE:-?}"
if command -v mysqldump >/dev/null 2>&1; then
  mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>/dev/null | gzip > "/home/osszakze/atelier-db-backup-$STAMP.sql.gz"
  [ -s "/home/osszakze/atelier-db-backup-$STAMP.sql.gz" ] && echo "backup OK: ~/atelier-db-backup-$STAMP.sql.gz" || { echo "backup FAILED — aborting"; exit 9; }
else
  echo "mysqldump unavailable — aborting (will not migrate without backup)"; exit 9
fi

echo "### 3. Code backup"
tar czf "/home/osszakze/atelier-code-backup-$STAMP.tar.gz" -C /home/osszakze/public_html atelier 2>/dev/null
[ -f "/home/osszakze/atelier-code-backup-$STAMP.tar.gz" ] && echo "backup OK: ~/atelier-code-backup-$STAMP.tar.gz"

echo "### 4. File sync (never touches .env / storage / vendor)"
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

echo "### 5. Migrate (daily_report_* tables — additive only)"
cd "$APP"
PHP_BIN=$(command -v php || echo /opt/cpanel/ea-php82/root/usr/bin/php)
"$PHP_BIN" artisan migrate --force 2>&1 | tail -8

echo "### 6. Permissions + caches"
chmod -R ug+w "$APP/storage" "$APP/bootstrap/cache"
cd "$APP"
"$PHP_BIN" artisan optimize:clear 2>&1 | grep -E "INFO|cleared" | head -2
"$PHP_BIN" artisan --version | head -1

echo "### 7. Scheduled task registered?"
"$PHP_BIN" artisan schedule:list 2>/dev/null | head -3

echo "### 8. Live checks"
curl -sk -o /dev/null -w "  /up           -> %{http_code}\n" https://atelier.osszcollection.com/up
curl -sk -o /dev/null -w "  /login        -> %{http_code}\n" https://atelier.osszcollection.com/login
curl -sk -o /dev/null -w "  /accounting   -> %{http_code} (403 = route alive, guest blocked)\n" https://atelier.osszcollection.com/accounting
curl -sk -o /dev/null -w "  /daily-report -> %{http_code} (403 = NEW feature live, 404 = not deployed)\n" https://atelier.osszcollection.com/daily-report

echo ""
echo "=================================================="
echo "  UPDATE COMPLETE"
echo "  Next (in the app, as management@... or admin@...):"
echo "   - Administration -> Daily Report: manage recipients,"
echo "     then 'Send Test to My Inbox', then 'Send Now'"
echo "   - cPanel -> Cron Jobs: ONE entry (see DEPLOYMENT.md §7)"
echo "   - Admin account now sees ACCOUNTING + Daily Report"
echo "=================================================="
