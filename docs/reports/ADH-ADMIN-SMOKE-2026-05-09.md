# ADH Admin Panel Smoke Raporu - 2026-05-09

## Amaç

Admin panelinin üretim öncesi kritik yüzeylerinde son batch değişikliklerinden sonra hızlı fonksiyonel doğrulama yapmak.

## Ortam

- Workspace: `C:\nwp0203\haber-sitesi`
- Uygulama: `http://localhost:8010`
- Laravel ortamı: `local`
- Admin hesabı: `admin@admin.com`
- Admin durumu: aktif, `super_admin` rolü bağlı

Not: `http://127.0.0.1:8001` bu turda ADH değil, farklı bir proje (`Rose Garden`) servis ediyordu. ADH smoke için ayrı olarak `localhost:8010` kullanıldı.

## Tarayıcı Kontrolü

- Public homepage `Adıyaman Dijital Haber` başlığıyla 200 döndü.
- `/admin` login sayfasına doğru redirect verdi.
- `/admin/login` tarayıcıda açıldı ve login formu doğru Türkçe metinlerle göründü.
- Browser eklentisi email input'a programatik yazma sırasında input/clipboard kaynaklı hata verdi. Güvenlik politikasını dolanmamak için admin iç yüzeyleri browser ile oturum açarak gezilmedi.

Bu kısıt uygulama login hatası olarak değerlendirilmedi; admin hesabı ve şifre hash'i Laravel tarafında doğrulandı, panel yüzeyleri Feature/Livewire testleriyle authenticated şekilde kontrol edildi.

## Test Kanıtı

Komut:

```powershell
php artisan test tests\Feature\Filament
```

Sonuç:

- 32 test geçti
- 200 assertion geçti
- Başarısız test yok

Ek hedefli paket:

```powershell
php artisan test tests\Feature\Filament\AdminContentIntegrityTest.php tests\Feature\Filament\AdminOperationsReadinessTest.php tests\Feature\Filament\ContentOperationsAndLayoutTest.php tests\Feature\Filament\AdminLanguageQualityTest.php tests\Feature\Filament\IhaHealthPageTest.php tests\Feature\Filament\IntegrationSettingsPageTest.php tests\Feature\Filament\AdminDashboardAndNewsResourceTest.php
```

Sonuç:

- 25 test geçti
- 171 assertion geçti
- Başarısız test yok

## Doğrulanan Admin Yüzeyleri

- Dashboard ve haber resource sayfaları authenticated admin ile açılıyor.
- Analytics ve operasyon sayfaları authenticated admin ile açılıyor.
- User management editor rolüne kapalı.
- IHA Health sayfası admin tarafından açılıyor.
- Integration settings Instagram readiness durumunu gösteriyor.
- Admin guide/tour anchorları dashboard, layout ve health sayfalarında render oluyor.
- Representative admin pages mojibake dizileri üretmiyor.

## Son Batch Fonksiyon Kanıtları

- IHA haberleri bulk action ile publish/archive/draft/category/featured/breaking mutasyonlarından korunuyor.
- IHA edit sayfasında delete action gizli.
- NewsArticle tag alanı gerçek `tags()` ilişkisine sync oluyor.
- Public image upload alanları PDF MIME kabul etmiyor.
- HeaderTheme fixed date, nth weekday ve date range tiplerinde koşullu validation çalışıyor.
- Contact form admin'den belirlenen alıcı e-postaya mail gönderiyor.
- General/SEO/Integration/Email settings persist ve remount davranışı doğrulandı.
- SystemAlerts no-log, stale, failed ve success-lag durumlarını ayırıyor.
- LayoutStudio draft save public base state'i değiştirmiyor; publish aşamasında uyguluyor.
- MediaLibrary yalnız orphan media silebiliyor; attached media korunuyor.
- Legacy LayoutManager disabled stub; mutasyon metodları kapatıldı.

## IHA/Queue Lokal Durum Notu

Bu makine üretim queue/cron kaynağı olmayacak; bu nedenle aşağıdaki değerler production readiness kanıtı değildir, sadece lokal geliştirme gözlemidir.

- `schedule:list` içinde `php artisan iha:sync` 15 dakikalık schedule ile görünüyor.
- `schedule:list` içinde `iha:sync --inline` görünmüyor.
- `iha:monitor-forward --limit=20` sonucu: `health=healthy`, `quality_risk=no`, `freshness_minutes=8`.
- Lokal `jobs` tablosunda pending backlog var: `default=260`, `analytics=509`.
- `failed_jobs` boş.
- Son sync log bu turda `running` durumunda görüldü.

Production geçişinde bu backlog lokal veri olarak temizlenebilir veya yok sayılabilir; asıl kanıt Hetzner sunucuda cron + Supervisor/systemd queue worker üzerinden alınmalı.

## İçerik Kalitesi Lokal Notu

Lokal DB'de 258 IHA haberinden iki eski kayıt gövde kalitesi açısından zayıf görünüyor:

- `id=34`, gövde uzunluğu 139 karakter
- `id=35`, gövde uzunluğu 0 karakter

Son 20 IHA haber penceresinde `empty_content=0`, `weak_body=0`, `short_body=0` olduğu için ileriye dönük ingest kalitesi bu smoke turunda sağlıklı göründü.

## Sonuç

Admin paneli için P0/P1 fonksiyonel blocker görülmedi. Kritik admin güvenliği ve içerik bütünlüğü kontrolleri testlerle yeşil.

Kalan üretim notları:

- Hetzner deployment öncesi cron + queue worker kanıtı sunucuda alınmalı.
- Lokal pending queue backlog production durumuyla karıştırılmamalı.
- İki eski zayıf IHA haber kaydı geçmiş katalog recovery iş listesinde tutulmalı.
