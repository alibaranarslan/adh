# ADH Instagram Auto Publish Implementation - 2026-05-13

## Summary

Implemented the v1 Instagram auto-publish system for published ADH news.

The system now records every eligible published article in an idempotent social publication
table, generates a 1080x1080 Instagram feed creative with an overlaid short headline, creates
a bounded caption, publishes via Instagram Graph API, and exposes publication state in the
admin panel.

## Implemented Capabilities

- New `social_publications` table with one publication per article/platform.
- New `SocialPublication` model and `SocialPublicationService`.
- `NewsArticleObserver` now creates Instagram publication records for published IHA and
  manual articles.
- `PublishToInstagramJob` now works from `publication_id`, not a serialized article model.
- `InstagramService` now supports:
  - `INSTAGRAM_ENABLED`
  - `INSTAGRAM_GRAPH_VERSION`
  - readiness status,
  - 2200-character caption cap,
  - category/tag hashtags,
  - 72-character creative title cap,
  - 1080x1080 JPEG creative generation,
  - absolute public creative URLs,
  - separated container/publish API response handling.
- Admin Integration Settings now includes an Instagram automation enable toggle.
- New admin resource: Instagram publication list with preview, status, caption, error, media
  id, attempts, and retry action.
- News table now surfaces Instagram publication status per article.

## Operational Rules

- Published IHA and manual articles are eligible.
- Draft articles are ignored.
- Duplicate publication records are prevented by unique article/platform constraint.
- Missing credentials or disabled automation creates an auditable skipped state when the job
  runs.
- Missing article image creates an auditable skipped state.
- Container or publish API failure creates a failed state and can be retried from admin.
- Production requires `queue:work database --queue=default,analytics,instagram`.
- Production requires public HTTPS `APP_URL` so Instagram can fetch the generated creative.

## Local Verification

Passed:

- `php artisan test tests/Feature/Automation/InstagramAutomationTest.php tests/Feature/Jobs/InstagramPublicationJobTest.php tests/Unit/Services/InstagramServiceTest.php`
  - 13 tests, 43 assertions
- `php artisan test tests/Feature/Filament/IntegrationSettingsPageTest.php tests/Feature/Filament/SocialPublicationResourceTest.php tests/Feature/Filament/AdminOperationsReadinessTest.php`
  - 11 tests, 69 assertions
- `php artisan test tests/Feature/Jobs/SyncIhaNewsJobTest.php tests/Feature/Commands/SyncIhaNewsCommandTest.php`
  - 6 tests, 40 assertions
- `php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdminDashboardAndNewsResourceTest.php tests/Feature/Filament/AdminLanguageQualityTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php`
  - 27 tests, 270 assertions
- `php artisan test tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Feature/Filament/ContentOperationsAndLayoutTest.php tests/Feature/Filament/AdminContentIntegrityTest.php`
  - 21 tests, 185 assertions
- `php artisan test tests/Unit/Support/AdminPrivilegesTest.php tests/Unit/Support/AdminGuideRegistryTest.php tests/Unit/Services/IhaSyncTriggerServiceTest.php`
  - 8 tests, 34 assertions
- `php artisan deploy:verify --base-url=http://127.0.0.1:8000`
  - all checks passed
- `php artisan queue:failed`
  - no failed jobs
- `php artisan route:list --path=admin/social-publications`
  - admin route registered

Local migration applied:

- `2026_05_13_000001_create_social_publications_table`

## Production Notes

Before live Instagram publish:

- Set `INSTAGRAM_ENABLED=true`.
- Set a valid long-lived `INSTAGRAM_ACCESS_TOKEN`.
- Set `INSTAGRAM_BUSINESS_ACCOUNT_ID`.
- Keep `INSTAGRAM_GRAPH_VERSION=v24.0` unless Meta requires a newer version.
- Confirm Meta app permissions include Instagram content publishing capability.
- Confirm generated creative URLs are public HTTPS and reachable by Meta.
- Confirm Supervisor/systemd worker listens to `instagram`.

## Remaining Production Evidence

Blocked until Hetzner access:

- Real token/account readiness.
- Live Graph API connection test.
- One controlled live Instagram publication.
- Worker proof for `default,analytics,instagram`.
- Public HTTPS creative URL fetch proof.
