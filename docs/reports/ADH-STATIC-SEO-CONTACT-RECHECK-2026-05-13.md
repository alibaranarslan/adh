# ADH Static Page, SEO and Contact Recheck - 2026-05-13

## Scope

Local predeploy control for customer-facing static information pages, SEO support routes, footer-accessed policy pages and the contact form delivery path.

Production server evidence is not included because Hetzner access is not available in this phase.

## Result

Status: PASS for local predeploy.

Hetzner production go/no-go items remain BLOCKED until server access is available.

## Evidence

### Automated tests

Command:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdminStaticPageReflectionTest.php tests/Feature/Filament/AdminOperationsReadinessTest.php
```

Result:

- PASS: 30 tests
- Assertions: 193

Covered areas:

- Public home, category, detail, city, robots and advertising smoke behavior.
- Fixed static routes and generic page routes for customer policy pages.
- Admin static page edit reflection to public routes.
- Protected static pages cannot be deleted from admin.
- Contact form sends to configured recipient email.
- General/SEO/Integration/Email settings persist and remount.
- IHA health credential fallback and system alert evidence states.

### Local HTTP smoke

Target: `http://127.0.0.1:8000`

- PASS: `/hakkimizda` returned 200.
- PASS: `/gizlilik-politikasi` returned 200.
- PASS: `/kvkk` returned 200.
- PASS: `/cerez-politikasi` returned 200.
- PASS: `/iletisim` returned 200.
- PASS: `/robots.txt` returned 200.
- PASS: `/sitemap.xml` returned 200.

### Text quality smoke

Checked pages:

- `/hakkimizda`
- `/gizlilik-politikasi`
- `/kvkk`
- `/cerez-politikasi`
- `/iletisim`

Result:

- PASS: no mojibake patterns found.
- PASS: no `Lorem ipsum` or `TODO` demo text found.
- PASS: page titles render as real Turkish titles.

Observed titles:

- `Hakkımızda | Adıyaman Dijital Haber`
- `Gizlilik Politikası | Adıyaman Dijital Haber`
- `KVKK Aydınlatma Metni | Adıyaman Dijital Haber`
- `Çerez Politikası | Adıyaman Dijital Haber`
- `İletişim | Adıyaman Dijital Haber`

## Functional Notes

- Footer policy links are backed by public page routes and seeded customer content.
- Direct fixed routes are present for `/hakkimizda`, `/gizlilik-politikasi`, `/kvkk`, `/cerez-politikasi`.
- Generic public page routes are present for `/sayfa/hakkimizda`, `/sayfa/gizlilik-politikasi`, `/sayfa/kvkk-aydinlatma`, `/sayfa/cerez-politikasi`.
- Contact form uses `general.contact_recipient_email` first, then falls back to `general.contact_email`, then mail config.
- Admin General Settings includes a dedicated contact form recipient field.
- Robots route rewrites/appends the current sitemap URL.
- Local `public/sitemap.xml` exists and is served successfully.

## Risks / Blocked Items

- BLOCKED: Production `.env`, real mail delivery, public domain sitemap URL and HTTPS behavior cannot be verified without Hetzner access.
- BLOCKED: Production cron/queue evidence is outside this local static/contact check.
- NOTE: The actual contact recipient address must be set in production admin settings or `.env`/mail config before go-live.

## Decision

Local predeploy static page, SEO route and contact form readiness is acceptable.

Next recommended step: continue with a public visual smoke pass focused on homepage/detail/category visible surface after the recent ad, dark-mode and static page changes.
