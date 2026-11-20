# Deploying OSSZ ATELIER to Namecheap shared hosting (cPanel)

This is the deployment recipe used for **atelier.osszcollection.com**. The app runs on a Namecheap Stellar shared plan (LiteSpeed + PHP 8.2, MySQL) with the subdomain's document root pointed at the app's `public/` directory.

## 1. Create the subdomain + database (cPanel)

1. **cPanel → Domains → Create A New Domain** (or Subdomains):
   - Name: `atelier.osszcollection.com`
   - Document root: `/home/USER/atelier.osszcollection.com` (delete the auto-created files inside)
2. **cPanel → MySQL Databases**:
   - Create database `osszakze_atelier`
   - Create user `osszakze_atelier` with a strong password, add to the database with **ALL PRIVILEGES**
3. **cPanel → MultiPHP Manager**: set the subdomain to **PHP 8.2** (or 8.3).

## 2. Upload the application

Option A — Git (cPanel → **Git Version Control**):

```
Clone URL: https://github.com/membaherve-cyber/ossz-atelier.git
Branch:    laravel        # Laravel app source (deployable)
```

> The `main` branch holds the Next.js development build; the `laravel`
> branch holds this Laravel application at the repository root — clone
> that branch into the subdomain folder.

Option B — Upload:

```bash
# on your machine
composer install --no-dev --optimize-autoloader
zip -r ossz-atelier.zip . -x ".git/*"
```

Upload `ossz-atelier.zip` via cPanel **File Manager** into the subdomain folder and **Extract**. Move the contents so the app root is directly `/home/USER/atelier.osszcollection.com/` (artisan, app/, public/, …).

## 3. Environment + keys

In the app root (File Manager → +File):

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_URL=https://atelier.osszcollection.com
APP_KEY=            # generate (step 4)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osszakze_atelier
DB_USERNAME=osszakze_atelier
DB_PASSWORD=your-db-password
```

## 4. Generate the app key + migrate (cPanel Terminal)

cPanel → **Terminal** (or SSH):

```bash
cd ~/atelier.osszcollection.com
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force   # demo dataset + demo accounts
```

> If the cPanel PHP CLI differs from the site PHP, prefix with the version, e.g. `/opt/cpanel/ea-php82/root/usr/bin/php artisan …`

## 5. Point the document root at public/

The domain must serve `public/` (never the app root — `.env` and `database/` would be exposed otherwise).

- cPanel → **Domains → Manage** (or Subdomains → Edit): set Document Root to
  `/home/USER/atelier.osszcollection.com/public`

The included `public/.htaccess` handles the front controller, HTTPS redirects and blocks sensitive files.

## 6. Daily report e-mail (mail transport)

The application sends an automated **daily business-health report**
(Administration → Daily Report) to the distribution list —
`info@osszcollection.com` is active by default. Configure the transport in
`.env`:

**Option A — sendmail (recommended on Stellar shared hosting, zero
credentials):**

```env
MAIL_MAILER=sendmail
MAIL_FROM_ADDRESS="atelier@osszcollection.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Option B — authenticated SMTP through a domain mailbox (best
deliverability):** create a mailbox in cPanel (e.g. `atelier@osszcollection.com`),
then:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.osszcollection.com
MAIL_PORT=465
MAIL_USERNAME="atelier@osszcollection.com"
MAIL_PASSWORD="the mailbox password"
MAIL_FROM_ADDRESS="atelier@osszcollection.com"
MAIL_FROM_NAME="${APP_NAME}"
```

After changing `.env` run `php artisan optimize:clear`. Verify delivery from
the app: **Administration → Daily Report → Send Test to My Inbox**
(logged in as Management). For development, `MAIL_MAILER=log` writes the
rendered e-mail to `storage/logs/laravel.log` instead of sending.

## 7. Daily report cron job (cPanel)

The scheduled send needs ONE cron entry. In cPanel → **Cron Jobs** → add
(run every minute — the application decides when to send, using the send
time + timezone configured on the Daily Report page):

```
* * * * * cd /home/USER/atelier.osszcollection.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

(Adjust the PHP path to the MultiPHP version chosen in step 1 — the
command `which php` in cPanel Terminal shows the right binary.)

Alternative — a single daily entry instead of every minute (fires at
07:00 **server** time, so prefer the every-minute variant above):

```
0 7 * * * cd /home/USER/atelier.osszcollection.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan atelier:daily-report >> /dev/null 2>&1
```

Notes:

- The report sends at the time configured in **Administration → Daily
  Report** (default 07:00 Africa/Douala) and never twice per day
  (scheduled runs are deduplicated in `daily_report_logs`).
- Recipients, send time, timezone and report sections are managed in the
  app by Management — no code edits needed.
- Every run (scheduled, manual, test) is recorded in the Send History on
  the same page.

## 8. Permissions (storage & bootstrap/cache)

```bash
cd ~/atelier.osszcollection.com
chmod -R ug+w storage bootstrap/cache
```

(storage holds compiled views, sessions and cache — must be writable by PHP)

## 9. First login + hardening

- Browse to `https://atelier.osszcollection.com` → you are redirected to `/login`
- Sign in with `management@atelier.com` / `Atelier@2026`
- **Users & Roles → Reset Password** for every demo account
- Verify the language switch (EN / FR) and the bell notifications

## Updating

```bash
cd ~/atelier.osszcollection.com
git pull            # or upload changed files
php artisan migrate --force
php artisan optimize:clear
```

## Troubleshooting

| Symptom | Fix |
|---|---|
| 500 on first load | `storage/` not writable → step 6; or missing `.env` → step 3 |
| Blank page | Check `storage/logs/laravel.log` |
| "No application encryption key" | `php artisan key:generate --force` |
| Styles missing | Document root must be `public/` (step 5), not the app root |
| Login loops back | `APP_URL` must match the real domain incl. https |
| CSRF 419 | Cookie domain issues — keep `SESSION_DOMAIN=null` |

## Notes

- SQLite works for local dev (`DB_CONNECTION=sqlite`) — production uses MySQL
- The `/up` health endpoint returns 200 when the app is up (Laravel default)
- Queue stays on the `database` driver — notifications are in-app; the ONLY e-mail the app sends is the daily report (see §6/§7)
