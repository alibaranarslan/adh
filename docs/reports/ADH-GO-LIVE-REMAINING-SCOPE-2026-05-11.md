# ADH Canlıya Hazırlık Kalan İş Sırası - 2026-05-11

## Karar Özeti

Durum: **LOCAL PREDEPLOY DEVAM EDEBİLİR / PRODUCTION GO-NO-GO HETZNER KANITINA BAĞLI**

Reklam v1 kapsamı kapatıldı. Bundan sonraki canlıya hazırlık sırası şu dört ana başlığa indirildi:

1. IHA ingest ve içerik kalite kanıtı
2. Admin panel genel sağlık ve kritik CRUD kontrolü
3. Public site smoke ve müşteri gözüyle kırık yüzey kontrolü
4. Hetzner production cron, queue, env ve public canlı kanıtları

Production canlı kararı, Hetzner sunucusunda cron + queue worker + kontrollü IHA sync + public haber detay gövde metni kanıtları alınmadan verilmemeli.

## Kapsam Dışı / Backlog Ayrımı

Canlıya çıkış blocker'ı olmayan işler:

- Reklam veren modeli
- Kampanya modeli
- CTR/performance raporu
- CSV/PDF reklam raporu dışa aktarımı
- Otomatik UTM üretimi
- Reklam dashboard widget'ı
- Geçmiş arşivdeki tüm eski IHA haberlerini kusursuz tamamlama
- Full mobil QA'nın her breakpoint için detaylı turu

Bu işler ayrı backlog olarak tutulur; mevcut v1 canlı hazırlık hedefini bloke etmez.

## 1. IHA Ingest Kapanış Kontrolü

Amaç: Bundan sonra gelen IHA haberlerinin düzenli, tam gövdeli ve izlenebilir kalite sinyaliyle ADH'de görünmesini kanıtlamak.

Yerel/kod tarafında beklenen durum:

- `php artisan iha:sync` default queued çalışır.
- `php artisan iha:sync --inline` yalnız manuel debug/acil çalışma yoludur.
- Scheduler `iha:sync --inline` kullanmaz.
- IHA job `default` queue'dadır.
- Monitor çıktısı `empty_content`, `weak_body`, `short_body`, `body_depth_ratio`, `freshness_minutes` sinyallerini üretir.
- IHA kayıtları admin bulk/delete yollarıyla bozulamaz.

Hetzner'da alınacak zorunlu kanıt:

- `php artisan schedule:list` içinde `iha:sync` görünür, `--inline` görünmez.
- `crontab -l` her dakika `php artisan schedule:run` çalıştırır.
- Supervisor/systemd worker `default,analytics,instagram` queue setini dinler.
- Kontrollü `php artisan iha:sync --limit=3` veya queued test worker ile tamamlanır.
- `php artisan iha:monitor-forward --limit=20` sağlıklı metrik üretir.
- Public son 3 IHA haber detayında ana gövde metni görünür.

Öncelik: **P0 production kanıtı**

## 2. Admin Panel Kapanış Kontrolü

Amaç: Müşterinin kullanacağı admin yüzeyinde kritik içerik ve operasyon işlevlerinin kırık olmamasını sağlamak.

Kontrol edilecekler:

- Login, dashboard, logout
- Haber liste/create/edit akışı
- IHA haberlerinde read-only ve mutasyon koruması
- Kategori, tag, statik sayfa yönetimi
- Medya kütüphanesi ve bağlı medya silme koruması
- Reklam yönetimi, AdSense Client ID durumu, slot ölçü rehberi
- Layout Studio draft/publish ayrımı
- Genel/SEO/Email/Entegrasyon ayarlarının persist davranışı
- IHA Health ve Sync Logs ekranları
- User/role yetki sınırları
- Admin mojibake/language-quality testi

Mevcut durum:

- Reklam admin UX kapatıldı.
- Admin operations ve language-quality testleri geçiyor.
- Tam panel turu sunucu öncesi localde tekrar yapılmalı; sunucuda deployment sonrası tekrar alınmalı.

Öncelik: **P1 local kapanış + P1 production smoke**

## 3. Public Site Smoke Kontrolü

Amaç: Müşteri veya okur gözüyle kırık, boş, 404, mojibake veya güven zedeleyen yüzey kalmaması.

Kontrol edilecek sayfalar:

