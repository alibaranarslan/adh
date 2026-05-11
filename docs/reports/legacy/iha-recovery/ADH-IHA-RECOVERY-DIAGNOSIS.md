# ADH IHA Recovery Diagnosis

Date: 2026-04-16
Environment inspected: local ADH runtime at `C:\nwp0203\haber-sitesi`
Status: pre-fix diagnosis

## Executive Summary

Confirmed blocker behind "ADH public shows only one news item":

1. The active local ADH data store currently contains only `2` `news_articles` rows total.
2. Only `1` row is `status='published'`.
3. There are `0` IHA-sourced rows in `news_articles`, including soft-deleted rows.
4. Public ADH is therefore rendering the only published row that exists; this is not primarily caused by a hidden public query bug collapsing many IHA rows into one.
5. The current IHA sync path is queue-first by default. In this environment:
   - `QUEUE_CONNECTION=database` is active at runtime.
   - no resident `queue:work` / `queue:listen` worker process was found;
   - scheduled and admin-triggered sync both call `iha:sync` without `--inline`;
   - `iha_sync_logs` in the active SQLite snapshot are empty, so there is no evidence of completed IHA sync runs in the DB currently serving public ADH.
6. The upstream IHA feed is reachable and returns recent items, so the source is not globally down.

Net: the immediate production-critical issue is missing IHA data in the active DB, combined with a sync execution path that depends on queue processing which is not evidenced as running here.

## Relevant Code Paths

### IHA fetch/import

- `app/Console/Commands/SyncIhaNewsCommand.php`
- `app/Jobs/SyncIhaNewsJob.php`
- `app/Services/IhaApiService.php`
- `routes/console.php`

Key findings:

- `iha:sync` creates an `iha_sync_logs` row with `status='running'`.
- Unless `--inline` is supplied or `queue.default === 'sync'`, the command dispatches `SyncIhaNewsJob` to the queue and returns immediately.
- Scheduler currently runs `Schedule::command('iha:sync')->cron('*/15 * * * *')`.
- Admin actions in both `IhaHealth` and `IhaSyncLogResource` call `Artisan::call('iha:sync')`, also without `--inline`.

### Deduplication / upsert

- `app/Jobs/SyncIhaNewsJob.php`
- `database/migrations/2026_03_02_000004_create_news_articles_table.php`

Key findings:

- Dedup key is `news_articles.iha_id`.
- `news_articles.iha_id` is declared `unique()`.
- Existing IHA records are updated in place via `NewsArticle::where('iha_id', $ihaId)->first()`.
- New records get a unique slug via `generateUniqueSlug()`.
- No evidence was found that dedup currently collapses multiple unrelated IHA items into one row.

### Category mapping

- `app/Services/IhaCategoryMapper.php`

Key findings:

- Mapping prefers category-name-to-slug mapping first (`IHA_CATEGORY_MAP`), then locality heuristics, then optional settings/category-code mapping.
- Local categories present in the SQLite snapshot match the expected slugs:
  `gundem`, `siyaset`, `ekonomi`, `spor`, `egitim`, `saglik`, `kultur-sanat`, `teknoloji`, `yasam`, `magazin`, `asayis`.
- `categories.iha_category_code` is null for all inspected categories; current mapping relies on name/slugs, not numeric IHA codes.

### City / local mapping

- `app/Services/IhaCategoryMapper.php`
- `database/migrations/2026_03_03_152551_add_city_slug_to_news_articles_table.php`

Key findings:

- Locality scoring uses:
  - `3` for Adiyaman/local
  - `2` for region
  - `1` for national/other
- `city_slug` is inferred from IHA city name or title keywords.
- Home page modules use `city_code=3` for local news and `city_code=2` for region news.

### Translation enqueueing

- `app/Jobs/TranslateArticleJob.php`
- `app/Observers/NewsArticleObserver.php`
- `app/Filament/Pages/IhaHealth.php`

Key findings:

- `SyncIhaNewsJob` explicitly dispatches `TranslateArticleJob` after create/update.
- `NewsArticleObserver` does **not** auto-dispatch translations for `source='iha'`; this preserves source discipline.
- Translation backlog does not block public visibility unless downstream UI explicitly depends on translated locales. For Turkish public visibility, this was not proven as a blocker.

### Image fetch / refresh

