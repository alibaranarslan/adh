# ADH Predeploy Readiness Recheck - 2026-05-09

## Amaç

Hetzner sunucusuna bağlanmadan önce ADH production ingest/deploy hazırlığını kod, env şablonu, scheduler, queue ve operasyon dokümanları üzerinden doğrulamak.

## Kapsam

- IHA production ingest komut davranışı
- Laravel scheduler tanımı
- Queue worker hedef modeli
- Production env şablonu
- Deploy verification komutu
- IHA evidence runbook ve deploy checklist
- Lokal runtime kanıtı ile production readiness kanıtının ayrımı

## Kod Durumu

### IHA sync komutu

Dosya: `app/Console/Commands/SyncIhaNewsCommand.php`

- `php artisan iha:sync` default olarak `SyncIhaNewsJob` işini `default` queue'ya bırakıyor.
- `--inline` sadece manuel/debug yolu olarak kalıyor.
- Yeni sync başlatmadan önce 2 saati aşmış `running` loglar `failed` yapılıyor.
- Stale olmayan aktif `running` log varsa yeni sync atlanıyor.
- Queue dispatch hatası olursa log `failed` olarak kapanıyor.

### Scheduler

Dosya: `routes/console.php`

Scheduler tanımı production modeline uygun:

```text
*/15 * * * *  php artisan iha:sync
0 * * * *     php artisan iha:monitor-forward --limit=20
5,20,35,50 * * * * php artisan iha:refresh-images
```

`schedule:list` çıktısında `iha:sync --inline` görünmedi.

### Queue worker

Dokümante edilen production worker komutu:

```bash
php artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
```

Bu karar mevcut risk profiline uygun:

- IHA job `default` queue'da.
- Analytics jobları `analytics` queue'da.
- Instagram jobları `instagram` queue'da.
- Worker özel `iha-sync` kuyruğuna bağlı değil; production'da tüketilmeyen queue riski azaltılmış.

## Production Env Şablonu

Dosya: `.env.production.example`

Bu recheck sırasında iki hizalama yapıldı:

- `IHA_REQUEST_DELAY=30` -> `IHA_REQUEST_DELAY=1`
- `LOG_CHANNEL=daily` -> `LOG_CHANNEL=stack`
- `LOG_STACK=daily,sentry` eklendi