- Ana sayfa
- Kategori sayfaları
- Haber detay sayfaları
- Arama
- Şehir/il sayfası
- Hakkımızda
- Gizlilik Politikası
- KVKK Aydınlatma
- Çerez Politikası
- İletişim
- Footer linkleri
- Reklam slotlarının boşken gizlenmesi
- Gerçek reklam girilince header/sidebar/detail/footer render davranışı

Görsel kontrol başlıkları:

- Mojibake yok
- Placeholder/bozuk metin yok
- Kritik boş blok yok
- Kırık görsel yok
- Header/kategori taşması yok
- Haber detayında ana metin var
- Footer ve bilgi panosu düzenli
- Mobilde kritik üst üste binme yok

Öncelik: **P1 müşteri sunumu ve P1 production smoke**

## 4. Hetzner Production Go/No-Go

Amaç: Kodun lokal makineye bağlı olmadan Linux sunucu cron + queue mimarisiyle çalıştığını kanıtlamak.

Sunucu erişimi gelince zorunlu kontrol:

- SSH erişimi
- App dizini, branch/commit
- PHP/Composer/Node sürümleri
- MySQL bağlantısı
- `.env` var/yok kontrolü, secret yazdırmadan doğrulama
- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=database`
- `DB_QUEUE=default`
- `IHA_SYNC_INTERVAL=15`
- `IHA_MIN_BODY_LENGTH=280`
- Gerçek `IHA_USER_CODE`, `IHA_USERNAME`, `IHA_PASSWORD`
- `composer install --no-dev --optimize-autoloader`
- `npm ci && npm run build`
- `php artisan migrate --force`
- `php artisan storage:link`
- `php artisan optimize`
- Nginx site config
- Supervisor/systemd worker
- Cron schedule runner
- Laravel logs ve `queue:failed` sweep

Production go/no-go için minimum PASS:

- Homepage 200
- Admin login 200
- Static legal pages 200
- Son IHA sync success/partial, kalite riski yok veya açıklanmış
- Worker running
- Cron installed
- `queue:failed` temiz veya açıklanmış
- Son 3 IHA public detayda gövde metni var
- `APP_DEBUG=false`

Öncelik: **P0 canlı karar**

## Önerilen İş Sırası

1. **Local IHA final probe**
   - Son ingest/monitor komutları çalıştırılır.
   - Son IHA haber detayları gövde metni açısından kontrol edilir.

2. **Local admin final smoke**
   - Admin kritik ekranları ve ayar persist davranışı tekrar doğrulanır.
   - Rehber/metin/mojibake testleri çalıştırılır.

3. **Local public final smoke**
   - Ana sayfa, kategori, detay, statik sayfalar, iletişim ve footer linkleri kontrol edilir.
   - Kırık görüntü/boş blok/404 varsa kapatılır.

4. **Hetzner deploy hazırlığı**
   - Sunucu erişimi gelmeden deploy runbook ve env checklist hazır tutulur.
   - Sunucu erişimi geldiğinde production go/no-go kanıtları alınır.

5. **Production go/no-go**
   - Cron + worker + IHA sync + monitor + public detay kanıtı tamamlanır.
   - Ancak bu aşamadan sonra "canlıya hazır" kararı verilir.

## Güncel Riskler

| Risk | Durum | Karar |
|---|---|---|
| Hetzner cron/worker kanıtı yok | BLOCKED | Sunucu erişimi bekleniyor |
| Production `.env` gerçek secret değerleri doğrulanmadı | BLOCKED | Sunucu erişimi bekleniyor |
| Public canlı domain smoke yapılmadı | BLOCKED | Deploy sonrası yapılacak |
| Reklam ticari raporlama yok | BACKLOG | V1 blocker değil |
| Full mobil QA detay turu yapılmadı | BACKLOG | Kritik mobil kırıklar smoke içinde kontrol edilir |

## Kapanış Notu

Bu dosya canlıya hazırlıkta kalan işleri sıralamak içindir. Mevcut lokal kod tabanı açısından reklam v1 ve admin reklam yönetimi kapatılmıştır. Bundan sonraki en doğru adım, IHA ingest ve public haber detay gövde kanıtını son kez localde doğrulayıp ardından admin/public final smoke turuna geçmektir.

## Local IHA Final Probe - 2026-05-11

Durum: **PASS with production caveat**

Çalıştırılan kontroller:

- `php artisan iha:monitor-forward --limit=20`
- `php artisan schedule:list`
- `php artisan queue:failed`
- Son IHA haber kayıtlarında DB gövde uzunluğu kontrolü
- Son 3 IHA haber public detay HTTP 200 + başlık + gövde kesiti kontrolü
- IHA command/job/monitor test paketi

Monitor kanıtı:

```text
IHA_FORWARD_MONITOR health=healthy sync_status=running quality_risk=no quality_affected=0 freshness_minutes=16 fetched=0 created=0 updated=0 skipped=0 window=20 empty_content=0 weak_body=0 short_body=0 body_depth_ratio=1.00 generic_source_url_ratio=0.95
```

Son 20 IHA penceresi:

- `empty_content=0`
- `weak_body=0`
- `short_body=0`
- `body_depth_ratio=1.00`
- `quality_risk=no`

Son IHA haber DB gövde kanıtı:

| Slug | Content Length | Public Detail |
|---|---:|---|
| `mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu` | 2118 | 200, başlık var, gövde kesiti var |
| `mardinde-cifte-sampiyonluk-coskusu` | 1172 | 200, başlık var, gövde kesiti var |
| `ikinci-kattan-dusen-genc-hayatini-kaybetti` | 640 | 200, başlık var, gövde kesiti var |

Scheduler kanıtı:

```text
*/15 * * * *  php artisan iha:sync
0 * * * *     php artisan iha:monitor-forward --limit=20
```

`schedule:list` içinde `iha:sync --inline` görünmedi.

Failed jobs:

```text
No failed jobs found.
```

Test kanıtı:

```text
Tests: 15 passed (106 assertions)
```

Çalıştırılan paket:

```bash
php artisan test tests\Feature\Commands\SyncIhaNewsCommandTest.php tests\Feature\Commands\MonitorIhaForwardIngestCommandTest.php tests\Feature\Jobs\IhaSyncLogStatusTest.php tests\Unit\Services\IhaSyncTriggerServiceTest.php
```

Lokal caveat:

- Lokal `jobs` tablosunda pending backlog var: `default=275`, `analytics=793`.
- Pending `SyncIhaNewsJob` sayısı: `15`.
- Son sync log `running` durumda görünüyor; monitor health sağlıklı olsa da bu lokal worker çalışmaması/yarım kalan lokal scheduler etkisi olarak değerlendirilmeli.
- Bu durum Hetzner production go/no-go kanıtı yerine geçmez. Production kararında zorunlu kanıt hâlâ sunucuda çalışan cron + Supervisor/systemd queue worker + controlled queued IHA testidir.

Karar:

- IHA içerik kalite ve public detay gövde kontrolü lokal olarak geçti.
- Production canlı kararı hâlâ Hetzner cron/worker ve kontrollü queued sync kanıtına bağlı.

## Local Admin Final Smoke - 2026-05-11

Durum: **PASS**

Çalıştırılan komut:

```bash
php artisan test tests\Feature\Filament
```

Sonuç:

```text
Tests: 58 passed (578 assertions)
```

Ek kritik admin recheck:

```text
Tests: 26 passed (260 assertions)
```

Bu ek tur IHA content integrity, role permissions, advertisement CRUD ve layout/media kontrollerini tekrar çalıştırdı.

Kapsanan başlıklar:

- Admin dashboard status kartları ve hızlı aksiyonlar
- Haber yönetimi index/create/edit yüzeyleri
- IHA haberlerinin bulk publish/archive/draft/category/featured/breaking aksiyonlarıyla değiştirilememesi
- IHA edit sayfasında delete action'ın gizli olması
- Haber tag formunun gerçek `tags()` ilişkisine sync edilmesi
- Writer/editor/super_admin rol sınırları
- Writer'ın publish/featured/breaking zorlayamaması
- User resource self-delete ve son super admin delete koruması
- HeaderTheme schedule validation
- Public image upload alanlarında PDF MIME engeli
- Medya kütüphanesinde attached media delete koruması
- Media library batch loading
- General/SEO/Integration/Email settings persist ve remount davranışı
- Contact form recipient email entegrasyonu
- System alerts no-log/stale/running/failed/success-lag durumları
- Admin language-quality ve mojibake kontrolleri
- Statik sayfa admin edit -> public yansıma kontrolleri
- Protected static page delete koruması
- Reklam CRUD, AdSense Slot ID validation, Client ID hazır/eksik uyarısı
- Layout Studio preview/publish/restore ve draft'ın public base state'i mutasyona uğratmaması
- Legacy LayoutManager'ın disabled stub olarak kalması
- Analytics ve operations sayfaları
- IHA Health ve Integration Settings sayfaları

Lokal DB gözlemi:

- Roller mevcut: `writer`, `editor`, `super_admin`
- IHA haber sayısı: `261`
- Statik sayfa sayısı: `7`
- Aktif reklam kaydı: `0`

Karar:

- Admin panel lokal final smoke açısından P0/P1 blocker göstermedi.
- Production deploy sonrası aynı admin smoke mantığı Hetzner ortamında tekrar alınmalı.

## Local Public Final Smoke - 2026-05-11

Durum: **PASS**

Çalıştırılan public test paketi:

```bash
php artisan test tests\Feature\Public
```

Sonuç:

```text
Tests: 42 passed (239 assertions)
```

HTTP smoke ile 200 dönen sayfalar:

- `/`
- `/kategori/gundem`
- `/kategori/siyaset`
- `/il/adiyaman`
- `/iller`
- `/arama?q=Adiyaman`
- `/arsiv`
- `/hakkimizda`
- `/gizlilik-politikasi`
- `/kvkk`
- `/cerez-politikasi`
- `/iletisim`
- `/sayfa/hakkimizda`
- `/sayfa/gizlilik-politikasi`
- `/sayfa/kvkk-aydinlatma`
- `/sayfa/cerez-politikasi`
- `/robots.txt`
- `/sitemap.xml`
- `/health`

HTTP smoke sinyalleri:

- Mojibake: `false`
- Boş reklam placeholder'ı: `false`
- 404 metni/yanıltıcı hata sinyali: `false`
- Reklam kaydı: `0`
- Render edilebilir reklam: `0`

Son 5 haber detay kontrolü:

| Slug | Status | Gövde Sinyali | Mojibake | Boş Reklam |
|---|---:|---|---|---|
| `mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu` | 200 | var | false | false |
| `mardinde-cifte-sampiyonluk-coskusu` | 200 | var | false | false |
| `ikinci-kattan-dusen-genc-hayatini-kaybetti` | 200 | var | false | false |
| `sanliurfada-anneler-gunu-unutulmadi` | 200 | var | false | false |
| `karagul-hasadi-basladi` | 200 | var | false | false |

Footer link kontrolü:

- `/sayfa/hakkimizda`
- `/iletisim`
- `/sayfa/gizlilik-politikasi`
- `/sayfa/cerez-politikasi`
- `/sayfa/kvkk-aydinlatma`
- `/kategori/gundem`
- `/il/adiyaman`
- `/kategori/asayis`
- `/kategori/yasam`
- `/kategori/ekonomi`
- `/kategori/spor`

Yukarıdaki linklerin tamamı ana sayfa HTML çıktısında mevcut.

İletişim form kontrolü:

- `name` alanı var.
- `email` alanı var.
- `subject` alanı var.
- `message` alanı var.
- Submit/gönderim yüzeyi var.
- Mojibake yok.

Görsel smoke:

- In-app browser ile `http://127.0.0.1:8000/` açıldı.
- Sayfa title: `Adıyaman Dijital Haber`
- Görsel ilk ekran smoke'unda belirgin mojibake, 404, boş reklam kutusu veya kırık header sinyali görülmedi.

