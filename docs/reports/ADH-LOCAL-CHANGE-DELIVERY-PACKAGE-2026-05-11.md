# ADH Local Change Delivery Package - 2026-05-11

## Scope

This package records the latest local predeploy work completed before Hetzner access.
It is not a production go-live approval. Production readiness still requires server-side
cron, queue worker, environment, deploy, and public URL evidence.

## Delivered Local Workstreams

### 1. Production scheduler alignment

Status: PASS locally

- `routes/console.php` no longer exposes the default Laravel `inspire` schedule.
- `iha:sync` is scheduled every 15 minutes without `--inline`, so cron only queues the work.
- Scheduler failure logging is wired through `[SCHEDULER_FAILURE]`.
- Supporting production tasks are present for monitor, image refresh, archive, pharmacy,
  prayer, weather, editorial recalculation, and sitemap generation.

Evidence:

- `php artisan schedule:list` shows `iha:sync` without `--inline`.
- `php artisan schedule:list` no longer shows `inspire`.

Production requirement:

- Hetzner must run one cron entry: `* * * * cd /var/www/haber-sitesi && php artisan schedule:run >> /dev/null 2>&1`
- Hetzner must run a persistent queue worker via Supervisor or systemd.

### 2. IHA ingest health and quality

Status: PASS locally, BLOCKED for production

- Local IHA monitor returned healthy output.
- Latest monitor evidence showed `quality_risk=no`, `empty_content=0`, `weak_body=0`,
  `short_body=0`, and `body_depth_ratio=1.00`.
- `queue:failed` had no failed jobs.

Known local limitation:

- Local database had a large `jobs` backlog because this machine is not the production
  queue host. This must not be treated as production evidence.
- Production acceptance requires proving the queue worker consumes IHA jobs on Hetzner.

Reference report:

- `docs/reports/ADH-LOCAL-GO-LIVE-SANITY-2026-05-11.md`

### 3. Advertisement system readiness

Status: PASS locally, BLOCKED for production publisher credentials

- Manual banner placements were tested on public homepage and article detail pages.
- AdSense placement rendering was tested with temporary local smoke credentials.
- Impression and click endpoints returned successful responses and counters incremented.
- Temporary `ADH_SMOKE_*` ads, temporary test images, and test `ca-pub` setting were removed.
- Public HTML was rechecked for absence of smoke traces.

Production requirement:

- Real Google AdSense publisher/client ID and slots must be entered in admin settings.
- `ads.txt` must be configured for the real publisher account before live monetization.
- Final live ad rendering must be checked after domain and AdSense approval state are known.

Reference report:

- `docs/reports/ADH-ADVERTISING-READINESS-2026-05-11.md`

### 4. Dark mode compatibility fixes

Status: PASS locally

- Public static page side navigation no longer renders as a bright card in dark mode.
- Admin Media Library display/filter controls now use admin theme-aware colors in dark mode.
- In-app browser smoke covered homepage, static page, admin dashboard, admin advertisement
  page, admin IHA health page, and admin media library.

Primary files:

- `resources/views/pages/show.blade.php`
- `resources/views/filament/pages/media-library.blade.php`
- `resources/css/admin-guide.css`

Reference report:

- `docs/reports/ADH-DARK-MODE-SMOKE-2026-05-11.md`

### 5. Public/admin smoke coverage

Status: PASS locally

Validated local public paths:

- `/`
- `/kategori/gundem`
- `/iletisim`
- `/sayfa/hakkimizda`
- `/sayfa/gizlilik-politikasi`
- `/sayfa/kvkk-aydinlatma`
- `/sayfa/cerez-politikasi`
- Latest sampled IHA article detail path

Validated admin areas:

- Dashboard
- Advertisement management
- Media Library
- IHA Health
- Settings-related smoke routes from the targeted test suite

## Test Evidence

Recent local test runs passed:

- IHA command/job/monitor/unit service suite: 23 passed, 143 assertions.
- Public/admin targeted suite: 26 passed, 201 assertions.
- Advertisement/public/media hardening suite: 26 passed, 181 assertions.
- Full targeted Filament suite from the dark mode pass: 58 passed, 578 assertions.
- `php artisan deploy:verify --base-url=http://127.0.0.1:8000`: all checks passed.

## Production Go/No-Go State

Local predeploy status: PASS with caveats

Production status: BLOCKED

Blocked until Hetzner access is available for:

- Real `.env` verification without printing secrets.
- PHP, Composer, Node, MySQL, Nginx checks.
- Migration/build/optimize/storage-link verification.
- Cron proof.
- Supervisor/systemd queue worker proof.
- Queued IHA sync proof on server.
- Public live domain smoke.
- AdSense live publisher configuration.

## Git/Delivery Risk

The working tree is broad and contains many tracked and untracked changes from the larger
project history. Do not use `git add .` for delivery.

Recommended delivery approach:

1. Review an exact staging list before commit.
2. Stage only the files required for the intended release scope.
3. Keep local/generated/unclassified files out of the release commit.
4. Treat this report as a scope map, not as a complete git diff.

## Recommended Next Step

Prepare a selective staging and commit plan for the local-ready scope, then wait for Hetzner
access to execute the production go/no-go runbook.
