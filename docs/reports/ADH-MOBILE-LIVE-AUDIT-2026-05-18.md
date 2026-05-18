# ADH Mobile Live Audit - 2026-05-18

## Scope

- Live target inspected: `https://adiyamandijitalhaber.com.tr/`
- Local implementation target: `http://127.0.0.1:8000/`
- Viewports: `390x844`, `430x932`
- Focus: public mobile homepage/header/cookie/hero first screen.

## Constructive Criticism

### Finding 1 - Mobile logo overflows on narrow phones

- Severity: P1
- Evidence: `docs/reports/assets/mobile-live-audit-2026-05-18/home-390-live-before.png`
- Risk: At `390px`, the masthead title was clipped from the right, making the site identity look unfinished.
- Fix: Mobile masthead typography now uses a narrower tracking and viewport-based clamp. The logo remains one line without overflowing.

### Finding 2 - Cookie consent dominates the first visit

- Severity: P1
- Evidence: `docs/reports/assets/mobile-live-audit-2026-05-18/home-390-live-before.png`
- Risk: The cookie panel covered too much of the first news view and contained dense legal copy on first paint.
- Fix: The mobile cookie panel was reduced to `34dvh`, the default copy was shortened, detailed cookie category text moved behind settings, and action buttons now use a two-row safe layout on narrow screens.

### Finding 3 - Mobile navigation priority was weak

- Severity: P2
- Evidence: `docs/reports/assets/mobile-live-audit-2026-05-18/home-390-live-before.png`
- Risk: On narrow screens, the visible topbar did not clearly prioritize the hamburger/category entry point.
- Fix: The hamburger menu was moved before the language control on mobile, negative right margin was removed, and the unused center space now carries a compact time/weather pill.

### Finding 4 - Breaking ticker and source text polish

- Severity: P2
- Risk: The breaking strip had tight spacing and a source-level mojibake bullet risk.
- Fix: Mobile spacing was tightened, headline max-width reduced, link weight improved, and bullet markup uses `&bull;`.

### Finding 5 - Hero section feels heavy on mobile

- Severity: P2
- Risk: The hero image and copy consumed too much vertical space before the user reached the news rhythm.
- Fix: Mobile hero image height was reduced to a fixed compact height, the headline/summary now sits in a raised card immediately below the image, summary is clamped on mobile, and the fast agenda block starts only after the main story copy.

### Finding 6 - Header upper band lacked useful mobile information

- Severity: P2
- Risk: The mobile topbar had empty center space while useful local context such as time and weather was only available on desktop.
- Fix: A compact mobile-only pill was added to show current time and Adıyaman weather temperature between search and menu controls.

## Implemented Files

- `resources/views/layouts/partials/header.blade.php`
- `resources/views/components/language-switcher.blade.php`
- `resources/views/layouts/partials/breaking-bar.blade.php`
- `resources/views/cookie-consent.blade.php`
- `resources/views/home/sections/hero.blade.php`
- `resources/css/app.css`
- `tests/Feature/Public/PublicPagesTest.php`

## Evidence Screenshots

### Before - Live

- `docs/reports/assets/mobile-live-audit-2026-05-18/home-390-live-before.png`
- `docs/reports/assets/mobile-live-audit-2026-05-18/home-430-live-before.png`

### After - Local

- `docs/reports/assets/mobile-live-audit-2026-05-18/home-390-local-after-third-pass.png`
- `docs/reports/assets/mobile-live-audit-2026-05-18/home-430-local-after-third-pass.png`

## Validation

```bash
npm run build
php artisan optimize:clear
php artisan test tests\Feature\Public\PublicPagesTest.php tests\Feature\Public\NewsDetailPresentationTest.php
```

Result:

- `npm run build`: PASS
- `PublicPagesTest`: PASS, 19 tests
- `NewsDetailPresentationTest`: PASS, 6 tests
- Combined: PASS, 25 tests / 152 assertions

## Deployment Note

The live domain was inspected, but the fixes were implemented in the local repository. Production will continue showing the old mobile surface until this patch is deployed to the server and the production asset cache is refreshed.

Recommended production deploy evidence after server access:

```bash
npm run build
php artisan optimize:clear
curl -I https://adiyamandijitalhaber.com.tr/
```

Then recapture mobile screenshots on the live domain.