Karar:

- Public lokal final smoke açısından P0/P1 blocker görülmedi.
- Full mobil QA ayrı detay turu olarak backlog'da kalır; bu smoke yalnız kritik müşteri sunumu ve canlı öncesi kırıkları hedefler.

## Hetzner Deploy Hazirligi - 2026-05-11

Durum: **READY / SERVER ACCESS PENDING**

Hazirlanan operasyon paketi:

- `docs/operations/ADH-HETZNER-DEPLOY-PREP-CHECKLIST-2026-05-11.md`

Bu paket sunucuya baglanmadan once gerekli deploy girdilerini, production `.env` kararlarini, Nginx/PHP-FPM sablonunu, cron satirini, Supervisor worker konfigunu, kontrollu IHA queued testini, public haber detay govde kanitini ve go/no-go karar kurallarini tek checklist halinde toplar.

Net karar:

- Lokal predeploy ve public/admin/IHA kontrolleri Hetzner asamasina gecmek icin yeterli gorunuyor.
- Production canli karari halen verilmemeli.
- Canli karar ancak Hetzner uzerinde cron + queue worker + controlled queued IHA sync + monitor + latest public IHA detail body evidence alindiktan sonra verilmeli.

Sunucu erisimi gelince ilk alinacak kanitlar:

1. `php artisan schedule:list`
2. `crontab -l`
3. `sudo supervisorctl status`
4. `php artisan iha:sync --limit=3`
5. `php artisan queue:work database --queue=default,analytics,instagram --once` gerekirse
6. `php artisan iha:monitor-forward --limit=20`
7. Son 3 IHA haber public detayinda govde metni kontrolu

Kalan durum:

- Hetzner SSH, production secrets, DNS/SSL ve server runtime kanitlari bekleniyor.
- Bu kanitlar gelmeden `GO` karari yerine `BLOCKED: production evidence pending` karari korunur.

## Mobile Critical Smoke - 2026-05-11

Durum: **PASS WITH FULL-MOBILE-QA CAVEAT**

Rapor:

- `docs/reports/ADH-MOBILE-CRITICAL-SMOKE-2026-05-11.md`

Yapilan lokal iyilestirme:

- Mobil cookie consent paneli viewport'un buyuk kismini kaplamayacak sekilde `max-h-[46dvh]` ile sinirlandi.
- Panel ic scroll davranisini koruyor.
- Aksiyon butonlari panel icinde sticky kalacak sekilde duzenlendi.
- Public testte bu yeni kabul kriteri korumaya alindi.

Kanıt:

- `php artisan test tests\Feature\Public` -> `42 passed (239 assertions)`
- `php artisan deploy:verify --base-url=http://127.0.0.1:8000` -> `All checks passed`
- Mobil screenshotlar `storage/app/qa-mobile` altina alindi.