- `app/Services/IhaImageService.php`
- `app/Console/Commands/RefreshIhaImagesCommand.php`

Key findings:

- Create-path image handling is direct: if `image_url` exists, `SyncIhaNewsJob` downloads the image during article creation.
- Update-path only fetches an image when `featured_image` is empty.
- `iha:refresh-images` is potentially expensive because it re-fetches the IHA feed once per candidate article. Given upstream throttle behavior, this is unsafe for large bulk runs.

### Publish / status / archive logic

- `app/Models/NewsArticle.php`
- `app/Console/Commands/ArchiveOldNewsCommand.php`
- `app/Http/Controllers/ArchiveController.php`

Key findings:

- IHA-created rows are inserted with `status='published'` and `published_at` from feed or `now()`.
- `published()` scope: `status='published'` and `published_at IS NULL OR published_at <= now()`.
- `publiclyAccessible()` scope includes `status IN ('published','archived')` with the same `published_at` rule.
- `news:archive` archives only `published` articles older than configured threshold (default 90 days).
- Current local DB has only one archived article and it is manual, not IHA.

### Public news listing queries

- `app/Services/HomeModuleDataService.php`
- `app/Http/Controllers/HomePageController.php`
- `app/Http/Controllers/NewsController.php`
- `app/Http/Controllers/CityController.php`
- `app/Http/Controllers/ArchiveController.php`

Key findings:

- Homepage modules all read from `NewsArticle::published()`.
- Some homepage slots additionally require `featured_image`, but not the whole listing.
- Category pages use `NewsArticle::published()->where('category_id', ...)`.
- City pages use `NewsArticle::published()->where('city_slug', ...)`.
- Detail pages use `NewsArticle::publiclyAccessible()->where('slug', ...)`.
- No code path inspected here explains "only one visible item" if many published IHA rows existed.

### Admin health / freshness

- `app/Filament/Pages/IhaHealth.php`
- `app/Filament/Resources/IhaSyncLogResource.php`
- `app/Filament/Widgets/StatsOverviewWidget.php`
- `app/Filament/Widgets/SystemAlertsWidget.php`
- `app/Services/AdhControlCenterService.php`

Key findings:

- Freshness and dashboard health depend on `iha_sync_logs`.
- With empty `iha_sync_logs`, the health view necessarily shows no successful sync history.
- Manual sync actions currently trigger the queue-first command path.

## DB Findings

### Connection state

Confirmed:

- CLI using the default `.env` MySQL settings fails with `SQLSTATE[HY000] [2002]` connection refusal.
- The already-running ADH web server at `http://127.0.0.1:8000` responds successfully and matches the SQLite snapshot content in `database/database.sqlite`.

Implication:

- Recovery commands must be run against the actual DB serving ADH public.
- In this local environment, the SQLite file is the only proven working DB target.
- Any claim about the MySQL dataset is `UNVERIFIED`.

### Counts from `database/database.sqlite`

Confirmed via direct SQLite inspection:

- `news_articles` total rows: `2`
- `published()` scope rows: `1`
- `publiclyAccessible()` scope rows: `2`
- `status='published'`: `1`
- `status='archived'`: `1`
- `source='iha'`: `0`
- `source='manuel'`: `2`
- `published_at IS NULL`: `0`
- `published_at > now()`: `0`
- missing category on current rows: `0`
- soft-deleted rows: `0`
- soft-deleted IHA rows: `0`
- duplicate `iha_id` rows: none
- duplicate `source_url` rows: none
- latest `created_at`: `2026-04-15 13:24:06`
- latest `published_at`: `2026-04-15 15:59:15`
- oldest `published_at`: `2026-04-05 16:59:15`
- `iha_sync_logs` rows: `0`
- queued jobs count in `jobs`: `462`
- queued sync jobs in `jobs`: `0`
- queued translation jobs in `jobs`: `0`
- queued analytics jobs in `jobs`: `462`

### Public rendering evidence

Confirmed via HTTP inspection of the running site:

- homepage exposed only the single published article link:
  `runtime-adh-yayin-haberi`
- category page `/kategori/gundem` exposed that same article
- archive page `/arsiv` exposed the archived manual article

Therefore the public behavior matches DB state exactly.

## Runtime / Process Findings

Confirmed:

- ADH PHP built-in server is running on `127.0.0.1:8000`.
- `GET /health` returned:
  - `status: ok`
  - `queue_driver: database`
  - `queue_table_present: true`
