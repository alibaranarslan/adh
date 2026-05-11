# ADH Customer Demo Package - 2026-05-11

## Purpose

This package defines a safe customer demo flow for Adiyaman Dijital Haber while Hetzner production access is still unavailable.

The demo should communicate two facts together:

- The visible local public/admin product surfaces are ready for customer review.
- Final production go-live still requires Hetzner evidence: Linux cron, queue worker, MySQL, Nginx/HTTPS, production secrets and controlled IHA ingest proof.

Do not say the site is fully production-live until the Hetzner go/no-go checklist passes.

## Current Local Evidence

Latest local proof before this package:

```text
deploy:verify: All checks passed
iha:monitor-forward: health=healthy, quality_risk=no, empty_content=0, weak_body=0, short_body=0
queue:failed: No failed jobs found
Public tests: 42 passed (239 assertions)
Filament/admin tests: 58 passed (578 assertions)
Admin operations targeted smoke: 21 passed (177 assertions)
```

Known caveat:

```text
Local monitor can show sync_status=running because local worker/scheduler state is not production evidence. This must not be used as a production readiness claim. Hetzner cron + worker proof remains required.
```

## Safe Opening Script

Use:

```text
Bugun haber sitesinin public yuzunu, haber detay akisini, kurumsal sayfalarini, reklam yerlesim mantigini ve yonetim panelindeki ana operasyon yuzeylerini gosterecegim.
Kod ve lokal predeploy kontrolleri bu sunum icin hazir. Canliya alma karari ise Hetzner sunucusunda cron, queue worker, veritabani, SSL ve IHA cekim kanitlari alindiktan sonra verilecek.
```

Avoid:

```text
Canliya tamamen hazir.
Sunucuda da kesin calisiyor.
IHA hicbir zaman problem cikarmaz.
Tum mobil QA tamamlandi.
Tum reklam ticari operasyonu eksiksiz bitti.
```

Safer wording:

```text
Lokal predeploy kontrolleri gecti.
Yeni IHA haberlerinde bos/zayif govde kalite riski su an yok.
Reklam v1 altyapisi manuel banner ve AdSense modeliyle hazir.
Canli karar Hetzner uzerinde alinacak kanitlarla netlesecek.
Mobilde kritik public/admin operasyon kirilmalari smoke edildi; full mobil QA ayrica planli.
```

## 10-Minute Pre-Demo Checklist

Run from:

```powershell
cd C:\nwp0203\haber-sitesi
```

Commands:

```powershell
php artisan deploy:verify --base-url=http://127.0.0.1:8000
php artisan iha:monitor-forward --limit=20
php artisan queue:failed
php artisan schedule:list
```

Expected:

```text
deploy:verify: All checks passed
iha:monitor-forward: quality_risk=no, empty_content=0, weak_body=0, short_body=0
queue:failed: No failed jobs found
schedule:list: iha:sync exists, iha:sync --inline does not appear
```

If server is not running:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Before screen sharing:

- Close or hide terminals showing `.env`, credentials or raw logs.
- Preload demo tabs.
- Clear accidental old tabs with historical blocker reports.
- If cookie banner appears, accept or reject it before the main walkthrough unless cookie consent itself is being shown.

## Current IHA Detail Samples

Use one of these only if checked shortly before the meeting:

```text
/ikinci-kattan-dusen-genc-hayatini-kaybetti | content_len=643
/mardinde-cifte-sampiyonluk-coskusu | content_len=1175
/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu | content_len=2126
/sanliurfada-anneler-gunu-unutulmadi | content_len=1760
/gibtuden-buyuk-basari-ilahiyat-ve-teknoloji-sosyalfestte-finale-yuruyor | content_len=2287
```

Preferred demo sample:

```text
/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu
```

Reason:

- Full body is comfortably long.
- It is recent in the local IHA window.
- It demonstrates that detail pages are not summary-only.

If any sample fails, do not debug in front of the customer. Pick a fresh sample from `php artisan iha:monitor-forward --limit=20`.

## Browser Tab Order

Open these tabs before the meeting:

```text
1. http://127.0.0.1:8000/
2. http://127.0.0.1:8000/kategori/gundem
3. http://127.0.0.1:8000/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu
4. http://127.0.0.1:8000/sayfa/hakkimizda
5. http://127.0.0.1:8000/sayfa/gizlilik-politikasi
6. http://127.0.0.1:8000/sayfa/kvkk-aydinlatma
7. http://127.0.0.1:8000/sayfa/cerez-politikasi
8. http://127.0.0.1:8000/iletisim
9. http://127.0.0.1:8000/admin/login
```

If admin will be shown after login, use this admin order:

```text
1. /admin
2. /admin/news-articles
3. /admin/iha-health
4. /admin/iha-sync-logs
5. /admin/advertisements
6. /admin/pages
7. /admin/media-library
8. /admin/general-settings
9. /admin/integration-settings
```

Admin demo account in local seed:

```text
admin@admin.com
password
```

Do not use or reveal production credentials during the demo.

## 20-Minute Public Demo Route

### 1. Homepage

URL:

```text
/
```

Show:

- Brand masthead.
- Breaking strip.
- Category navigation.
- Editorial hero.
- Local agenda and information panel.
- Footer trust links.
- Empty ad slots are not shown as broken placeholders.

Narrative:

```text
Ana sayfa bir haber vitrini gibi kurgulandi: manset, son dakika, kategori akisi, yerel bilgi panosu ve kurumsal linkler tek yuzeyde toplaniyor.
```

Optional:

- Show dark mode briefly, then return to light mode if preferred.
- Show mobile only if asked; critical public mobile smoke passed, but full mobile QA remains a separate round.

### 2. Category Page

URL:

```text
/kategori/gundem
```

Show:

- Category list is reachable.
- Cards are readable.
- Navigation remains consistent.

Narrative:

```text
Kategori sayfalari haber arsivini konu bazli gezilebilir hale getiriyor. Gundem, siyaset, ekonomi ve diger ana basliklar ayni yapi uzerinden calisiyor.
```

### 3. IHA News Detail

Preferred URL:

```text
/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu
```

Show:

- Title.
- Image or fallback.
- Category/date/source meta.
- Main body text.
- Share/related surfaces if visible.

Narrative:

```text
IHA'dan gelen haberlerde yalniz baslik ve ozet degil, ana haber metninin de detay sayfasinda gorunmesi hedeflendi. Su anki forward monitor penceresinde bos veya zayif govde riski gorunmuyor.
```

Do not say:

```text
Gecmisteki tum arsiv kusursuz tamamlandi.
```

Safer:

```text
Ilk canli hedef yeni gelen haberlerin duzenli, tam govdeli ve izlenebilir kalite sinyaliyle yayinlanmasi. Eski arsiv recovery ayri is akisi olarak tutuldu.
```

### 4. Static Trust Pages

Open:

```text
/sayfa/hakkimizda
/sayfa/gizlilik-politikasi
/sayfa/kvkk-aydinlatma
/sayfa/cerez-politikasi
```

Show:

- Pages return 200.
- They are not empty.
- Footer/trust navigation is consistent.

Narrative:

```text
Footer uzerinden erisilen kurumsal ve yasal sayfalar guven hissi icin kritik. Bu sayfalar 404 donmuyor ve icerik yuzeyi bos degil.
```

### 5. Contact Page

URL:

```text
/iletisim
```

Show:

- Contact information.
- Form fields.
- Clean layout.

Narrative:

```text
Iletisim sayfasi okur geri bildirimi ve kurumsal iletisim icin duzenli bir yuzey sunuyor. Formun alici e-posta adresi admin ayariyla yonetilecek sekilde kurgulandi.
```

Do not submit unless real mail settings and recipient are intentionally being tested.

If not submitting:

```text
Form yuzeyini burada gosteriyorum; gercek SMTP gonderim kaniti canli sunucu ve alici e-posta bilgileriyle alinacak.
```

## Advertising Demo Notes

Show public behavior:

- Empty/misconfigured ad slots do not render broken boxes.
- Manual banner and Google AdSense are both supported by the admin model.
- Real ad creatives and AdSense IDs must be entered by admin/operator.

Admin route:

```text
/admin/advertisements
/admin/integration-settings
```

Narrative:

