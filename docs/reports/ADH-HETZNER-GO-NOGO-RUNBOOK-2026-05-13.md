# ADH Hetzner Production Go/No-Go Runbook - 2026-05-13

## Summary

This runbook is for the first Hetzner production session. It must be executed on the server, not on the local Windows development machine.

Production go-live is allowed only if every required evidence item below passes.

## Required Production Environment

`.env` values to verify by presence/shape only, without printing secrets:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adiyamandijitalhaber.com.tr
QUEUE_CONNECTION=database
DB_QUEUE=default
FILESYSTEM_DISK=public
IHA_SYNC_INTERVAL=15
IHA_MIN_BODY_LENGTH=280
INSTAGRAM_ENABLED=false_or_true_by_business_decision
```

Secrets that must be present or intentionally deferred:

- `APP_KEY`
- MySQL credentials
- SMTP credentials
- `IHA_USER_CODE`
- `IHA_USERNAME`
- `IHA_PASSWORD`
- Instagram token and business account if Instagram auto-publish is enabled
- AdSense client/slot settings if ads are enabled

## Deploy Sequence

Use the real production branch and remote.

```bash
cd /var/www/haber-sitesi
git fetch --all --prune
git checkout __PRODUCTION_BRANCH__
git pull --ff-only

composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
php artisan sitemap:generate

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data /var/www/haber-sitesi
sudo find /var/www/haber-sitesi/storage /var/www/haber-sitesi/bootstrap/cache -type d -exec chmod 775 {} \;
sudo find /var/www/haber-sitesi/storage /var/www/haber-sitesi/bootstrap/cache -type f -exec chmod 664 {} \;
```

`php artisan sitemap:generate` is mandatory because `public/sitemap.xml` is generated and ignored by git.

## Cron and Worker

Cron must contain exactly the Laravel scheduler model:

```bash
* * * * cd /var/www/haber-sitesi && php artisan schedule:run >> /dev/null 2>&1
```

Queue worker must listen to all required queues:

```bash
php artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
```

Supervisor example:

```ini
[program:adh-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/haber-sitesi/artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/haber-sitesi/storage/logs/worker.log
stopwaitsecs=3600
```

## Evidence Commands

Run and save outputs:

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
crontab -l
supervisorctl status || systemctl status adh-worker
php artisan queue:failed
php artisan deploy:verify --base-url=https://adiyamandijitalhaber.com.tr
php artisan iha:sync --limit=3
php artisan queue:work database --queue=default,analytics,instagram --once
php artisan iha:monitor-forward --limit=20
```

Expected scheduler proof:

- `php artisan iha:sync` appears.
- `iha:sync --inline` does not appear.
- `php artisan sitemap:generate` appears.

Expected deploy verification proof:

- `/health` OK.
- Homepage OK.
- `/robots.txt` OK and includes `Sitemap:`.
- `/sitemap.xml` OK and includes XML `urlset`.

Expected IHA proof:

- Controlled queued sync creates a log.
- Worker processes the job.
- Latest log ends as `success` or acceptable `partial`.
- No unexplained stale `running` log.
- `empty_content=0`.
- `weak_body=0`.
- `short_body=0`.
- Public latest 3 IHA detail pages return 200 and show body text.

Expected Instagram proof, if enabled:

- Integration settings show configured token/account.
- One controlled published article creates a `social_publications` record.
- Creative URL is public HTTPS and returns 200.
- Worker processes `instagram` queue.
- Record ends as `published` with `media_id`, or expected `skipped` if Instagram is intentionally disabled.

## Public Smoke URLs

Verify in browser after deploy:

- `https://adiyamandijitalhaber.com.tr/`
- `https://adiyamandijitalhaber.com.tr/kategori/spor`
- Latest 3 IHA detail URLs
- `https://adiyamandijitalhaber.com.tr/arama?q=Mardin`
- `https://adiyamandijitalhaber.com.tr/iletisim`
- `https://adiyamandijitalhaber.com.tr/hakkimizda`
- `https://adiyamandijitalhaber.com.tr/gizlilik-politikasi`
- `https://adiyamandijitalhaber.com.tr/kvkk`
- `https://adiyamandijitalhaber.com.tr/cerez-politikasi`
- `https://adiyamandijitalhaber.com.tr/robots.txt`
- `https://adiyamandijitalhaber.com.tr/sitemap.xml`

## Go/No-Go Rule

GO only if:

- `deploy:verify` passes.
- Cron is installed.
- Queue worker is running and listening to `default,analytics,instagram`.
- Controlled IHA queued sync completes.
- `iha:monitor-forward` has no critical state and no unexplained stale `running` state.
- Latest public IHA details show body text.
- Admin login and dashboard load.
- Static policy pages and contact page return 200.
- Storage public assets return 200.
- No current unexplained Laravel error in `storage/logs`.

NO-GO if any of the following occurs:

- `APP_DEBUG=true` in production.
- `/health`, `/`, `/robots.txt` or `/sitemap.xml` fails.
- Queue worker is absent.
- Cron is absent.
- Latest IHA sync remains stale `running`.
- Latest IHA details have empty body.
- Public storage assets 404.
- Admin cannot log in.
- SMTP/contact recipient is missing when contact delivery is expected.
- Instagram is enabled but token/account/HTTPS creative URL proof fails.