- No resident PHP process matching `queue:work`, `queue:listen`, `schedule:work`, or `schedule:run` was found during inspection.

Implication:

- The default queued sync path has no proven consumer in this environment.
- Even if scheduler invocations exist outside the inspected process list, there is still no successful sync evidence in the active SQLite DB.

## Upstream IHA Feed Findings

Confirmed:

- The IHA feed is reachable and returns current items.
- A probe against the live feed returned `62-63` items in a recent window spanning approximately:
  - newest observed item: `2026-04-16 15:43:41`
  - oldest observed item: `2026-04-15 15:47:28`
- Upstream throttle behavior requires approximately `30` seconds between RSS requests; requests made too quickly return a throttle message.

### Historical pagination / range behavior

Observed:

- `wp=0` and `wp=1`, separated by `35` seconds, returned nearly the same recent window.
- The first item was identical on both pages.
- Only the final item differed slightly.

Conclusion:

- True multi-page historical traversal via `wp` is **UNVERIFIED**.
- No local backup, dump, or alternate DB copy containing historical IHA article rows was found in the repository.

## Confirmed Root Causes

1. **The active ADH DB does not currently contain IHA news rows.**
   - Evidence: `source='iha'` count is `0` in the SQLite DB that matches public output.

2. **Public ADH shows one item because only one published row exists.**
   - Evidence: homepage/category output matches `published()` count of `1`.

3. **The current sync entrypoints are queue-first, but no queue worker is evidenced as running here.**
   - Evidence:
     - command/scheduler/admin code paths dispatch queued jobs unless `--inline`;
     - runtime queue driver is `database`;
     - no queue worker process was found;
     - no sync jobs are present in the DB queue;
     - `iha_sync_logs` is empty.

4. **The upstream source is available, so total outage of IHA feed is not the reason for zero public IHA articles.**
   - Evidence: direct feed probe returned recent items successfully.

## Unverified Hypotheses

- Whether another non-inspected environment or DB previously contained older IHA rows.
- Whether `wp` or another upstream parameter can access materially older IHA history beyond the observed recent window.
- Whether an external OS scheduler exists but is targeting a different DB or environment.
- Whether any remote MySQL dataset still contains historical IHA rows. Local CLI could not reach MySQL, so this is `UNVERIFIED`.

## Exact Blocker(s) Causing "Only One News Item Visible"

Primary confirmed blockers:

1. `news_articles` in the active DB contains only one published row.
2. There are zero IHA rows in that DB.
3. The IHA sync path depends on queued execution by default, but a working queue consumer is not evidenced in the inspected environment.

Secondary operational blockers:

4. Default CLI DB target in `.env` points to unreachable MySQL, so operator commands run naively from shell will fail or target the wrong dataset.
5. Existing `iha:refresh-images` design is not safe for bulk historical remediation because it refetches the throttled feed per article.

## Recommended Minimal Recovery Strategy

1. **Do not broad-refactor.**
   Keep the existing sync job, dedup key, category mapper, city mapper, publish logic, and readonly IHA behavior.

2. **Patch the sync execution surface, not the ingestion model.**
   - Keep `SyncIhaNewsJob` as the writer.
   - Make ongoing scheduled/admin-triggered sync use inline execution in this environment so IHA creation does not depend on an absent queue worker.

3. **Add a bounded first-pass control.**
   - Add a `--limit` option to `iha:sync` / `SyncIhaNewsJob` so operators can run a small idempotent trial before full replay.

4. **Use the existing `iha_id` dedup path for backfill.**
   - No deletes.
   - No bypass of unique `iha_id`.
   - Re-runs should result in `updated` / `skipped`, not duplicate inserts.

5. **Recover the proven feed window first.**
   - The upstream feed has been proven to supply a recent window only.
   - Any claim of older historical recovery beyond that must remain `UNVERIFIED` until another upstream-supported range mechanism or DB backup is proven.

6. **Run recovery against the active DB context, not the unreachable default MySQL shell target.**
   - In this local environment, that means explicit SQLite-targeted operator commands.

7. **Treat translation as non-blocking and image refresh as separate.**
   - Translation backlog should be recorded but should not block public visibility.
   - Separate image refresh should only be run if post-sync sampling proves it necessary.