```text
Reklam altyapisi iki modele ayrildi: manuel banner ve Google AdSense. Manuel reklamda gorsel, hedef link, slot, tarih araligi ve sira yonetiliyor. AdSense tarafinda global Client ID ve kayit bazli Slot ID gerekiyor. Eksik reklam kaydi public sitede bos kutu uretmiyor.
```

Important caveat:

```text
Reklam veren, kampanya, CTR raporu, CSV/PDF rapor ve otomatik UTM gibi ticari operasyonlar v1 canli blocker degil; backlog'a kaydedildi.
```

## Optional Admin Preview

Only show admin if the customer wants operational detail.

Safe flow:

1. Dashboard
2. News Articles
3. IHA Health
4. IHA Sync Logs
5. Advertisements
6. Pages
7. Media Library
8. General Settings
9. Integration Settings

Narrative:

```text
Admin panel haber yonetimi, statik sayfa yonetimi, medya, reklamlar, ayarlar ve IHA operasyon takibi icin ayrildi. IHA kaynakli haberlerde katalog butunlugunu bozacak toplu veya silme aksiyonlari sinirlandi.
```

Safe admin actions:

- Browse lists.
- Open records for inspection.
- Show IHA Health summary.
- Show Sync Logs list.
- Show ad management fields.
- Show settings pages without revealing secrets.

Avoid:

- Creating or deleting live content during demo.
- Bulk publish/archive/category actions.
- Force delete.
- Opening raw `.env`.
- Showing passwords, tokens, API keys.
- Running `iha:sync` unless specifically requested.
- Showing legacy layout manager.

## If Something Goes Wrong

### Homepage stale or odd

Run outside screen share:

```powershell
php artisan view:clear
php artisan cache:clear
```

Then reload.

### Random IHA detail has missing body

Say:

```text
Bazi eski importlar ayri recovery kapsamindaydi. Canli hedefimiz yeni IHA cekimlerinin kalite kapisindan gecerek tam govdeli yayinlanmasi. Simdi son kontrol edilen guncel IHA ornegini aciyorum.
```

Then open a fresh monitor sample.

### Contact mail is questioned

Say:

```text
Form yuzeyi ve alici ayari hazir. Gercek e-posta gonderim kanitini canli SMTP ve sunucu ortaminda alacagiz.
```

### Customer asks if it can go live immediately

Say:

```text
Kod ve lokal predeploy tarafi hazir. Canliya alim karari icin Hetzner uzerinde cron, queue worker, veritabani, SSL ve IHA cekim kanitlarini almamiz gerekiyor. Bu adimi gecmeden production-ready demem dogru olmaz.
```

### Customer asks about automatic IHA ingestion

Say:

```text
Mimari bu bilgisayara bagli degil. Sunucuda cron Laravel scheduler'i tetikleyecek, IHA sync isi queue'ya dusecek ve worker bunu isleyecek. Boylece haber cekimi masaustu bilgisayara bagli kalmadan calisacak.
```

## Final Demo Checklist

| Item | Status |
|---|---|
| Local server opens homepage | PASS |
| `deploy:verify` passes | PASS |
| `iha:monitor-forward` has `quality_risk=no` | PASS |
| Latest IHA sample detail has body text | PASS |
| Static trust pages return 200 | PASS |
| Contact page opens cleanly | PASS |
| Public mobile critical smoke has no P0/P1 | PASS WITH CAVEAT |
| Admin operations smoke has no P0/P1 | PASS WITH CAVEAT |
| Advertisement v1 readiness is documented | PASS |
| Browser tabs are preloaded in planned order | MANUAL BEFORE MEETING |
| Terminal/secrets windows are hidden before screen sharing | MANUAL BEFORE MEETING |
| Hetzner production evidence limitation is ready to explain | PASS |

## Related Current Reports

- `docs/reports/ADH-GO-LIVE-REMAINING-SCOPE-2026-05-11.md`
- `docs/reports/ADH-MOBILE-CRITICAL-SMOKE-2026-05-11.md`
- `docs/reports/ADH-ADMIN-MOBILE-OPERATIONS-SMOKE-2026-05-11.md`
- `docs/reports/ADH-ADVERTISING-READINESS-2026-05-11.md`
- `docs/operations/ADH-HETZNER-DEPLOY-PREP-CHECKLIST-2026-05-11.md`
