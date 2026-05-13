# ADH Media Upload Security Recheck - 2026-05-13

## Karar

Durum: **PASS - medya kütüphanesi ve public image upload güvenliği korunuyor.**

Bu turda medya kütüphanesi, orphan/attached media silme davranışı, public görsel upload
MIME allowlist'i, storage URL görünürlüğü ve public haber/reklam görsel etkileri tekrar
kontrol edildi. Yeni production blocker bulunmadı; iki güvenlik regresyon testi eklendi.

## Doğrulanan Davranışlar

- MediaLibrary yalnız configuration access yetkisi olan kullanıcıya açılıyor.
- Attached media silinemiyor.
- Orphan media silinebiliyor.
- Orphan/attached kullanım durumu admin yüzeyinde görünür.
- MediaLibrary batch loading çalışıyor.
- Public image upload alanları ortak `AdminImageUploads` kısıtlarını kullanıyor.
- PDF upload public görsel alanlarında kabul edilmiyor.
- MIME allowlist sadece raster görselleri içeriyor:
  - `image/jpeg`
  - `image/png`
  - `image/webp`
  - `image/gif`
- `application/pdf`, `image/svg+xml`, `text/html`, `application/x-php` allowlist dışında.
- Haber ve reklam public görselleri storage URL formatıyla render ediliyor.
- Kırık/boş reklam görseli publicte boş slot üretmiyor.

## Eklenen Test Güvenceleri

Dosya:

- `tests/Feature/Filament/AdminMediaUploadHardeningTest.php`

Eklenen senaryolar:

- `test_media_library_requires_configuration_access`
  - Yetkisiz kullanıcı `/admin/media-library` yüzeyine erişemez.
  - Orphan media kaydı korunur.

- `test_admin_image_upload_mime_allowlist_stays_image_only`
  - Allowlist'in yalnız güvenli image MIME değerlerinden oluştuğunu doğrular.
  - PDF, SVG, HTML ve PHP MIME değerlerinin allowlist dışında kaldığını doğrular.

## Test Kanıtı

Medya ve admin içerik paketi:

```bash
php artisan test tests/Feature/Filament/AdminMediaUploadHardeningTest.php tests/Feature/Filament/ContentOperationsAndLayoutTest.php tests/Feature/Filament/AdminContentIntegrityTest.php
```

Sonuç:

- `27 passed`
- `181 assertions`

Public medya etkisi ve dil kalitesi:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Public/NewsDetailPresentationTest.php tests/Feature/Filament/AdminLanguageQualityTest.php
```

Sonuç:

- `27 passed`
- `272 assertions`

Deploy verification:

```bash
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Sonuç:

- All checks passed

Diff kontrolü:

```bash
git diff --check
```

Sonuç:

- whitespace/diff hatası yok

## Production Notu

Kod tarafında medya/upload güvenliği için local predeploy blocker yok. Canlıya alımda şu
operasyon kanıtları ayrıca alınmalıdır:

- `php artisan storage:link` çalışmış olmalı.
- Public `/storage/...` URL'leri Nginx üzerinden 200 dönmeli.
- Haber görseli, reklam görseli, logo ve favicon gerçek domain üzerinden açılmalı.
- Admin panelde attached media için `Silme kapalı`, orphan media için `Sil` görünmeli.
- Production dosya izinleri web user tarafından okunabilir, storage yazılabilir olmalı.

