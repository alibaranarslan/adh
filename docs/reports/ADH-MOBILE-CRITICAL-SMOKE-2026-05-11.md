# ADH Mobile Critical Smoke - 2026-05-11

## Scope

Server access was unavailable, so production go/no-go remained blocked. This pass focused on local mobile-critical public surfaces that do not require Hetzner:

- Homepage first viewport
- Category page first viewport
- Latest IHA news detail first viewport
- Contact page first viewport
- Static/trust page HTTP smoke
- Cookie consent mobile behavior

This is not the full mobile QA matrix. It is a critical smoke pass intended to reduce customer/demo-facing mobile risk while waiting for server access.

## Preflight

Commands:

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
php artisan iha:monitor-forward --limit=20
php artisan queue:failed
```

Result:

```text
deploy:verify: All checks passed
iha:monitor-forward: health=healthy, quality_risk=no, empty_content=0, weak_body=0, short_body=0
queue:failed: No failed jobs found
```

IHA caveat:

```text
sync_status=running and freshness_minutes=70 are local scheduler/worker-state caveats. They are not production evidence. Hetzner cron + worker proof is still required.
```

## Visual Evidence

Screenshots generated with local Chrome mobile viewports:

```text
storage/app/qa-mobile/home-360x740.png
storage/app/qa-mobile/home-390x844.png
storage/app/qa-mobile/category-390x844.png
storage/app/qa-mobile/contact-390x844.png
storage/app/qa-mobile/detail-390x844.png
storage/app/qa-mobile/home-390x844-cookie-compact.png
storage/app/qa-mobile/detail-390x844-cookie-compact.png
```

Observed after the compact cookie fix:

- Header and masthead render without overlap in the first viewport.
- Category nav is horizontally scrollable and remains tappable.
- Homepage hero area becomes visible above the cookie panel.
- Latest IHA detail route returns 200 and the page shell renders cleanly.
- Contact route returns 200 and page shell renders cleanly.
- No visible mojibake was observed in the checked public HTML responses.
- Cookie consent no longer dominates most of the mobile viewport; it is capped and internally scrollable.

## Fix Applied

File:

```text
resources/views/cookie-consent.blade.php
```

Change:

- Mobile cookie panel capped to `max-h-[46dvh]`.
- Cookie panel remains `overflow-y-auto`.
- Action row is sticky inside the panel, so `Kabul Et`, `Reddet`, and `Ayarlar` stay reachable while scrolling the consent text.
- Mobile copy sizes were tightened; desktop/tablet panel behavior remains capped near the previous 70dvh model.

Test updated:

```text
tests/Feature/Public/PublicPagesTest.php
```

The cookie consent assertion now checks the compact mobile cap, desktop cap and scroll behavior.

## HTTP Smoke

Checked routes:

```text
/
/kategori/gundem
/ikinci-kattan-dusen-genc-hayatini-kaybetti
/iletisim
/sayfa/hakkimizda
/sayfa/gizlilik-politikasi
/sayfa/kvkk-aydinlatma
/sayfa/cerez-politikasi
```

Result:

```text
All checked routes returned HTTP 200.
Mojibake signal: false
404 signal: false
```

## Tests

Commands:

```bash
php artisan test tests\Feature\Public
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Result:

```text
Tests: 42 passed (239 assertions)
deploy:verify: All checks passed
```

## Decision

Status: PASS WITH FULL-MOBILE-QA CAVEAT

No P0/P1 mobile-critical public blocker was found in this limited local pass after the cookie panel fix.

Remaining caveats:

- Full mobile QA matrix is still not complete.
- Admin mobile QA was not covered in this pass.
- Production mobile/domain verification remains blocked by Hetzner access.
- Production IHA cron/worker evidence remains blocked by Hetzner access.

## Next Recommended Step

While server access is unavailable, continue with local low-risk readiness work:

1. Run a targeted admin mobile/operations smoke for dashboard, IHA Health, Sync Logs and ad management.
2. Or, if customer demo is imminent, prepare the customer demo tab/order checklist and preload public/admin pages.
