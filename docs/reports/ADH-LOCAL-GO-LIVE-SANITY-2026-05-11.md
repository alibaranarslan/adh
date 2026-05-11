# ADH Local Go-Live Sanity - 2026-05-11

## Karar

Local predeploy kod ve public/admin fonksiyonel yuzey sonucu: **PASS**

Hetzner production go/no-go sonucu: **BLOCKED**

Sebep: Sunucu erisimi henuz yok. Production cron, Supervisor/systemd queue worker, Nginx, MySQL, real `.env`, real AdSense/IHA credential ve live domain kanitlari alinmadan canliya hazir karari verilmemeli.

## PASS Kanitlari

### Public site

HTTP smoke:

- `/` -> 200
- `/kategori/gundem` -> 200
- `/iletisim` -> 200
- `/sayfa/hakkimizda` -> 200
- `/sayfa/gizlilik-politikasi` -> 200
- `/sayfa/kvkk-aydinlatma` -> 200
- `/sayfa/cerez-politikasi` -> 200
- Son IHA haber detayi -> 200

Son IHA haber detayi:

- `mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu`
- Status: 200
- Govde/metin bolumu gorunur.

### IHA ingest / kalite

`schedule:list` kaniti:

- `php artisan iha:sync`
- Cron: `*/15 * * * *`
- `--inline` yok.
- `php artisan iha:monitor-forward --limit=20`
- `php artisan iha:refresh-images`

Temizlik:

- Laravel demo `inspire` schedule'i production yuzeyinden kaldirildi.

Monitor:

- `health=healthy`
- `quality_risk=no`
- `empty_content=0`
- `weak_body=0`
- `short_body=0`
- `body_depth_ratio=1.00`

Not:

- Local son sync log `running`; local makinede queue worker surekli calismadigi icin `jobs` tablosunda backlog olusmus durumda. Bu production kod modeli icin blocker degil, ancak production worker kaniti icin go/no-go sartidir.

### Queue / failed jobs

- `php artisan queue:failed` -> No failed jobs found.
- Local `jobs` backlog: 1218
- Yorum: Local ortam production runtime kaniti sayilmamalidir. Hetzner'da Supervisor/systemd worker calismadan IHA sistemi production-ready sayilmaz.

### Reklam sistemi

Son fonksiyonel smoke sonucu:

- Manual header banner render edildi.
- Mobile header kreatif render edildi.
- Home `between-news` ve `footer` slotlari render edildi.
- Detail `article-top` manual banner render edildi.
- Detail `article-bottom` AdSense slotu `data-ad-client` ve `data-ad-slot` ile render edildi.
- `api/ad-impression/{ad}` ve `api/ad-click/{ad}` endpointleri 200 dondu ve sayac artirdi.
- Smoke sonrasi `ADH_SMOKE_*` kayitlari, test `ca-pub` ayari ve test gorselleri temizlendi.

### Dark mode

Son smoke sonucu:

- Public ana sayfa, haber detay, iletisim, statik sayfalar PASS.
- Admin dashboard, listeler, ayarlar, IHA ekranlari PASS.
- Admin create/edit formlar PASS.
- Medya Kutuphanesi dark mode buton kacaklari duzeltildi.

### Admin

Gecerli test kanitlari:

- Filament full suite daha once ayni turda PASS: 58 tests / 578 assertions.
- Bu final sanity turunda hedefli admin/reklam/settings testleri PASS.

## Test Kanitlari

### IHA command/job/monitor paketi

```bash
php artisan test tests\Feature\Commands\SyncIhaNewsCommandTest.php tests\Feature\Commands\MonitorIhaForwardIngestCommandTest.php tests\Feature\Jobs\SyncIhaNewsJobTest.php tests\Feature\Jobs\SyncIhaNewsLimitTest.php tests\Feature\Jobs\IhaSyncLogStatusTest.php tests\Unit\Services\IhaApiServiceTest.php tests\Unit\Services\IhaCategoryMapperTest.php tests\Unit\Services\IhaSyncTriggerServiceTest.php
```

Sonuc:

- 23 passed
- 143 assertions

### Public/admin hedefli sanity paketi

```bash
php artisan test tests\Feature\Public\PublicPagesTest.php tests\Feature\Filament\AdminSettingsOperationsFinalSmokeTest.php tests\Feature\Filament\IhaHealthPageTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php
```

Sonuc:

- 26 passed
- 201 assertions

### Reklam final paketi

```bash
php artisan test tests\Feature\Public\PublicPagesTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Filament\AdminMediaUploadHardeningTest.php
```

Sonuc:

- 26 passed
- 181 assertions

### Deploy verify

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Sonuc:

- PASS
- App boots
- Database connection
- Cache read/write
- Queue configuration
- Health endpoint
- Homepage responds

## Local Ortam Notlari

Local `.env`:

- `APP_ENV=local`
- `APP_DEBUG=true`
- `DB_CONNECTION=sqlite`
- `QUEUE_CONNECTION=database`
- `IHA_SYNC_INTERVAL=15`

Bu degerler local gelistirme icin kabul edilebilir. Production icin dogrulanmasi gerekenler:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `QUEUE_CONNECTION=database`
- `DB_QUEUE=default`
- `IHA_SYNC_INTERVAL=15`
- `IHA_MIN_BODY_LENGTH=280`

## BLOCKED - Hetzner Go/No-Go

Sunucu erisimi gelince alinacak zorunlu kanitlar:

- `git rev-parse HEAD`
- `php -v`
- `composer install --no-dev`
- `npm run build`
- `php artisan migrate --force`
- `php artisan storage:link`
- `php artisan optimize`
- `php artisan schedule:list`
- `crontab -l`
- `supervisorctl status` veya systemd worker status
- `php artisan queue:failed`
- `php artisan iha:monitor-forward --limit=20`
- Public son 3 IHA haber detayinda govde metni
- Public reklam slotlari ve AdSense real client/slot kontrolu
- Google AdSense real domain/policy/ads.txt kontrolu

## Sonuc

Local kod ve fonksiyonel yuzeyler canliya hazirlik icin yeterli seviyede.

Canliya alma karari henuz verilemez; karar Hetzner production runtime kanitlari alindiktan sonra verilmeli.
