# ADH Full Snapshot Staging Manifest - 2026-05-11

## Purpose

Define the full release-snapshot staging candidate for the current ADH codebase.

This manifest exists because the local workspace is not a small patch against a complete
upstream ADH baseline. Most ADH application files are currently untracked from Git's point
of view.

No files were staged while preparing this manifest.

## Branch

Current branch:

- `codex/adh-local-predeploy-package`

## Untracked File Distribution

Observed untracked top-level counts:

- `app`: 146
- `resources`: 85
- `tests`: 48
- `database`: 39
- `public`: 25
- `docs`: 15
- `handoff`: 7
- `config`: 5
- `lang`: 3
- single-file candidates: `.env.production.example`, `composer.lock`, `package-lock.json`,
  `postcss.config.js`, `tailwind.config.js`
- unclassified local artifacts: `get([`, `where(`

Interpretation:

- The application baseline is not fully tracked yet.
- A deployable release commit likely needs a full snapshot, not a latest-patch-only commit.

## Full Snapshot Include Candidate

Stage these groups only after the secret/runtime scan passes.

### Application code

- `app/Console/`
- `app/Filament/`
- `app/Http/Controllers/`
- `app/Http/Middleware/`
- `app/Jobs/`
- `app/Mail/`
- `app/Models/`
- `app/Observers/`
- `app/Policies/`
- `app/Providers/`
- `app/Services/`
- `app/Support/`

Tracked modified application files to include:

- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`

### Configuration and bootstrap

- `.env.example`
- `.env.production.example`
- `bootstrap/`
- `config/`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `phpunit.xml`
- `postcss.config.js`
- `tailwind.config.js`
- `vite.config.js`

### Database

- `database/migrations/`
- `database/seeders/`

Tracked modified database files to include:

- `database/seeders/DatabaseSeeder.php`

### Routes

- `routes/web.php`
- `routes/console.php`
- `routes/api.php`

### Public/frontend resources

- `resources/css/`
- `resources/js/`
- `resources/views/`
- `lang/`

Intentional tracked deletion candidate:

- `resources/views/welcome.blade.php`

### Public assets

Candidate public assets:

- `public/images/branding/`
- `public/images/news/`
- `public/css/filament/`
- `public/js/filament/`

Hold for policy decision:

- `public/sitemap.xml`
- generated Filament public assets, if deployment will build or publish them on server

Intentional tracked deletion candidate:

- `public/robots.txt`, only if robots output is generated elsewhere or intentionally omitted.

### Tests

- `tests/Feature/`
- `tests/Unit/`
- modified `tests/Feature/ExampleTest.php`

### Documentation

Recommended docs to include:

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
- `docs/reports/ADH-COMMIT-INVENTORY-2026-05-11.md`
- `docs/reports/ADH-FULL-SNAPSHOT-STAGING-MANIFEST-2026-05-11.md`

Optional historical handoff files:

- `handoff/03-quality/release-audit/`

These are useful as forensic context but not required for runtime.

## Exclude From Staging

Never stage:

- `.env`
- `.phpunit.result.cache`
- `node_modules/`
- `vendor/`
- `storage/`
- local SQLite databases
- logs and cache files
- local screenshots or temporary exports
- smoke-test advertisement assets

Do not stage unless explicitly approved:

- `public/sitemap.xml`
- generated public build assets
- `handoff/` forensic files

Accidental local artifacts to keep out:

- `get([`
- `where(`

Both contain command-line parse error output and are not release files.

## Pre-Staging Scans

Run before staging the full snapshot:

```bash
rg -n "IHA_PASSWORD|IHA_USERNAME|IHA_USER_CODE|APP_KEY|DB_PASSWORD|MAIL_PASSWORD|SENTRY_LARAVEL_DSN|ca-pub-|ADH_SMOKE" --glob "!vendor/**" --glob "!node_modules/**" --glob "!storage/**"
git status --short --untracked-files=all
```

Expected result:

- Secrets must not appear with real values in staged files.
- Smoke values must not remain in application code or public HTML fixtures.

## Post-Staging Checks

After selective staging:

```bash
git diff --cached --name-only
git diff --cached --stat
git diff --cached --check
```

Then run:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php
php artisan test tests/Feature/Commands/SyncIhaNewsCommandTest.php tests/Feature/Jobs/SyncIhaNewsJobTest.php tests/Feature/Commands/MonitorIhaForwardIngestCommandTest.php tests/Unit/Services/IhaSyncTriggerServiceTest.php
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

## Recommendation

Use the full snapshot route unless the upstream target branch is proven to already contain
the complete ADH application baseline.

Do not create a commit until:

- the pre-staging scans pass,
- the exact staged file list is reviewed,
- and generated/local artifacts are confirmed absent.
