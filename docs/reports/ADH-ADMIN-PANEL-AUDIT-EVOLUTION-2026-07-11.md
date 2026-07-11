# ADH Admin Panel Audit and Evolution Report - 2026-07-11

## Scope

Goal: inspect the ADH admin panel page by page, identify whether each admin surface has a direct public-site effect, fix concrete admin/public inconsistencies, and keep a backlog for the next evolution pass.

Environment: local ADH app at `http://127.0.0.1:8000`.

Admin account used for local verification: `admin@admin.com`.

## Admin Surfaces Audited

Browser smoke covered the following admin URLs:

- `/admin`
- `/admin/news-articles`
- `/admin/news-articles/create`
- `/admin/categories`
- `/admin/categories/create`
- `/admin/tags`
- `/admin/tags/create`
- `/admin/pages`
- `/admin/pages/create`
- `/admin/advertisements`
- `/admin/advertisements/create`
- `/admin/local-info-entries`
- `/admin/local-info-entries/create`
- `/admin/newsletter-subscriptions`
- `/admin/newsletter-subscriptions/create`
- `/admin/header-themes`
- `/admin/header-themes/create`
- `/admin/iha-health`
- `/admin/iha-sync-logs`
- `/admin/social-publications`
- `/admin/general-settings`
- `/admin/email-settings`
- `/admin/seo-settings`
- `/admin/integration-settings`
- `/admin/layout-studio`
- `/admin/layout-manager-legacy`
- `/admin/media-library`
- `/admin/analytics`
- `/admin/cache-management`
- `/admin/backup-manager`
- `/admin/users`
- `/admin/users/create`
- `/admin/shield/roles`
- `/admin/admin-operation-audits`

Rendered admin pages did not show mojibake after this batch. `/admin/shield/roles` remains guarded with `403` from direct route access and was not treated as a visible navigation blocker.

## Direct Public-Effect Map

- `NewsArticleResource`: direct effect on public article cards, detail pages, homepage scoring, IHA/manual content integrity.
- `CategoryResource`: direct effect on category pages, header/category navigation, article grouping.
- `TagResource`: direct effect on tag pages and article discoverability.
- `PageResource`: direct effect on static pages, footer links, SEO metadata.
- `AdvertisementResource`: direct effect on public ad slots, house-ad fallback, impressions/clicks.
- `HeaderThemeResource`: direct effect on public header special-day strip.
- `LayoutStudio`: direct effect on homepage composition after publish.
- `GeneralSettings`: direct effect on footer, contact, branding, default site text.
- `SeoSettings`: direct effect on robots, sitemap, meta defaults and AI/search visibility.
- `IntegrationSettings`: direct effect on IHA, translation, Instagram, AdSense readiness.
- `IhaHealth` and `IhaSyncLogs`: operational effect on IHA freshness and incident visibility.
- `MediaLibrary`: direct effect on broken media risk when attached media are deleted.
- `Users` and `Roles`: direct effect on admin authority and operational safety, not public content by itself.

## Fixes Applied

### 1. Legacy Layout Manager Debug Error Removed

Problem: `/admin/layout-manager-legacy` rendered a Symfony/Ignition debug error in local mode because the page called `abort(410)` from `mount()`.

Fix:

- Removed the abort path.
- Kept the legacy page hidden from navigation.
- Limited access to admin-panel users.
- Rendered a controlled disabled stub with a link to Layout Studio.
- Preserved the invariant that legacy mutation methods do not exist.

Public effect: avoids a broken admin UX and prevents confusion around legacy layout controls.

### 2. Admin Source Mojibake Guard Strengthened

Problem: `AdminPanelProvider` contained a mojibake brand string even though runtime middleware masked it.

Fix:

- Replaced the source string with correct UTF-8: `Adıyaman Dijital Haber Yönetim`.
- Extended `AdminLanguageQualityTest` to include `app/Providers/Filament`.
- Cleaned `IhaHealth` source strings to correct UTF-8.

Public effect: no direct public-page change, but prevents future admin UI regressions and removes dependency on runtime encoding repair.

### 3. IHA Scheduler Model Re-aligned With Queue-Based Production Target

Problem: current scheduler still used `iha:sync --inline` every 10 minutes. This contradicted the production queue model and made the admin health note operationally misleading.

Fix:

- Changed scheduler from `iha:sync --inline` every 10 minutes to queued `iha:sync` every 15 minutes.
- Kept short queue worker schedule enabled for shared-hosting style execution.
- Changed default `services.iha.sync_interval` from `10` to `15`.
- Updated IHA Health copy to say scheduler queues the sync and worker completes it.

