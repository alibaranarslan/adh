# ADH Release Branch Final Verification - 2026-05-12

## Scope

Final local verification for branch `codex/adh-local-predeploy-package` before push/PR or
Hetzner production go/no-go work.

## Git State

Result:

- PASS
- Working tree was clean before this report was added.
- Staged area was empty before this report was added.

Latest commits at verification time:

- `042045e0 Archive legacy audit reports`
- `10e65c70 Ignore generated public assets`
- `94eb13b2 Add required public image assets`
- `bd1279b3 Prepare ADH local predeploy release snapshot`

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

## Deploy Verify

Command:

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Result:

- PASS
- App boots.
- App URL configured.
- Production debug guard passed.
- Database connection OK.
- Log directory writable.
- Cache read/write OK.
- Queue configuration OK.
- Session cookie security OK.
- `/health` responds.
- Homepage responds.

## Scheduler

Command:

```bash
php artisan schedule:list
```

Result:

- PASS
- `php artisan iha:sync` appears on the schedule.
- `iha:sync` does not use `--inline`.
- Default Laravel `inspire` command is absent.

Scheduled production-relevant commands:

- `php artisan iha:sync`
- `php artisan iha:monitor-forward --limit=20`
- `php artisan iha:refresh-images`
- `php artisan news:archive`
- `php artisan pharmacy:refresh`
- `php artisan prayer:refresh`
- `php artisan weather:refresh`
- `php artisan editorial:recalculate`
- `php artisan sitemap:generate`

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

- PASS for content quality signals.
- `health=healthy`
- `quality_risk=no`
- `quality_affected=0`
- `empty_content=0`
- `weak_body=0`
- `short_body=0`
- `body_depth_ratio=1.00`

Local caveat:

- Last sync was `running` with `freshness_minutes=738`.
- This is not production evidence because the local machine is not the intended queue/cron
  host.
- Hetzner must prove cron and queue worker operation before production ready approval.

## Decision

Local release branch verification: PASS with production caveat

Production go/no-go: BLOCKED until Hetzner access and server evidence are available.

## Next Step

Push branch `codex/adh-local-predeploy-package` or keep it local until Hetzner access is
available.
