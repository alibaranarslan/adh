# ADH Instagram Production Runbook - 2026-05-13

## Amaç

ADH'de yayınlanan haberlerin Instagram Business hesabına otomatik görsel post olarak
paylaşılması için production ortamında gereken ayar, servis, kontrol ve kanıt adımlarını
tek yerde toplamak.

Bu runbook, lokal geliştirme makinesini production kanıtı saymaz. Canlı karar Hetzner
sunucusunda public HTTPS URL, queue worker, Meta Graph API ve admin izleme kanıtlarıyla
verilir.

## Sistem Özeti

- Tetikleyici: `NewsArticleObserver`
- Kayıt tablosu: `social_publications`
- Queue job: `PublishToInstagramJob`
- Queue adı: `instagram`
- Servis: `InstagramService`
- Admin ekranı: `admin/social-publications`
- Ayar ekranı: `admin/integration-settings`
- Format: Instagram feed image post
- Creative: 1080x1080 JPEG, haber görseli, koyu gradient, kısa başlık, ADH marka bandı
- Caption: başlık, kısa özet, public haber linki, kategori/tag bazlı hashtag

## Production .env Gereksinimleri

Zorunlu:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adiyamandijitalhaber.com.tr
QUEUE_CONNECTION=database
DB_QUEUE=default

INSTAGRAM_ENABLED=true
INSTAGRAM_ACCESS_TOKEN=...
INSTAGRAM_BUSINESS_ACCOUNT_ID=...
INSTAGRAM_GRAPH_VERSION=v24.0
```

Önemli notlar:

- `APP_URL` mutlaka public HTTPS domain olmalıdır.
- Meta, görseli ADH sunucusundan URL ile çeker; lokal veya HTTP URL kabul edilmez.
- `INSTAGRAM_ENABLED=false` ise sistem yayın yapmaz, job çalıştığında kayıt izlenebilir
  şekilde `skipped` kalır.
- Token ve business account id admin panelde ayarlanabilir; `.env` fallback'i korunur.

## Meta Business Gereksinimleri

Canlı publish için müşteri tarafında şu koşullar doğrulanmalıdır:

- Instagram hesabı Business veya Creator hesaptır.
- Instagram hesabı ilgili Facebook Page'e bağlıdır.
- Meta App, ilgili Business varlığına erişebilir.
- Token uzun ömürlü veya production için yenileme süreci yönetilebilir durumdadır.
- Token, Instagram content publishing yetkisine sahiptir.
- Kullanılan `INSTAGRAM_BUSINESS_ACCOUNT_ID`, bağlı IG business account id'sidir.

Meta tarafı tamamlanmadan kod hazır olsa bile canlı yayın kanıtı alınamaz.

## Deploy Adımları

1. Kod deploy edilir.
2. Composer bağımlılıkları kurulur.

```bash
composer install --no-dev --optimize-autoloader
```

3. Migration çalıştırılır.

```bash
php artisan migrate --force
```

4. Storage link doğrulanır.

```bash
php artisan storage:link
```

5. Cache/optimize uygulanır.

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Queue worker Supervisor veya systemd ile çalıştırılır.

```bash
php artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
```

7. Scheduler sadece Laravel scheduler'ı çalıştırır.

```cron
* * * * cd /var/www/haber-sitesi && php artisan schedule:run >> /dev/null 2>&1
```

## Supervisor Örneği

```ini
[program:adh-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/haber-sitesi/artisan queue:work database --queue=default,analytics,instagram --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/haber-sitesi/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Kanıt komutları:

```bash
supervisorctl status
php artisan queue:failed
```

## Canlı Smoke Test

Kontrollü bir test haberinde şu sıra izlenir:

1. Admin panelde Instagram otomasyon aktif edilir.
2. `admin/integration-settings` ekranında token/account readiness kontrol edilir.
3. Görseli olan bir test haber `published` yapılır.
4. `social_publications` kaydı oluştuğu doğrulanır.
5. Worker job'u tüketir.
6. Creative dosyası oluşur.
7. Creative URL public HTTPS olarak açılır.
8. Publication status `published` olur.
9. `media_id` kaydedilir.
10. Instagram hesabında post görünür.

Veritabanı kanıt sorguları:

```bash
php artisan tinker
```

```php
App\Models\SocialPublication::query()
    ->where('platform', 'instagram')
    ->latest()
    ->first();
```

Admin kanıtları:

- `admin/social-publications` listesinde kayıt görünür.
- `status=published` görünür.
- `media_id` doludur.
- Creative preview kırık değildir.
- Caption Türkçe karakterleri korur.

## Hata Durumları

`skipped`:

- Instagram otomasyonu kapalıdır.
- Token/account id eksiktir.
- Haber artık published değildir.
- Haber görseli yoktur.

`failed`:

- Meta `/media` container oluşturma çağrısı başarısızdır.
- Meta `/media_publish` çağrısı başarısızdır.
- Creative üretimi sırasında beklenmeyen hata oluşmuştur.

Operatör aksiyonu:

- `admin/social-publications` üzerinden hata mesajı okunur.
- Ayar/token/görsel sorunu düzeltilir.
- Retry action çalıştırılır.

## Kalite Kapıları

Canlıya hazır kabul için aşağıdaki maddeler PASS olmalıdır:

- `php artisan migrate:status` içinde `create_social_publications_table` çalışmış.
- `admin/social-publications` 200 dönüyor.
- `admin/integration-settings` Instagram toggle ve credential alanlarını gösteriyor.
- `queue:work` worker `instagram` kuyruğunu dinliyor.
- `queue:failed` güncel açıklanamayan hata göstermiyor.
- Test creative URL'si public HTTPS olarak açılıyor.
- Test publication `published` ve `media_id` dolu.
- Instagram hesabında post görünür.

## Kapsam Dışı

V1 kapsamında olmayan işler:

- Story paylaşımı
- Reels paylaşımı
- Çoklu görsel carousel
- Admin onay kuyruğu
- Otomatik token yenileme paneli
- Instagram yorum/DM yönetimi

Bu işler ayrı ürün fazı olarak ele alınmalıdır.

