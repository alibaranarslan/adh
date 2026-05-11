# ADH Pre-Staging Verification - 2026-05-11

## Purpose

Record the final local verification run before any staging or commit action.

No files were staged while preparing this report.

## Branch

- `codex/adh-local-predeploy-package`

## Test Results

### Public, advertisement, and media hardening

Command:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php
```

Result:

- PASS
- 26 tests passed
- 181 assertions

### IHA sync, job, monitor, and trigger service

Command:

```bash
php artisan test tests/Feature/Commands/SyncIhaNewsCommandTest.php tests/Feature/Jobs/SyncIhaNewsJobTest.php tests/Feature/Commands/MonitorIhaForwardIngestCommandTest.php tests/Unit/Services/IhaSyncTriggerServiceTest.php
```

Result:

- PASS
- 11 tests passed
- 71 assertions

## Scheduler Verification

Command:

```bash
php artisan schedule:list
```

Result:

- PASS
- `php artisan iha:sync` is scheduled every 15 minutes.
- `iha:sync` does not include `--inline`.
- Default Laravel `inspire` schedule is absent.

Observed scheduled commands:

- `php artisan iha:sync`
- `php artisan iha:monitor-forward --limit=20`
- `php artisan iha:refresh-images`
- `php artisan news:archive`
- `php artisan pharmacy:refresh`
- `php artisan prayer:refresh`
- `php artisan weather:refresh`
- `php artisan editorial:recalculate`
- `php artisan sitemap:generate`

## Deploy Verification

Command:

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Result:

- PASS
- App boots.
- App URL configured.
- Production debug guard passed for local verification command.
- Database connection OK.
- Log directory writable.
- Cache read/write OK.
- Queue configuration OK.
- Session cookie security OK.
- `/health` responds.
- Homepage responds.

## Queue Failed Jobs

Command:

```bash
php artisan queue:failed
```

Result:

- PASS
- No failed jobs found.

## IHA Forward Monitor

Command:

```bash
php artisan iha:monitor-forward --limit=20
```

Result:

- PASS for quality signals.
- `health=healthy`
- `quality_risk=no`
- `quality_affected=0`
- `empty_content=0`
- `weak_body=0`
- `short_body=0`
- `body_depth_ratio=1.00`

Local caveat:

- Last sync was `running` with `freshness_minutes=108`.
- This is acceptable only as local pre-staging context because this machine is not the
  production queue host.
- Production readiness still requires Hetzner cron and queue worker proof.

## Decision

Local pre-staging verification status: PASS with production caveat

Production readiness status: BLOCKED until Hetzner evidence is collected.

## Recommended Next Step

If the user approves staging, use Route A from
`docs/reports/ADH-STAGING-COMMAND-PLAN-2026-05-11.md`, then inspect the staged diff before
any commit.
