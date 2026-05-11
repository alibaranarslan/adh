# ADH Admin Mobile Operations Smoke - 2026-05-11

## Scope

Server access is unavailable, so production go/no-go remains blocked. This local pass focused on admin/operations surfaces that can be hardened without Hetzner:

- Admin dashboard
- IHA Health
- IHA Sync Logs
- Analytics / operations pages
- Advertisement management
- General / SEO / Integration / Email settings
- System alerts
- Role and permission boundaries
- Admin language quality
- Mobile overflow safety for custom admin tables

This is not a complete admin mobile device QA pass. It is a local critical smoke and responsive hardening pass.

## Fixes Applied

### IHA Health Logs Table

File:

```text
resources/views/filament/pages/iha-health.blade.php
```

Change:

- The recent sync logs table wrapper now uses `overflow-x-auto`.
- This prevents the multi-column operational table from forcing a narrow mobile viewport wider than the screen.

### Analytics Tables

File:

```text
resources/views/filament/pages/analytics.blade.php
```

Change:

- Period/action buttons now wrap on narrow widths.
- Top articles and traffic-source tables are wrapped with `overflow-x-auto`.
- Tables use `min-w-full` and spacing guards.
- User-facing labels were normalized to clean Turkish while preserving existing behavior.

### Control Center Custom Tables

File:

```text
resources/css/admin-guide.css
```

Change:

- `.admin-table-shell` now uses `overflow-x-auto`.
- `.admin-table` now uses `min-width: 100%`.
- This protects custom dashboard tables from clipping or forcing page-level horizontal overflow.

## Tests

Targeted admin operations smoke:

```bash
php artisan test tests\Feature\Filament\AdminSettingsOperationsFinalSmokeTest.php tests\Feature\Filament\AdminOperationsReadinessTest.php tests\Feature\Filament\IhaHealthPageTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Filament\IntegrationSettingsPageTest.php tests\Feature\Filament\AdminDashboardAndNewsResourceTest.php tests\Feature\Filament\AnalyticsAndOperationsPagesTest.php
```

Result:

```text
Tests: 21 passed (177 assertions)
```

Full admin/Filament regression:

```bash
php artisan test tests\Feature\Filament
```

Result:

```text
Tests: 58 passed (578 assertions)
```

Deploy smoke:

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Result:

```text
All checks passed.
```

Encoding/static signal:

```text
resources/views/filament/pages/analytics.blade.php bad=False
resources/views/filament/pages/iha-health.blade.php bad=False
resources/css/admin-guide.css bad=False
```

## Covered Functional Risks

- Super admin can render settings and operations pages.
- Settings and operations pages remain super-admin only where expected.
- IHA Health uses `.env` config credentials when DB settings are empty.
- Contact form sends to configured recipient email.
- System alerts show missing, stale, failed and success-lag IHA evidence.
- General / SEO / Integration / Email settings persist and remount.
- Write-only secret settings are preserved when blank on save.
- Advertisement CRUD works for manual banners and AdSense.
- AdSense slot validation and missing Client ID state remain covered.
- Admin language-quality tests pass.
- IHA records remain protected from destructive bulk/delete paths.

## Decision

Status: PASS WITH DEVICE-QA CAVEAT

No P0/P1 admin operations blocker was found in this local smoke pass. The main mobile risk identified in custom operational tables was hardened with scroll-safe wrappers.

Remaining caveats:

- This pass used test/runtime evidence and static responsive hardening, not a full manual mobile admin device tour.
- Production admin verification remains blocked until Hetzner deploy and production admin account proof.
- Production cron/worker/IHA evidence remains blocked until server access.

## Next Recommended Step

While server access is unavailable, the most useful remaining local work is customer-demo readiness:

1. Prepare a demo walkthrough checklist with public/admin tab order.
2. Confirm the pages to show are preloaded and free of secret/terminal exposure.
3. Keep production limitation language ready: local readiness is acceptable, but final go-live requires Hetzner evidence.
