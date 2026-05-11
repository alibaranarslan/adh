# FINAL-RELEASE-GATE

## ADH IHA Recovery Addendum

### Root Cause

- The active ADH DB serving public output contained one published manual article and zero IHA rows.
- IHA sync was queue-first by default.
- No working queue worker was evidenced in the inspected environment.
- Scheduled and admin-triggered sync therefore had no proven path to populate IHA news in the active DB.

### Fix Summary

- Added bounded `--limit` support to the existing `iha:sync` path.
- Switched scheduler to `php artisan iha:sync --inline`.
- Switched admin manual sync actions to inline execution.
- Patched `iha:refresh-images` to use a single fresh feed snapshot keyed by `iha_id`, avoiding per-article throttled fetches and misuse of the locality score field.

### Commands Used

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'

php artisan iha:sync --inline --limit=10
php artisan iha:sync --inline
php artisan iha:sync --inline
php artisan iha:refresh-images --hours=24 --limit=10
php artisan schedule:list
```

### Recovered Historical Scope

- Recovered and verified current upstream feed window:
  - oldest recovered IHA `published_at`: `2026-04-15 16:29:58`
  - newest recovered IHA `published_at`: `2026-04-16 15:43:41`
- Recovery older than this window remains `UNVERIFIED`.

### Proof Current Flow Still Works

- latest sync log (`id=3`) status: `success`
- latest sync run: fetched `61`, created `0`, updated `0`, skipped `61`
- scheduler lists `php artisan iha:sync --inline`
- `IhaHealth` freshness state resolves to `healthy`

### Remaining Risks

1. Historical recovery beyond the current feed window is still unproven.
2. Eight recovered IHA rows still have no image.
3. Translation backlog increased after replay, though Turkish public visibility is restored.
