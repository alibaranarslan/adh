# ADH AI Visibility Implementation - 2026-05-19

## Scope

Goal: make `adiyamandijitalhaber.com.tr` easier for ChatGPT Search, Google AI surfaces, Bing/Copilot and similar answer engines to crawl, understand, cite and evaluate as a local Adiyaman news source.

Non-goal: this work does not guarantee recommendation or ranking inside any AI chat product. It strengthens crawl access, source discovery, feed availability, structured data and editorial trust signals.

## Implemented

### AI crawler and robots policy

- Dynamic `robots.txt` now preserves `User-agent: * / Allow: /`.
- `OAI-SearchBot` is explicitly allowed.
- `ChatGPT-User` is explicitly allowed.
- `GPTBot` is not blocked.
- Robots output now advertises:
  - `https://adiyamandijitalhaber.com.tr/sitemap.xml`
  - `https://adiyamandijitalhaber.com.tr/sitemap-news.xml`
  - `https://adiyamandijitalhaber.com.tr/rss.xml`

### Machine-readable source map

- Added dynamic `/llms.txt`.
- Content type: `text/markdown; charset=UTF-8`.
- Cache policy: public cache for 10 minutes.
- Includes:
  - core public source URLs,
  - sitemap/news sitemap/RSS URLs,
  - trust and editorial policy URLs,
  - last 10 published public Turkish articles.
- Excludes admin, preview, draft and private surfaces.

### RSS/feed layer

- Added public feeds:
  - `/rss.xml`
  - `/feed/news.xml`
  - `/feed/adiyaman.xml`
  - `/feed/kategori/{slug}.xml`
- Feed output includes title, link, guid, pubDate, category and description.
- Image enclosure is emitted only when a real article image exists.
- Placeholder/stock image is not emitted as RSS enclosure.

### Structured data

- Expanded site `Organization` schema:
  - `areaServed: Adiyaman`
  - `knowsAbout` local news topics
  - `contactPoint` when contact email/phone settings exist
  - existing `sameAs` social links preserved
- Existing `WebSite` + `SearchAction` schema preserved.
- Added page-level schema:
  - `AboutPage` for `/hakkimizda`
  - `ContactPage` for `/iletisim`
  - `CollectionPage` for category and city landing pages

### Editorial trust page

- Added `yayin-ilkeleri` as a protected static page slug.
- Added localized route `/yayin-ilkeleri`.
- Added footer link to editorial principles.
- Added `AiVisibilityContentSeeder` to create:
  - `Yayın İlkeleri ve Haber Kaynaklarımız`
  - English and Kurmanji title/content/meta variants

### Admin monitoring

- SEO health snapshot now exposes:
  - `llms_txt_available`
  - `rss_available`
  - `oai_searchbot_allowed`
  - `chatgpt_user_allowed`
  - `llms_recent_articles_available`
- Admin SEO page has an `AI Görünürlüğü` section.
- Dashboard SEO card source now includes AI crawl and feed/llms health cards.

## Tests

Commands run locally:

```bash
php artisan test tests\Feature\Public\AiVisibilityTest.php
php artisan test tests\Feature\Public\SeoInfrastructureTest.php
php artisan test tests\Feature\Filament\AdminOperationsReadinessTest.php --filter=seo_integration
php artisan test tests\Feature\Public\PublicPagesTest.php
php artisan test tests\Feature\Public\LocalizedNavigationTest.php
php artisan test tests\Feature\Filament\AdminLanguageQualityTest.php
npm run build
php artisan test
```

Results:

- `AiVisibilityTest`: 4 passed, 44 assertions
- `SeoInfrastructureTest`: 3 passed, 25 assertions
- `AdminOperationsReadinessTest --filter=seo_integration`: 1 passed, 32 assertions
- `PublicPagesTest`: 24 passed, 147 assertions
- `LocalizedNavigationTest`: 2 passed, 18 assertions
- `AdminLanguageQualityTest`: 2 passed, 126 assertions
- `npm run build`: passed
- Full suite `php artisan test`: 205 passed, 1390 assertions
- Local HTTP smoke:
  - `http://127.0.0.1:8000/llms.txt`: 200
  - `http://127.0.0.1:8000/rss.xml`: 200
  - `http://127.0.0.1:8000/robots.txt`: contains `OAI-SearchBot`, `ChatGPT-User`, `rss.xml`

Additional note:

- `HomeModuleDataServiceTest` was aligned with the accepted Acil Gündem editorial-score priority behavior. The product behavior was not changed: hero-used articles remain excluded from duplicate breaking placement, and the highest-scored remaining article keeps priority over purely image-recency fallback.

## Deployment Notes

After deploy, run:

```bash
php artisan db:seed --class=Database\\Seeders\\AiVisibilityContentSeeder
php artisan sitemap:generate
php artisan optimize:clear
```

Live smoke targets:

- `https://adiyamandijitalhaber.com.tr/robots.txt`
- `https://adiyamandijitalhaber.com.tr/llms.txt`
- `https://adiyamandijitalhaber.com.tr/rss.xml`
- `https://adiyamandijitalhaber.com.tr/feed/news.xml`
- `https://adiyamandijitalhaber.com.tr/sitemap.xml`
- `https://adiyamandijitalhaber.com.tr/sitemap-news.xml`
- one recent article detail page for schema/canonical verification

## Remaining External Operations

- Submit sitemap index and news sitemap in Google Search Console.
- Add/verify Bing Webmaster Tools.
- Monitor referrals in GA4 from `chatgpt.com`, `perplexity.ai`, `copilot.microsoft.com`, `bing.com`, and `google.com`.
- Validate representative article URLs in Google Rich Results Test and Search Console URL Inspection.
