# ADH Selective Staging Plan - 2026-05-11

## Purpose

Define a safe staging strategy for the current local-ready ADH codebase without using
`git add .`.

The repository working tree contains a large number of tracked modifications and untracked
application files. A narrow patch commit may be useful for the latest fixes, but it will not
represent the full deployable application if the target branch does not already contain the
untracked application baseline.

## Current Git Reality

Status: DIRTY / BROAD

Observed categories:

- Tracked modifications: framework/config/build files such as `.env.example`, `composer.json`,
  `package.json`, `routes/console.php`, `routes/web.php`, `resources/css/app.css`,
  `resources/js/app.js`, and related bootstrap/config files.
- Tracked deletions: default starter files such as `public/robots.txt` and
  `resources/views/welcome.blade.php`.
- Untracked application baseline: most ADH app code under `app/`, `database/`, `resources/`,
  `routes/api.php`, `tests/`, `lang/`, `config/*`, and documentation.
- Unclassified root artifacts: `get([` and `where(` are present and should not be staged
  without inspection.

Risk:

- `git add .` may include local/generated/unclassified files.
- Staging only the latest visible patch may omit required application files if the target
  branch is still close to a Laravel starter baseline.

## Staging Strategy

### Option A - Full local-ready release snapshot

Use this when the target repository/branch does not already contain the ADH application
baseline.

Stage deliberately:

- `.env.example`
- `.env.production.example`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `phpunit.xml`
- `vite.config.js`
- `tailwind.config.js`
- `postcss.config.js`
- `bootstrap/`
- `config/`
- `app/`
- `database/migrations/`
- `database/seeders/`
- `lang/`
- `routes/`
- `resources/css/`
- `resources/js/`
- `resources/views/`
- `tests/`
- Required static public assets under `public/images/`, `public/css/`, and `public/js/`
  after verifying they are referenced by the app.
- Selected docs and reports listed in this plan.

Also stage intentional deletions:

- `resources/views/welcome.blade.php`, if the starter page is intentionally removed.
- `public/robots.txt`, only if robots output is generated elsewhere or intentionally omitted.

Do not stage without review:

- `.env`
- local SQLite databases
- `storage/`
- `vendor/`
- `node_modules/`
- `public/build/` unless the deployment policy intentionally commits build assets
- `public/sitemap.xml` unless the deployment policy intentionally commits generated sitemap
- `get([`
- `where(`
- local screenshots, temporary exports, smoke assets, logs, and cache files

### Option B - Latest patch-only commit

Use this only if the target branch already contains the complete ADH application baseline.

Stage the latest local-ready patch set:

- `routes/console.php`
- `resources/views/pages/show.blade.php`
- `resources/views/filament/pages/media-library.blade.php`
- `resources/css/admin-guide.css`
- `docs/reports/ADH-DARK-MODE-SMOKE-2026-05-11.md`
- `docs/reports/ADH-ADVERTISING-READINESS-2026-05-11.md`
- `docs/reports/ADH-LOCAL-GO-LIVE-SANITY-2026-05-11.md`
- `docs/reports/ADH-LOCAL-CHANGE-DELIVERY-PACKAGE-2026-05-11.md`
- `docs/reports/ADH-SELECTIVE-STAGING-PLAN-2026-05-11.md`

Patch-only risk:

- If the remote branch does not already contain the untracked ADH application files, this
  commit is not deployable by itself.

## Documentation To Keep With Release

Recommended release documentation:

- `docs/reports/ADH-FULL-REQUIREMENTS-CHECKLIST-2026-05-09.md`
- `docs/reports/ADH-GO-LIVE-REMAINING-SCOPE-2026-05-11.md`
- `docs/reports/ADH-CUSTOMER-DEMO-PACKAGE-2026-05-11.md`
- `docs/reports/ADH-MOBILE-CRITICAL-SMOKE-2026-05-11.md`
- `docs/reports/ADH-ADMIN-MOBILE-OPERATIONS-SMOKE-2026-05-11.md`
- `docs/reports/ADH-REKLAM-SLOT-PAKETLERI-2026-05-11.md`
- `docs/reports/ADH-ADVERTISING-READINESS-2026-05-11.md`
- `docs/reports/ADH-DARK-MODE-SMOKE-2026-05-11.md`
- `docs/reports/ADH-LOCAL-GO-LIVE-SANITY-2026-05-11.md`
- `docs/reports/ADH-LOCAL-CHANGE-DELIVERY-PACKAGE-2026-05-11.md`
- `docs/reports/ADH-SELECTIVE-STAGING-PLAN-2026-05-11.md`

## Verification Before Commit

Run before staging:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php
php artisan test tests/Feature/Commands/SyncIhaNewsCommandTest.php tests/Feature/Jobs/SyncIhaNewsJobTest.php tests/Feature/Commands/MonitorIhaForwardIngestCommandTest.php tests/Unit/Services/IhaSyncTriggerServiceTest.php
php artisan deploy:verify --base-url=http://127.0.0.1:8000
php artisan schedule:list
php artisan queue:failed
php artisan iha:monitor-forward --limit=20
```

Check before commit:

```bash
git status --short
git diff --cached --stat
git diff --cached --name-only
```

Manual review gates:

- Confirm no secret values are staged.
- Confirm no smoke/test advertisement data is staged.
- Confirm no local scheduler proof is described as production proof.
- Confirm production remains `BLOCKED` until Hetzner evidence exists.

## Recommended Decision

Use Option A for a release branch if the remote repository does not already have the full ADH
application baseline.

Use Option B only for a small follow-up commit after confirming the baseline already exists
upstream.

Do not commit until the target branch state is known.
