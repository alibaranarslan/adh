# ADH Modernizasyon ve İHA Operasyon Uygulama Raporu

Tarih: 2026-07-10

## Kapsam

Bu turda ADH için P0 İHA tazelik problemi, admin sağlık görünürlüğü, Layout Studio dil/yerellik çizgisi, haber detay güven sinyali ve mobil cookie görünürlüğü ele alındı.

## Ana Bulgular

- Başlangıç durumunda yayınlanmış haber sayısı 20 idi ve son yayın tarihi 2026-06-29 görünüyordu.
- `iha:sync` normal çalıştırıldığında `IHA sync zaten calisiyor. Atlaniyor.` çıktısı veriyordu.
- DB'de `running` durumunda kalan İHA logu sonraki senkronları bloke ediyordu.
- Force-inline test çalıştı ve İHA bağlantısının aslında erişilebilir olduğunu gösterdi.
- Sorunun ana nedeni shared-hosting ortamında queue worker aksarsa `running` logun uzun süre kilitli kalabilmesiydi.

## Uygulanan Düzeltmeler

- `iha:sync` stale-running eşiği 2 saatten yapılandırılabilir 20 dakikaya indirildi.
- Scheduler, Turhost/shared-hosting için `*/10 * * * * php artisan iha:sync --inline` modeline çekildi.
- Kısa ömürlü database queue worker varsayılan açık hale getirildi.
- `iha:monitor-forward` 15 dakikada bir, security ingest audit üretim ortamında 30 dakikada bir çalışacak şekilde sıklaştırıldı.
- İHA Sağlığı sayfası temiz Türkçe metinlerle yeniden düzenlendi.
- Admin manuel İHA aksiyonu sınırlı inline test senkronuna dönüştürüldü.
- Dashboard sistem uyarıları 20 dakika running, 30 dakika tazelik uyarısı, 60 dakika kritik eşiklerine göre güncellendi.
- Layout Studio modül adları ve seeder değerleri "Güvenilir Yerel Haber" çizgisine uygun hale getirildi.
- Haber detay sayfasına "Kaynak ve Şeffaflık" kutusu eklendi.
- Cookie bildirimi mobilde daha düşük yükseklik ve blur ile ana okuma akışını daha az kapatacak hale getirildi.

## Kanıt

- `php artisan iha:sync --force --inline --limit=3`: success, 3 yeni haber.
- Sonrasında DB durumu: published=69, iha=69, jobs=0, failed_jobs=0.
- Son başarılı İHA sync: `articles_fetched=49`, `articles_created=0`, `articles_skipped=49`, `status=success`.
- `php artisan iha:monitor-forward --limit=20`: `health=healthy`, `quality_risk=no`, `freshness_minutes=0`.
- `php artisan adh:security-ingest-audit --freshness-minutes=120`: `SECURITY_INGEST_AUDIT status=pass`.
- `npm run build`: başarılı.
- Hedef testler: 21 test, 246 assertion, başarılı.
- Admin language quality: 2 test, 127 assertion, başarılı.
- In-app browser desktop smoke: home ve haber detay 200, console error yok, horizontal overflow yok.
- Mobil screenshot fallback: Playwright CLI `--channel=chrome`.

## Görsel Kanıt Dosyaları

- `C:\Users\Ali\AppData\Local\Temp\adh-modernization-smoke\adh-home-mobile-after.png`
- `C:\Users\Ali\AppData\Local\Temp\adh-modernization-smoke\adh-article-mobile-after.png`
- In-app browser viewport screenshots:
  - `.playwright-mcp\adh-home-desktop-after.png`
  - `.playwright-mcp\adh-article-desktop-after.png`

## Canlı/Turhost Notu

Turhost cron için gereken ana komut değişmedi:

```bash
* * * * * cd /home/USER/apps/adh && /usr/bin/php artisan schedule:run >> storage/logs/cron.log 2>&1
```

Uygulama içi scheduler artık bu tek cron üzerinden İHA'yı 10 dakikada bir inline çalıştırır ve queue worker'ı dakikada bir kısa süreli işletir. Production `.env` içinde şu değerlerin korunması gerekir:

```env
QUEUE_CONNECTION=database
SCHEDULE_QUEUE_WORKER=true
IHA_SYNC_INTERVAL=10
IHA_STALE_RUNNING_MINUTES=20
```

İHA credentials rapora yazılmadı; değerler `.env` veya admin Entegrasyon Ayarları üzerinden girilmelidir.
