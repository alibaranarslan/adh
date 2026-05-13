# ADH Full Requirements Checklist - 2026-05-13

## Summary

Local predeploy status: PASS with production BLOCKED items.

No P0/P1 blocker remains in local code/test/browser-fallback evidence. Hetzner production go/no-go remains blocked because server access, real secrets, HTTPS domain, cron and worker evidence are not available yet.

## Checklist

| Area | Status | Evidence |
| --- | --- | --- |
| Public visual smoke | PASS | `ADH-PUBLIC-VISUAL-SMOKE-2026-05-13.md` |
| News detail body rendering | PASS | `ADH-NEWS-DETAIL-IHA-READINESS-2026-05-13.md` |
| IHA queued production model | PASS | `ADH-IHA-LOCAL-PRODUCTION-MODEL-RECHECK-2026-05-13.md` |
| IHA local runtime freshness | WARN | latest local log is `running`; monitor now reports `health=warn`, not `healthy` |
| Instagram automation local flow | PASS | subagent evidence: 24 tests / 112 assertions passed |
| Advertisement management/tracking | PASS | subagent evidence and existing `ADH-ADVERTISING-TRACKING-RECHECK-2026-05-13.md` |
| Admin CRUD/settings/role guards | PASS | subagent evidence and wide suite |
| Layout Studio and dark mode | PASS | subagent evidence and wide suite |
| Media upload hardening | PASS | subagent evidence and wide suite |
| SEO/KVKK/static/contact | PASS | `ADH-STATIC-SEO-CONTACT-RECHECK-2026-05-13.md` |
| Wide local test suite | PASS | 122 tests / 884 assertions |
| Deploy verification guard | PASS | `deploy:verify` includes health, homepage, robots and sitemap |
| Hetzner server access | BLOCKED | no SSH/session evidence yet |
| Production cron | BLOCKED | server evidence required |
| Production queue worker | BLOCKED | Supervisor/systemd evidence required |
| Production IHA credentials | BLOCKED | secrets must be checked on server without printing values |
| Production Instagram token/account | BLOCKED | Meta Graph live proof required |
| Production HTTPS/public asset URLs | BLOCKED | domain and Nginx evidence required |
| Production mail delivery | BLOCKED | SMTP/live form delivery evidence required |
| Production AdSense/ads.txt | BLOCKED | publisher/slot/domain evidence required |

## Wide Test Suite Evidence

Command:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Public/NewsDetailPresentationTest.php tests/Feature/Commands/SyncIhaNewsCommandTest.php tests/Feature/Commands/MonitorIhaForwardIngestCommandTest.php tests/Feature/Jobs/IhaSyncLogStatusTest.php tests/Feature/Commands/EnrichIhaSourceUrlsCommandTest.php tests/Feature/Automation/InstagramAutomationTest.php tests/Feature/Jobs/InstagramPublicationJobTest.php tests/Unit/Services/InstagramServiceTest.php tests/Feature/Filament/IntegrationSettingsPageTest.php tests/Feature/Filament/SocialPublicationResourceTest.php tests/Feature/Filament/AdminDashboardAndNewsResourceTest.php tests/Feature/Filament/AdminOperationsReadinessTest.php tests/Feature/Filament/AdminStaticPageReflectionTest.php tests/Feature/Filament/AdminLanguageQualityTest.php tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Feature/Filament/ContentOperationsAndLayoutTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php tests/Feature/Filament/AdminRoleResourcePermissionTest.php tests/Feature/Filament/AdminContentIntegrityTest.php tests/Unit/Support/AdminPrivilegesTest.php tests/Unit/Services/HomeModuleDataServiceTest.php tests/Unit/Services/LayoutConfigServiceTest.php tests/Feature/Filament/AdminGuideModeTest.php
```

Result:

- `122 passed`
- `884 assertions`

## Subagent Evidence Summary

adh_akış:

- P0/P1 public/IHA/static blocker yok.
- P2 fixed by this batch: generated sitemap deploy verification is now guarded by `deploy:verify` and Hetzner runbook.
- P2 fixed by this batch: `running` IHA monitor state no longer reports healthy after the short grace period.
- Production evidence remains BLOCKED.

adh_nabız:

- P0/P1 admin/reklam/Instagram/layout/media blocker yok.
- Admin role/content guards passed.
- Advertisement rendering/tracking passed locally.
- Instagram local job/publication flow passed.
- Layout/dark mode and media upload hardening passed.
- Production Instagram token/account, HTTPS creative URL and queue worker proof remain BLOCKED.

## Decision

Local predeploy can proceed to Hetzner preparation. Production-ready/live-ready decision must not be given yet.

Go-live remains BLOCKED until `ADH-HETZNER-GO-NOGO-RUNBOOK-2026-05-13.md` is executed on the server and passes.