Kontrol edilen production değerleri:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adiyamandijitalhaber.com.tr
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
DB_QUEUE=default
IHA_SYNC_INTERVAL=15
IHA_REQUEST_DELAY=1
IHA_MIN_BODY_LENGTH=280
LOG_CHANNEL=stack
LOG_STACK=daily,sentry
```

Not: `.env.production.example` bu çalışma anında git açısından untracked görünüyor, fakat Hetzner predeploy dokümanı bu dosyayı referansladığı için içerik hizalaması kritik kabul edildi.

## Operasyon Dokümanları

Doğrulanan dosyalar:

- `C:\nwp0203\docs\operations\ADH-HETZNER-PREDEPLOY-PACKAGE-2026-05-09.md`
- `C:\nwp0203\docs\operations\ADH-IHA-PRODUCTION-EVIDENCE-RUNBOOK-2026-05-09.md`
- `C:\nwp0203\docs\operations\deploy_verification_checklist.md`
- `C:\nwp0203\docs\operations\queue_and_scheduler_operations.md`

Dokümanlar şu ana kararla uyumlu:

- Production cron sadece `schedule:run` çalıştırmalı.
- Production cron doğrudan `iha:sync --inline` çalıştırmamalı.
- Queue worker Supervisor/systemd ile sürekli çalışmalı.
- Worker `default,analytics,instagram` kuyruklarını dinlemeli.
- Local Windows Scheduled Task production readiness kanıtı sayılmamalı.
- Hetzner kanıtları sunucuda `crontab`, `supervisorctl/systemd`, `iha_sync_logs`, `queue:failed`, `iha:monitor-forward` ve public detay body kontrolleriyle alınmalı.

## Lokal Komut Kanıtları

### Syntax

```powershell
php -l app\Console\Commands\SyncIhaNewsCommand.php
```

Sonuç:

```text
No syntax errors detected
```

### IHA acceptance paketi

```powershell
php artisan test tests\Feature\Commands\SyncIhaNewsCommandTest.php tests\Unit\Services\IhaSyncTriggerServiceTest.php tests\Feature\Jobs\IhaSyncLogStatusTest.php tests\Feature\Commands\MonitorIhaForwardIngestCommandTest.php tests\Feature\Commands\EnrichIhaSourceUrlsCommandTest.php
```

Sonuç:

```text
19 passed
118 assertions
```

### Daha geniş IHA paketi

```powershell
php artisan test tests\Feature\Commands\SyncIhaNewsCommandTest.php tests\Feature\Commands\MonitorIhaForwardIngestCommandTest.php tests\Feature\Jobs\SyncIhaNewsJobTest.php tests\Feature\Jobs\SyncIhaNewsLimitTest.php tests\Feature\Jobs\IhaSyncLogStatusTest.php tests\Unit\Services\IhaApiServiceTest.php tests\Unit\Services\IhaSyncTriggerServiceTest.php
```

Sonuç:

```text
19 passed
129 assertions
```

### Deploy verify

```powershell
php artisan deploy:verify --base-url=http://localhost:8010
```

Sonuç:

```text
All checks passed.
```

Doğrulanan başlıklar:

- App boots
- App URL configured
- Production debug guard
- Database connection
- Log directory writable
- Cache read/write
- Queue configuration
- Session cookie security
- Health endpoint
- Homepage responds

### Scheduler

```powershell
php artisan schedule:list
```

IHA ile ilgili görünen satırlar:

```text
*/15       * * * *  php artisan iha:sync
0          * * * *  php artisan iha:monitor-forward --limit=20
5,20,35,50 * * * *  php artisan iha:refresh-images
```

`iha:sync --inline` görünmedi.

### Forward monitor

```powershell
php artisan iha:monitor-forward --limit=20
```

Sonuç özeti:

```text
health=healthy
quality_risk=no
empty_content=0
weak_body=0
short_body=0
body_depth_ratio=1.00
```

## Kalan Riskler

### Sunucu kanıtı henüz alınmadı

Bu recheck bağlantısız yapıldı. Aşağıdaki kanıtlar ancak Hetzner üzerinde alınabilir:

- `crontab -l`
- `sudo supervisorctl status` veya `systemctl status adh-worker`
- Worker komutunun gerçekten `default,analytics,instagram` dinlediği
- Production `.env` değerlerinin gerçek sunucuda doğru girildiği
- Controlled queued `php artisan iha:sync --limit=3` testinin worker ile tamamlandığı
- Public en son 3 IHA detay sayfasında gövde metni olduğu

### Lokal queue backlog production kanıtı değildir

Lokal makinede pending queue backlog gözlendi. Bu durum Hetzner production readiness için karar verdirici değildir; production worker kanıtı sunucuda alınmalıdır.

### `.env.production.example` untracked görünüyor

Dosya deploy dokümanlarında referanslandığı için production hazırlığında kullanılabilir, ancak repository teslim/commit kararında bu dosyanın takip edilip edilmeyeceği netleştirilmeli.

## Karar

Kod ve dokümanlar bağlantısız predeploy açısından production modeline uygun. Hetzner bağlantısı öncesi P0/P1 blocker görülmedi.

Canlıya alma için go/no-go kararı, sunucuda alınacak şu kanıtlar tamamlanmadan verilmemeli:

- Production `.env` değerleri secrets basılmadan doğrulandı
- Migrationlar MySQL üzerinde çalıştı
- `/health` ve homepage Nginx/SSL üzerinden 200 dönüyor
- Cron `schedule:run` çalıştırıyor
- Worker `RUNNING` ve doğru queue setini dinliyor
- Queued IHA sync tamamlanıyor
- `iha:monitor-forward` sağlıklı
- Son 3 public IHA haber detayında gövde metni var
- `queue:failed` ve log sweep temiz