Public effect: future IHA ingestion should not make the scheduler process own long-running feed work; this improves reliability of current-news flow.

### 4. Static Page SEO Gaps Closed

Problem: admin Pages list showed SEO missing for `hakkimizda`, `iletisim`, and `kvkk-aydinlatma`.

Fix:

- Added idempotent metadata backfill migration.
- Filled TR/EN/KU `meta_title` and `meta_description` for those protected static pages without overwriting existing values.

Public effect: static page SEO is now explicit rather than relying only on controller fallback.

### 5. Advertisement Public/Admin Contract Corrected

Problems:

- Public ad component and placement helper contained mojibake source strings.
- Legacy advertisement positions `sidebar` and `inline` were still present in local DB but current public templates use `sidebar-top`, `sidebar-bottom`, `article-top`.
- Homepage house-ad module duplicated positions already rendered inline in the homepage flow.

Fix:

- Cleaned public ad component strings.
- Cleaned `AdvertisementPlacement` metadata and guidance.
- Added idempotent migration mapping `sidebar -> sidebar-top` and `inline -> article-top`.
- Made homepage ads module context-aware: it skips slots already rendered elsewhere and preserves unsold inventory where the slot was not otherwise shown.

Public effect: homepage now shows professional house-ad inventory without duplicate slot repetition.

## Evidence

### Command Evidence

- `php artisan schedule:list`
  - Shows `php artisan iha:sync` at `*/15`.
  - Does not show `iha:sync --inline`.
  - Shows short `queue:work database --queue=default,analytics,instagram`.

- `php artisan migrate --force`
  - Applied `2026_07_11_000200_normalize_legacy_advertisement_positions`.
  - Applied `2026_07_11_000210_fill_static_page_seo_metadata`.

### Test Evidence

- `php artisan test tests\Feature\Filament\AdminLanguageQualityTest.php`
  - PASS: 2 tests, 131 assertions.

- `php artisan test tests\Feature\Filament\ContentOperationsAndLayoutTest.php --filter=legacy_layout_manager`
  - PASS: 1 test, 10 assertions.

- `php artisan test tests\Feature\Commands\SyncIhaNewsCommandTest.php`
  - PASS: 4 tests, 22 assertions.

- `php artisan test tests\Feature\Filament\AdminSecondaryResourcesOperationalTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php`
  - PASS: 8 tests, 154 assertions.

- `php artisan test tests\Feature\Public\PublicPagesTest.php`
  - PASS: 31 tests, 202 assertions.

### Browser Evidence

- `/admin/layout-manager-legacy`
  - `legacyHasStub=true`
  - `legacyHasIgnition=false`

- `/admin/iha-health`
  - `healthHasQueueModel=true`
  - `healthHasOldInlineCopy=false`
  - Rendered copy: `İHA senkronu 15 dakikada bir kuyruğa alınır ve sunucu queue worker tarafından tamamlanır.`

- `/admin/pages`
  - `seoEksikCount=0`
  - `hasMojibake=false`

- `/admin/advertisements`
  - `hasSidebarTop=true`
  - `hasMojibake=false`

- Public `/`
  - `houseAdCount=7`
  - positions: `home-top`, `home-feed`, `home-lower`, `between-news`, `sidebar-top`, `sidebar-bottom`, `footer`
  - duplicate positions: none
  - house-ad copy clean
  - mojibake: false

## Remaining Backlog

Priority order for the next admin evolution pass:

- P1: complete deeper NewsArticle admin audit around IHA record mutation, score controls, homepage editorial controls, and public card/detail effects.
- P1: exercise IHA Health actions from the panel, including test sync, translation queue button, and operation audit records.
- P2: decide whether the news-list view-count signal should be pinned/always-visible on desktop admin list or moved into a compact row description.
- P3: decide whether `/admin/shield/roles` should remain direct-route guarded or become a fully usable super-admin role management surface.

## Pass 2 - News and IHA Operations

### Additional Fixes Applied

#### IHA Health Production Sync Action

Problem: `IhaSyncTriggerService::triggerQueued()` existed and was used from the sync log list, but the primary IHA Health page only exposed an inline test sync action. This weakened the admin control-center model because the operator's main health screen did not offer the production-mode queued sync action.

Fix:

