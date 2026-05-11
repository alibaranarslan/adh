# ADH IHA Recovery Plan

Date: 2026-04-16
Status: pre-change plan
Plan type: `command patch + rerun`

## Recovery Objective

Restore IHA-backed news into the active ADH DB without broad refactor, while keeping current/live ingestion safe, deduped, and operator-rerunnable.

## Confirmed Constraints

- Minimal safe changes only.
- No data deletion.
- No dedup bypass.
- No source-discipline bypass for IHA.
- Do not break readonly behavior for IHA-sourced records in admin.
- Use the DB context that actually serves ADH public.

## Proven Recovery Scope

Proven recoverable right now:

- Re-ingest the currently available upstream IHA feed window into the active ADH DB.
- Preserve live ongoing sync by running the same ingestion inline instead of queue-only.

Not yet proven:

- Historical recovery older than the recent upstream window.
- Any restore from remote MySQL or backup media.

These remain `UNVERIFIED` until a concrete older source is demonstrated.

## Planned Minimal Changes

1. Patch `iha:sync` / `SyncIhaNewsJob` to support a bounded `--limit` option.
2. Patch scheduled sync in `routes/console.php` to call `iha:sync --inline`.
3. Patch admin manual sync actions to call `iha:sync --inline` so operator-triggered recovery does not enqueue work into an unconsumed queue.
4. Keep:
   - `iha_id` unique dedup logic
   - category mapping
   - locality / `city_slug` mapping
   - `status='published'` and `published_at` behavior
   - translation dispatch behavior
   - IHA admin readonly behavior

No new schema change is planned.
No new architecture is planned.
No unrelated RG work is planned.

## Historical IHA Restoration Strategy

1. Run a bounded inline sync first against the active DB:
   - small sample size via `--limit`
   - verify created/updated/skipped counts
   - inspect resulting rows and public visibility
2. If bounded run is healthy, rerun inline without the small bound to ingest the full currently available feed window.
3. Re-run the same command once more to verify idempotency:
   - expected behavior is mostly `updated` / `skipped`
   - no duplicate rows
4. If older-than-window history is still required, stop and escalate with evidence because older upstream pagination/range support is not yet proven.

## How Current / Live Ingestion Will Be Preserved

1. Scheduled sync will run inline instead of queue-first.
2. Manual admin sync will run inline instead of queue-first.
3. This keeps the same job logic, same dedup logic, and same write path, but removes dependency on a queue worker for IHA creation.
4. Translation jobs may still queue in the background. That is acceptable because Turkish public visibility does not depend on translation completion.

## Dedup / Upsert Safety

Safety basis:

- `news_articles.iha_id` is unique in schema.
- Sync logic looks up existing rows by `iha_id`.
- Existing rows are updated in place.
- New slugs are generated uniquely.

Verification during recovery:

- compare `COUNT(*)` and `COUNT(DISTINCT iha_id)` after each pass
- sample latest IHA ids after rerun
- confirm second run produces no duplicate storm

## Duplicate Public Item Avoidance

Duplicate avoidance will rely on the existing dedup path, not on post-facto cleanup.

Checks after each run:

1. `COUNT(*) WHERE source='iha'`
2. `COUNT(DISTINCT iha_id) WHERE source='iha' AND iha_id IS NOT NULL`
3. duplicates query on `GROUP BY iha_id HAVING COUNT(*) > 1`
4. quick public list sample for repeated titles / repeated detail targets

## Category / City Verification After Recovery

Sample checks after bounded run:

1. Inspect latest IHA rows with:
   - `iha_id`
   - `title`
   - `category_id`
   - category slug
   - `city_code`
   - `city_slug`
2. Confirm sample distribution across:
   - mapped category slugs
   - locality scores (`1`, `2`, `3`)
   - non-null `city_slug` where city is inferable
3. Verify:
   - homepage local module is no longer empty if local rows are present
   - category pages show mapped rows
   - city pages show rows where `city_slug` was detected

## Image Verification After Recovery

Default expectation:

- newly created IHA rows should receive images during ingest when `image_url` is present.

Checks:

1. count IHA rows with null/empty `featured_image`
2. sample 5 newest created IHA rows for image presence
3. sample public detail pages for image rendering

If image gaps are discovered:

- do **not** bulk-run the current `iha:refresh-images` blindly
- either:
  - skip if public visibility is already acceptable, or
  - run a small controlled follow-up only after confirming scope

## Public Listing Verification After Recovery

After bounded run and again after full run:

1. homepage must show more than one news item
2. `/kategori/{slug}` must show newly ingested content for a mapped category
3. representative detail pages must resolve
4. if `city_slug` rows exist, `/iller/{slug}` or equivalent city route must resolve those rows

## Rollback Considerations

Because this is an additive dedup-safe replay, preferred rollback is stop-and-investigate, not destructive cleanup.

If recovery behaves unexpectedly:

1. stop after the bounded pass
2. do not continue to full replay
3. preserve the `iha_sync_logs` evidence
4. preserve DB counts and sample row ids/slugs
5. if a code patch caused the issue, revert only the minimal patch

No rollback plan includes deleting historical news rows without explicit proof and logging.

## Recovery Classification

Recovery is planned as:

- `command patch + rerun`

Specifically:

- patch existing command/job to allow bounded safe replay
- patch sync entrypoints to inline execution
- rerun existing ingestion logic in a controlled sequence

No one-off destructive repair is planned.
No broad query redesign is planned.

## Operator-Safe Execution Order

### 1. Preflight

1. Confirm active DB target.
2. Confirm current counts.
3. Confirm IHA credentials present.
4. Confirm feed reachability.

### 2. Deploy minimal patch

1. add `--limit`
2. switch schedule/admin sync to inline
3. run targeted tests if available

### 3. Controlled first pass

1. run `iha:sync --inline --limit=<small number>` against the active DB
2. record start/end time and command output
3. inspect counts and sample rows
4. inspect public home/category/detail pages

### 4. Full recent-window replay

1. run `iha:sync --inline` against the same DB
2. record created/updated/skipped/failed
3. inspect public results again

### 5. Idempotency check

1. rerun `iha:sync --inline`
2. verify no duplicate storm

### 6. Optional image follow-up

Only if post-sync evidence shows meaningful missing-image impact.

## Planned Operator Commands

These commands are planned, not yet executed in this document.
They intentionally pin the known-working SQLite DB in this local environment.

PowerShell pattern:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan <command>
```

Planned bounded recovery command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:sync --inline --limit=10
```

Planned full replay command:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\nwp0203\haber-sitesi\database\database.sqlite'
$env:QUEUE_CONNECTION='database'
php artisan iha:sync --inline
```

## Stop Conditions

Stop and investigate if any of the following happens during the bounded pass:

1. unexpected duplicate creation
2. repeated sync failures
3. category or city assignments are systematically null/incorrect
4. created rows are not publicly visible despite `status='published'` and valid `published_at`
5. image handling causes a noticeable error spike
6. runtime points to a different DB than the one being recovered
