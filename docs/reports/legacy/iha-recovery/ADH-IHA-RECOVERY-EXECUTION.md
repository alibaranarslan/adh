# ADH IHA Recovery Execution

Date: 2026-04-16
Environment: `C:\nwp0203\haber-sitesi`
DB target used for recovery: `C:\nwp0203\haber-sitesi\database\database.sqlite`

## Pre-execution Validation

Code validation completed before recovery:

- `php -l app/Console/Commands/SyncIhaNewsCommand.php`
- `php -l app/Jobs/SyncIhaNewsJob.php`
- `php -l app/Console/Commands/RefreshIhaImagesCommand.php`
- `php -l app/Filament/Pages/IhaHealth.php`
- `php -l app/Filament/Resources/IhaSyncLogResource.php`
- `php -l tests/Feature/Jobs/SyncIhaNewsLimitTest.php`

Targeted tests completed:

```text
PASS Tests\Feature\Jobs\SyncIhaNewsJobTest
PASS Tests\Feature\Jobs\IhaSyncLogStatusTest
PASS Tests\Feature\Jobs\SyncIhaNewsLimitTest
Tests: 5 passed (41 assertions)
```

## Execution Environment

All recovery commands were explicitly pinned to the proven working SQLite DB:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
```

## Run 1: Bounded First Pass

Purpose:

- verify that inline sync creates IHA rows in the active DB
- verify category/city/image assignments on a small sample
- verify public ADH stops showing only one item

Exact command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:sync --inline --limit=10
```

Timing:

- start: `2026-04-16T15:53:39.2104344+03:00`
- end: `2026-04-16T15:53:59.1597745+03:00`

Observed command output:

```text
IHA sync tamamlandi. Log ID: 1 | Durum: success | Cekilen: 10 | Yeni: 10 | Guncellenen: 0 | Atlanan: 0
```

Post-run evidence:

- `iha_sync_logs.id=1` status `success`
- `articles_fetched=10`
- `articles_created=10`
- `articles_updated=0`
- `articles_skipped=0`
- public home immediately exposed multiple IHA detail links instead of one total article link

## Run 2: Full Recent-Window Replay

Purpose:

- recover the full currently available IHA feed window into the active DB

Exact command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:sync --inline
```

Timing:

- start: `2026-04-16T15:54:40.7384994+03:00`
- end: `2026-04-16T15:56:30.6063173+03:00`

Observed command output:

```text
IHA sync tamamlandi. Log ID: 2 | Durum: success | Cekilen: 61 | Yeni: 51 | Guncellenen: 0 | Atlanan: 10
```

Interpretation:

- the 10 bounded-pass items were encountered again and safely skipped
- 51 additional IHA rows were created
- no duplicate storm was observed

Log evidence:

```text
[2026-04-16 15:56:30] local.INFO: IHA sync tamamlandı {"articles_fetched":61,"articles_created":51,"articles_updated":0,"articles_skipped":10,"images_downloaded":43}
```

## Run 3: Idempotency / Fresh-Flow Recheck

Purpose:

- verify that a fresh post-backfill run remains safe
- verify reruns are dedup-safe and do not create duplicate public items

Exact command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:sync --inline
```

Timing:

- start: `2026-04-16T15:56:34.7974368+03:00`
- end: `2026-04-16T15:57:37.7960798+03:00`

Observed command output:

```text
IHA sync tamamlandi. Log ID: 3 | Durum: success | Cekilen: 61 | Yeni: 0 | Guncellenen: 0 | Atlanan: 61
```

Interpretation:

- rerun was fully dedup-safe in the observed window
- no duplicate inserts were created
- current inline IHA flow remained functional after backfill

Log evidence:

```text
[2026-04-16 15:57:37] local.INFO: IHA sync tamamlandı {"articles_fetched":61,"articles_created":0,"articles_updated":0,"articles_skipped":61,"images_downloaded":0}
```

## Run 4: Optional Image Follow-up

Reason for follow-up:

- after full replay, `61` IHA rows existed
- `53` had `featured_image`
- `8` still had missing `featured_image`

Before running this follow-up, `iha:refresh-images` was patched to:

- use one fresh feed snapshot instead of refetching once per article
- match by `iha_id`
- support bounded `--limit`
- stop misusing the `city_code` locality score as an IHA city filter

Exact command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:refresh-images --hours=24 --limit=10
```

Timing:

- start: `2026-04-16T15:59:50.1331802+03:00`
- end: `2026-04-16T15:59:52.1635643+03:00`

Observed command output:

```text
IHA gorsel yenileme tamamlandi. Aday: 8 | Yenilenen: 0 | Atlanan: 8 | Basarisiz: 0
```

Interpretation:

- no additional image could be recovered from the current feed snapshot
- image refresh completed safely, without provider hammering or failures

Log evidence:

```text
[2026-04-16 15:59:52] local.INFO: IHA gorsel yenileme tamamlandi {"candidates":8,"refreshed":0,"skipped":8,"failed":0}
```

## Recovery Totals

Net data recovery achieved in this execution:

- pre-recovery IHA rows: `0`
- post-recovery IHA rows: `61`
- total news rows after recovery: `63`
- published rows after recovery: `62`
- archived rows after recovery: `1`

Recovered IHA feed window observed in DB:

- oldest recovered IHA `published_at`: `2026-04-15 16:29:58`
- newest recovered IHA `published_at`: `2026-04-16 15:43:41`

## Warnings / Non-blocking Findings

1. Translation jobs accumulated in queue after IHA creation.
   - This was expected and did not block Turkish public visibility.
2. `8` recovered IHA rows still lack `featured_image`.
   - The patched image refresh command confirmed no current-feed image was recoverable for those rows.
3. No evidence was obtained that upstream page parameters provide older-than-window history.
   - Historical recovery beyond the observed feed window remains `UNVERIFIED`.