- Added `Senkronu Kuyruğa Al` header action to `/admin/iha-health`.
- Kept `Test Senkronu Başlat` as a separate inline diagnostic action.
- Added operation audit recording for `iha.queued_sync`.
- Added audit recording to the existing queued sync action on `IhaSyncLogResource`.

Public effect: admin can now trigger the real production-mode ingest path from the main IHA Health center; the action remains traceable in operation audit records.

#### News Admin Direct Public-Effect Tests

Problem: the admin content integrity suite already covered bulk protections, tags and writer restrictions, but two direct public-effect guarantees were not explicit:

- IHA article edit-save should not mutate source content.
- A published manual news article created in admin should become visible on the public detail page.

Fix:

- Added test coverage proving IHA edit-save does not alter title, content, slug, source or status.
- Added test coverage proving a published manual article created via admin renders on its public detail URL with body content.

Public effect: admin-created manual content has verified public visibility, while IHA source integrity remains protected.

### Additional Evidence

- `php artisan test tests\Feature\Filament\AdminContentIntegrityTest.php`
  - PASS: 19 tests, 187 assertions.

- `php artisan test tests\Feature\Filament\AdminOperationAuditTest.php tests\Feature\Filament\AdminOperationsReadinessTest.php tests\Unit\Services\IhaSyncTriggerServiceTest.php`
  - PASS: 14 tests, 106 assertions.

- Browser `/admin/news-articles`
  - `hasCreate=true`
  - `hasIhaTab=true`
  - `hasMojibake=false`

- Browser `/admin/iha-health`
  - `hasQueuedAction=true`
  - `hasTestAction=true`
  - `hasTranslationAction=true`
  - `hasMojibake=false`

### New Backlog Note

- P2: browser viewport did not expose the `Görüntülenme` table column even though table-level tests cover the column. In the next UI pass, decide whether the view-count signal should be pinned/always-visible on desktop admin list or moved into a compact row description.

## Pass 3 - Layout Studio, Media, Ads, and Settings

### Additional Fix Applied

#### Layout Studio Embedded Preview Height

Problem: the Layout Studio page rendered the embedded signed homepage preview, but browser measurement showed the preview frame at roughly `150px` height. That made the "same-screen preview" promise too weak for real admin use, because the operator could not inspect a meaningful desktop or mobile slice without relying on external preview links.

Fix:

- Replaced the preview wrapper's arbitrary Tailwind height dependency with an explicit inline preview contract.
- Desktop preview now uses `height: 720px; width: 100%;`.
- Mobile preview keeps the `390px` width target with `height: 720px; width: 390px; max-width: 100%;`.
- Extended the Layout Studio test to assert the desktop preview height contract.

Public effect: no direct public-page mutation. The change improves admin confidence before publish by making the public homepage preview usable inside the management panel.

### Additional Browser Evidence

- `/admin/layout-studio`
  - `hasPreviewSection=true`
  - `hasDesktopToggle=true`
  - `hasMobileToggle=true`
  - `hasReadiness=true`
  - `wrapperStyle="height: 720px; width: 100%;"`
  - `wrapperClientHeight=718`
  - `iframeClientHeight=718`
  - `layoutLogs=[]`
  - `mojibake=false`

- `/admin/media-library`
  - status 200
  - rendered copy explains that deletion is active only for orphaned media.
  - current local media count: `0 / 0 dosya`.
  - `mojibake=false`

- `/admin/advertisements`
  - status 200
  - `Yeni Reklam` visible.
  - `Eksik Görsel` visible for active banner records without usable media.
  - `mojibake=false`

- Public `/`
  - `houseAdCount=7`
  - visible unsold inventory: `Ana Sayfa Üst Sponsor`, `Ana Sayfa Haber Arası`, `Ana Sayfa Alt Sponsor`, `Sponsor Modülü Geniş`, `Sidebar Üst`, `Sidebar Alt`, `Footer`.
  - placeholder image leakage for `placeholder-news`, `newspaper`, or `gazete`: none in the sampled DOM.
  - `mojibake=false`

### Additional Test Evidence

- `php artisan test tests\Feature\Filament\ContentOperationsAndLayoutTest.php --filter=layout_studio_renders_panel_preview_and_device_toggle`
  - PASS: 1 test, 8 assertions.

- `php artisan test tests\Feature\Filament\AdminLanguageQualityTest.php`
  - PASS: 2 tests, 131 assertions.

- `php artisan test tests\Feature\Filament\ContentOperationsAndLayoutTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Filament\AdminOperationsReadinessTest.php`
  - PASS: 22 tests, 173 assertions.

