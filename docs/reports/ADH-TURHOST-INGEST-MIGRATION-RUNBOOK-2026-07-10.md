# ADH Turhost IHA Ingest Migration Runbook - 2026-07-10

## Amaç

Hetzner sunucusuna erişim olmadan ADH uygulamasını Turhost ortamına taşımak ve IHA haber çekimini düzenli, izlenebilir ve queue uyumlu biçimde yeniden çalıştırmak.

## Mevcut Yerel Bulgular

- `php artisan schedule:list` içinde `iha:sync` her 15 dakikada bir planlı.
- `iha:sync` production modelinde doğrudan uzun işlem yapmıyor; `SyncIhaNewsJob` işini `default` kuyruğuna bırakıyor.
- Queue worker çalışmadığında `jobs` tablosunda `SyncIhaNewsJob` birikiyor ve `iha_sync_logs` kaydı `running` kalabiliyor.
- Yerelde `php artisan queue:work database --queue=default,analytics,instagram --sleep=1 --tries=3 --stop-when-empty` çalıştırılınca birikmiş işler işlendi.

## Turhost İçin İki Model

### Model A - VPS / SSH / Supervisor Varsa

Bu tercih edilen modeldir.

Cron:

```bash
* * * * * cd /home/USER/PROJECT && /usr/bin/php artisan schedule:run >> storage/logs/cron.log 2>&1
```

Worker:

```bash
php artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
```

`.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE=default
SCHEDULE_QUEUE_WORKER=false
IHA_SYNC_INTERVAL=15
IHA_MIN_BODY_LENGTH=280
```

### Model B - Shared Hosting / Supervisor Yoksa

Bu fallback model Turhost panel cron'u ile çalışır.

Tek cron:

```bash
* * * * * cd /home/USER/PROJECT && /usr/bin/php artisan schedule:run >> storage/logs/cron.log 2>&1
```

`.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE=default
SCHEDULE_QUEUE_WORKER=true
IHA_SYNC_INTERVAL=15
IHA_MIN_BODY_LENGTH=280
```

Bu modda Laravel scheduler her dakika kısa ömürlü queue worker çalıştırır:

```bash
php artisan queue:work database --queue=default,analytics,instagram --sleep=1 --tries=3 --max-time=50 --stop-when-empty
```

Bu yaklaşım kalıcı daemon worker kadar güçlü değildir, fakat shared hosting ortamında IHA sync, çeviri, analitik ve Instagram işlerinin kuyrukta kalmasını engelleyen pratik çözümdür.

## Turhost Deploy Kontrol Listesi

1. PHP 8.2+ seçili olmalı.
2. Composer bağımlılıkları kurulmalı.
3. MySQL veritabanı ve kullanıcı oluşturulmalı.
4. `.env` production değerleri girilmeli.
5. `APP_URL=https://adiyamandijitalhaber.com.tr` olmalı.
6. `APP_ENV=production`, `APP_DEBUG=false` olmalı.
7. `QUEUE_CONNECTION=database`, `DB_QUEUE=default` olmalı.
8. IHA kimlik bilgileri girilmeli.
9. `php artisan migrate --force` çalışmalı.
10. `php artisan storage:link` çalışmalı.
11. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` çalışmalı.
12. Cron her dakika `schedule:run` çalıştırmalı.
13. Supervisor yoksa `SCHEDULE_QUEUE_WORKER=true` açılmalı.
14. Public document root Laravel `public` dizinine bakmalı.
15. SSL aktif ve geçerli olmalı.

## Canlı Doğrulama Komutları

```bash
php artisan schedule:list
php artisan iha:sync --limit=3
php artisan queue:work database --queue=default,analytics,instagram --stop-when-empty --tries=3
php artisan iha:monitor-forward --limit=20
php artisan adh:security-ingest-audit --freshness-minutes=120
php artisan queue:failed
```

## Kabul Kriterleri

- `/` 200 döner.
- `/admin` erişilebilir olur.
- `schedule:list` içinde `iha:sync` görünür.
- `schedule:list` içinde shared hosting modunda `queue:work ... --stop-when-empty` görünür.
- `jobs` tablosunda eski `SyncIhaNewsJob` birikmez.
- `iha:monitor-forward --limit=20` çıktısında:
  - `quality_risk=no`
  - `empty_content=0`
  - `weak_body=0`
  - `short_body=0`
- Son gelen IHA haberlerinin detay sayfasında ana metin görünür.

## Riskler

- Turhost shared hosting cron minimum sıklığı 1 dakikadan uzun ise haber çekimi gecikir.
- Shared hosting PHP process timeout kısa ise queue worker `--max-time=50` daha aşağı çekilmelidir.
- `proc_open` veya CLI PHP kısıtlıysa scheduler/queue panel cron ile çalışmayabilir; bu durumda VPS/Supervisor gerekir.
- Gerçek production geçişinde IHA credential, SSL, domain document root ve cron kanıtı alınmadan sistem hazır sayılmamalıdır.

