# ADH IHA Local Production Model Recheck - 2026-05-13

## Summary

IHA production-model code readiness: PASS with one local runtime warning.

The scheduler uses queued `iha:sync`, monitor quality signals work, and the monitor now warns when a `running` sync is older than the short grace window.

## Code Changes In This Batch

- `iha:monitor-forward` now includes `running_age_minutes`.
- A `running` sync older than 30 minutes reports `health=warn`.
- A `running` sync older than 120 minutes reports `health=critical`.
- `deploy:verify` now checks `/robots.txt` and `/sitemap.xml`, so a missing generated sitemap fails deploy verification.

## Command Evidence

```bash
php artisan schedule:list
```

Result:

- `php artisan iha:sync` is scheduled every 15 minutes.
- `iha:sync --inline` is not scheduled.
- `php artisan sitemap:generate` is scheduled daily at `04:00`.

```bash
php artisan iha:monitor-forward --limit=20
```

Result:

```text
IHA_FORWARD_MONITOR health=warn sync_status=running running_age_minutes=81 quality_risk=no quality_affected=0 freshness_minutes=81 fetched=0 created=0 updated=0 skipped=0 window=20 empty_content=0 weak_body=0 short_body=0 body_depth_ratio=1.00 generic_source_url_ratio=0.95
```

Interpretation:

- Content quality window is healthy: empty/weak/short body counts are all `0`.
- Local runtime status is `warn` because an old local `running` sync exists and local worker state is not production evidence.
- This is no longer incorrectly reported as `healthy`.

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Result:

- All checks passed.
- `robots.txt` check passed.
- `sitemap.xml` check passed.

## Test Evidence

```bash
php artisan test tests/Feature/Commands/SyncIhaNewsCommandTest.php tests/Feature/Commands/MonitorIhaForwardIngestCommandTest.php tests/Feature/Jobs/IhaSyncLogStatusTest.php tests/Feature/Commands/EnrichIhaSourceUrlsCommandTest.php
```

Included in the wide suite and passed.

Monitor-specific rerun:

- `4 passed`, `17 assertions`.

## Decision

IHA code and local quality monitoring are acceptable for predeploy.

Production IHA readiness remains BLOCKED until Hetzner proves:

- Linux cron runs `schedule:run`.
- Supervisor/systemd worker consumes `default,analytics,instagram`.
- Controlled queued IHA sync completes.
- `iha:monitor-forward --limit=20` shows no unexplained running/stale state.
- Latest public IHA details show body text.