- `php artisan test tests\Feature\Public\PublicPagesTest.php --filter='(home_renders_current_active_home_and_global_ads|active_banner_without_media|empty_home_ad_slots_render|empty_home_ad_slots_are_hidden|manual_banner_renders_mobile|sidebar_banner|adsense_ad_requires|news_detail_renders_article_top|sparse_home_modules)'`
  - PASS: 9 tests, 82 assertions.

- `git diff --check`
  - PASS.

### Updated Readiness Notes

- Layout Studio now has usable same-screen preview, quality gate warnings, signed external preview links, editor/super-admin separation and draft/publish tests.
- Media Library deletion protection is covered by test: attached media are not deleted, orphan media can be deleted.
- Advertisement admin/public contract is covered by tests and browser evidence: missing-media ads are visible as operational risk in admin and hidden/fallbacked appropriately on public pages.
- General, SEO, Integration and Email settings have save/remount and write-only secret preservation coverage.

## Pass 4 - HeaderTheme Admin Preview and Public Header Delta

### Additional Test Coverage Applied

Problem: the HeaderTheme public resolver and special-day strip had unit/public coverage, but the Filament table preview action itself was not directly covered. That left a gap between "theme can render" and "admin can open the signed public preview from the management surface."

Fix:

- Added a Filament test proving the `preview` table action is visible for a valid theme.
- Added a Filament test proving the `preview` action redirects to a valid signed public preview URL with the selected locale and preview date.
- The same test follows the redirect URL and verifies the public header receives the expected theme class, republic seal markup and event message.

Public effect: admin preview now has a verified path from management table to signed public header simulation before any live theme decision is made.

### Browser Evidence

- `/admin/header-themes`
  - status 200.
  - `Yeni Milli Gün Teması` visible.
  - `Önizle` action visible.
  - readiness/status columns visible: `TAKVİM`, `ZAMANLAMA`, `HAZIRLIK`.
  - asset policy columns visible: `BAYRAK`, `ATATÜRK`.
  - listed themes visible: `10 Kasım`, `29 Ekim`, `30 Ağustos`, `19 Mayıs`, `23 Nisan`, `Ramazan Bayramı`, `Kurban Bayramı`.
  - `mojibake=false`.
  - console error logs: none.

- Signed public preview `29-ekim`
  - header class includes `adh-header-theme adh-theme-29-ekim adh-tone-national`.
  - event badge exists.
  - asset: `turkish-flag-official.svg`, alt `Türk bayrağı`.
  - masthead overlay classes absent: no `adh-theme-visual`, no `adh-theme-ataturk`.
  - visible preview text includes `ÖNİZLEME`.
  - `mojibake=false`, console error logs: none.

- Signed public preview `10-kasim`
  - header class includes `adh-header-theme adh-theme-10-kasim adh-tone-commemoration`.
  - event badge exists.
  - asset: `ataturk-pd-tr-cutout.png`, alt `Mustafa Kemal Atatürk`.
  - masthead overlay classes absent.
  - visible message: `Saygı, özlem ve minnetle anıyoruz.`
  - `mojibake=false`, console error logs: none.

- Signed public preview `ramazan-bayrami`
  - header class includes `adh-header-theme adh-theme-ramazan-bayrami adh-tone-bayram`.
  - event badge exists.
  - asset: `bayram-crescent.svg`, alt `Bayram hilali`.
  - Atatürk/bayrak asset absent for bayram theme.
  - `mojibake=false`, console error logs: none.

### Additional Test Evidence

- `php artisan test tests\Feature\Filament\AdminSecondaryResourcesOperationalTest.php tests\Feature\Public\HeaderThemeTest.php tests\Unit\Support\HeaderThemeResolverTest.php`
  - PASS: 20 tests, 193 assertions.

- `php artisan test tests\Feature\Filament\AdminContentIntegrityTest.php --filter=header_theme`
  - PASS: 3 tests, 21 assertions.

### Operational Note

Signed preview URLs are host-sensitive. A URL signed for `localhost:8000` correctly returns `403 Invalid Signature` if manually rewritten to `127.0.0.1:8000`. The admin action itself redirects to the generated signed URL, so the local management flow works. Production still requires `APP_URL` to match the canonical public/admin host.

## Current Readiness Judgment

This batch closes several visible admin trust and public-effect inconsistencies, but the broader objective is not complete yet. The remaining backlog now centers on news-list ergonomics for high-signal columns, role-management surface policy, IHA Health action exercise, and a final page-by-page admin evolution sweep.
