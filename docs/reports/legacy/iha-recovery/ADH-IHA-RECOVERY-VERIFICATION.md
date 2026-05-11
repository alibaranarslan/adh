# ADH IHA Recovery Verification

Date: 2026-04-16
Verification basis: app-scoped queries against the recovered SQLite DB, public HTTP responses from `http://127.0.0.1:8000`, sync log records, and scheduler inspection.

## Proven Fixed

### 1. Public ADH no longer shows only one item

Confirmed:

- pre-recovery public state had one published article
- post-recovery app-scoped counts are:
  - `total_news = 63`
  - `published_scope_count = 62`
  - `publicly_accessible_count = 63`
  - `iha_published = 61`
- homepage returned `200` and exposed `11` unique article detail links in the sampled link set

### 2. IHA history in the currently available feed window was restored

Confirmed:

- `source='iha'` count moved from `0` to `61`
- recovered window in DB spans:
  - oldest recovered IHA `published_at`: `2026-04-15 16:29:58`
  - newest recovered IHA `published_at`: `2026-04-16 15:43:41`

Representative older recovered rows:

- `20260415AW684288` `desob-baskani-ebedinoglu-diyarbakir-kuyumcular-ve-sarraflar-odasini-ziyaret-etti`
- `20260415AW684289` `sirnakta-hafriyat-sahasinda-toprak-kaymasi-park-halindeki-2-arac-sarampole-yuvarlandi`

### 3. Deduplication remained safe

Confirmed:

- second full rerun created `0` new rows and skipped `61`
- duplicate query on `iha_id` returned no duplicates
- final three sync logs:
  - log `1`: fetched `10`, created `10`, skipped `0`
  - log `2`: fetched `61`, created `51`, skipped `10`
  - log `3`: fetched `61`, created `0`, skipped `61`

### 4. Category mapping remained functional

Confirmed category distribution on recovered IHA rows:

- `asayis`: `36`
- `gundem`: `12`
- `yasam`: `5`
- `egitim`: `3`
- `spor`: `3`
- `ekonomi`: `1`
- `saglik`: `1`

Public category verification:

- `/kategori/gundem` returned `200` and exposed multiple recovered IHA detail links
- `/kategori/asayis` returned `200` and exposed multiple recovered IHA detail links

### 5. City / locality mapping remained functional

Confirmed:

- `iha_local_count` (`city_code=3`): `8`
- `iha_region_count` (`city_code=2`): `53`
- `iha_national_count` (`city_code=1`): `0`
- `iha_missing_city_slug`: `0`

Observed `city_slug` distribution includes:

- `diyarbakir`: `19`
- `gaziantep`: `14`
- `batman`: `7`
- `adiyaman`: `5`
- `sirnak`: `5`

Public city verification:

- `/il/gaziantep` returned `200` and exposed multiple recovered IHA detail links
- `/il/adiyaman` returned `200` and exposed `5` recovered detail links in the sample

### 6. Representative article detail pages work

Confirmed:

- `/dtsodan-diyarbekirspora-destek-ziyareti?locale=tr` returned `200`
- response included the expected title and a `/storage/news-images/2026/04/` image path
- `/adiyamandaki-okul-onlerinde-siki-denetim?locale=tr` returned `200`
- response included an image path

### 7. Admin health/freshness reflects restored flow

Confirmed by executing `App\Filament\Pages\IhaHealth::getViewData()` against the recovered DB:

- `effective_interval = "15 dakika"`
- `freshness_lag_minutes ≈ 1.39`
- `freshness_state = "healthy"`
- `recent_logs_count = 3`
- `latest_log_status = "success"`

### 8. Ongoing/current flow still works after backfill

Confirmed:

- a fresh post-backfill sync run completed successfully
- it fetched `61`, created `0`, updated `0`, skipped `61`
- scheduler now lists `php artisan iha:sync --inline`

Scheduler evidence:

```text
*/15 * * * *  php artisan iha:sync --inline
```

## Improved But Unverified

1. Recovery older than the current upstream feed window.
   - Current execution recovered the proven recent window only.
   - Older historical traversal remains `UNVERIFIED`.

2. Direct browser verification of the authenticated `/admin/iha-health` page.
   - Health data itself is proven through the page class and `iha_sync_logs`.
   - Manual browser login verification was not performed here.

3. Remote MySQL parity.
   - Local shell MySQL target was unreachable.
   - Recovery and verification were executed against the DB proven to serve the current local ADH runtime.

## Still Open

1. `8` IHA rows still have no `featured_image`.
   - The patched single-feed `iha:refresh-images` run safely skipped all 8.
   - Current evidence suggests no recoverable image was present for them in the current feed snapshot.

2. Translation backlog increased after IHA replay.
   - This is not a blocker for Turkish public visibility.
   - It should be monitored operationally if EN/KU freshness matters.

## Verification Notes

### Important timezone note

Raw SQLite `datetime('now')` comparisons are UTC-based and can misclassify same-day local timestamps as "future". Final verification numbers in this document use Laravel app-scoped queries, which match the actual public visibility rules used by ADH.