Karar:

- Lokal mobil kritik public smoke kapsaminda P0/P1 blocker gorulmedi.
- Full mobil QA ve admin mobil QA halen ayri backlog/caveat olarak kalir.

## Admin Mobile Operations Smoke - 2026-05-11

Durum: **PASS WITH DEVICE-QA CAVEAT**

Rapor:

- `docs/reports/ADH-ADMIN-MOBILE-OPERATIONS-SMOKE-2026-05-11.md`

Yapilan lokal iyilestirmeler:

- IHA Health son senkron kayitlari tablosu mobilde yatay kaydirilabilir hale getirildi.
- Analytics period butonlari kucuk genisliklerde satira kirilacak hale getirildi.
- Analytics tablolarina `overflow-x-auto` wrapper eklendi.
- Admin dashboard/control-center ozel tablo shell'i yatay scroll destekleyecek sekilde guclendirildi.

Kanıt:

- Hedefli admin operations smoke -> `21 passed (177 assertions)`
- Full Filament/admin test paketi -> `58 passed (578 assertions)`
- `php artisan deploy:verify --base-url=http://127.0.0.1:8000` -> `All checks passed`

Karar:

- Lokal admin operasyon yuzeylerinde P0/P1 blocker gorulmedi.
- Tam manuel admin mobil cihaz turu ve production admin smoke halen Hetzner/deploy sonrasi caveat olarak kalir.

## Customer Demo Package - 2026-05-11

Durum: **READY FOR LOCAL CUSTOMER DEMO**

Rapor:

- `docs/reports/ADH-CUSTOMER-DEMO-PACKAGE-2026-05-11.md`

Paket icerigi:

- Demo acilis metni
- Kacinilacak iddialar
- Pre-demo komut checklist'i
- Guncel IHA detay ornekleri
- Public tab sirasi
- Admin tab sirasi
- Reklam anlatim notlari
- IHA, iletisim formu ve production limitation cevaplari
- Final demo checklist

Karar:

- Lokal musteri demosu icin public/admin/reklam/IHA anlatim sirasi hazir.
- Demo sirasinda `production-ready` iddiasi kurulmamali; dogru ifade `local predeploy ready, production evidence pending` olmalidir.
