# ADH Internet Reklamcılığı Readiness Raporu - 2026-05-11

## Karar

Durum: **PASS - reklam altyapısı profesyonel işletim modeline uygun hale getirildi.**

Reklam sistemi artık iki ana modeli destekler:

- Manuel banner: admin panelden görsel, hedef link, pozisyon, tarih aralığı ve sıra değeriyle yönetilir.
- Google AdSense: entegrasyon ayarındaki global `adsense_client_id` ve reklam kaydındaki `adsense_slot` ile çalışır.

Eksik veya yanlış yapılandırılmış aktif reklamlar public sitede boş/kırık reklam alanı üretmez. Sistem uygun sıradaki ilk geçerli reklamı seçer; geçerli reklam yoksa slot hiç render edilmez.

## Demo Temizliği

Demo UAT sonrası kaldırılanlar:

- `ADH-DEMO-REKLAM-*` kayıtları: `7`
- `storage/app/public/advertisements/adh-demo-*.png` görselleri: `7`
- Kalan demo kayıt: `0`
- Kalan demo görsel: `0`

## Reklam Yerleşimleri

Public sitede desteklenen slotlar:

- `header`
- `footer`
- `between-news`
- `sidebar-top`
- `sidebar-bottom`
- `article-top`
- `article-bottom`

## Profesyonel Davranış Kuralları

- Aktif manuel banner ancak `image_path` varsa publicte görünür.
- Aktif manuel banner görselsizse publicte gizlenir.
- Aktif AdSense reklam ancak global Client ID ve kayıt bazlı Slot ID varsa publicte görünür.
- AdSense Client ID yoksa AdSense slotu publicte gizlenir.
- Slot ID yoksa AdSense reklam publicte gizlenir.
- Aynı pozisyonda en düşük `sort_order` önceliklidir.
- Eksik sıradaki kayıt atlanır; varsa sonraki geçerli kayıt render edilir.
- Link girilmiş manuel banner tıklanabilir olur ve click tracking endpoint'i çalışır.
- Görünen reklamlar impression tracking endpoint'iyle sayılır.
- Manuel banner görselleri slot bazlı maksimum yükseklik sınırlarıyla render edilir; hatalı oranlı kreatifler public layout'u taşırmaz.
- Admin panelde pozisyon seçimine göre slot ölçü rehberi gösterilir.

## Admin Panel

Reklam yönetim ekranı:

- `/admin/advertisements`

Admin form davranışı:

- Tür seçimi: `Manuel Banner` veya `Google AdSense`
- Pozisyon seçimine göre slot ölçü rehberi gösterilir.
- Manuel Banner alanları:
  - Banner görseli
  - Opsiyonel tıklama linki
- Google AdSense alanları:
  - Zorunlu, rakamsal `AdSense Slot ID`
  - Form içinde global `AdSense Client ID` hazır/eksik durumu gösterilir.
- Ortak alanlar:
  - Pozisyon
  - Başlangıç tarihi
  - Bitiş tarihi
  - Aktif/pasif
  - Sıra

Admin tablo davranışı:

- `Yayın Durumu` sütunu eklendi.
- Statüler:
  - `Yayına Hazır`
  - `Pasif`
  - `Planlı`
  - `Süresi Doldu`
  - `Eksik Görsel`
  - `Eksik Slot ID`
  - `Eksik Client ID`
  - `Geçersiz`

## Kod Değişiklikleri

- `app/Models/Advertisement.php`
  - Reklam tür sabitleri eklendi.
  - `isRenderable()` ve `renderStatus()` eklendi.
  - Desktop/mobil banner URL çözümleme desteği eklendi.
- `app/Support/AdvertisementPlacement.php`
  - Slot bazlı desktop/mobil ölçü önerileri ve public maksimum yükseklik metadatası eklendi.
- `resources/views/components/ad-slot.blade.php`
  - Eksik reklamları gizleyen render mantığı eklendi.
  - Geçersiz ilk kayıt yerine sonraki geçerli kayıt seçilecek hale getirildi.
  - Mobil görsel varsa `<picture>` ile mobilde ayrı kreatif gösterilecek hale getirildi.
  - Manuel banner görsellerinde slot bazlı taşma koruması eklendi.
- `app/Services/HomeModuleDataService.php`
  - Ana sayfa reklam modülü yalnız render edilebilir reklam varsa açılacak hale getirildi.
- `app/Filament/Resources/AdvertisementResource.php`
  - Admin form açıklamaları ve AdSense validation eklendi.
  - `Desktop Banner Görseli` ve `Mobil Banner Görseli` alanları eklendi.
  - Pozisyona göre slot ölçü rehberi eklendi.
  - AdSense türünde Client ID eksik/hazır uyarısı eklendi.
  - Reklam listesinde pozisyonlar teknik anahtar yerine insan okunur etiketle gösterilecek hale getirildi.
  - Tür uyumsuz alanları normalize eden davranış eklendi.
  - Yayın durumu tablosu eklendi.
- `app/Filament/Pages/IntegrationSettings.php`
  - AdSense Client ID alan açıklaması, değerin public AdSense scriptinde kullanılacağını açık söyleyecek şekilde düzeltildi.
- `app/Filament/Resources/AdvertisementResource/Pages/CreateAdvertisement.php`
  - Form data normalization bağlandı.
- `app/Filament/Resources/AdvertisementResource/Pages/EditAdvertisement.php`
  - Form data normalization bağlandı.
- `app/Support/AdminGuides/AdminGuideRegistry.php`
  - Reklam yönetimi rehberi yeni davranışa uygun güncellendi.

## Test Kanıtı

Komut:

```bash
php artisan test tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Public\PublicPagesTest.php tests\Unit\Services\HomeModuleDataServiceTest.php tests\Feature\Filament\AdminMediaUploadHardeningTest.php tests\Feature\Filament\ContentOperationsAndLayoutTest.php
```

Sonuç:

- `34 passed`
- `230 assertions`

Ek browser smoke:

- URL: `http://127.0.0.1:8000/`
- Title: `Adıyaman Dijital Haber`
- Demo görsel count: `0`
- Header/home/sidebar/footer demo slot count: `0`
- Boş/kırık reklam görsel kaynağı: `0`
- Browser console ilgili hata/uyarı: `0`

Kontrollü reklam QA:

- Geçici `ADH-QA-HEADER-MANUAL` manuel banner kaydıyla header slotu test edildi.
- Geçici `ADH-QA-FOOTER-ADSENSE` AdSense kaydıyla Client ID + Slot ID render koşulu test edildi.
- Manuel banner HTML çıktısında mobil `<source>`, slot bazlı `max-height` ve `aspect-ratio` guardrail değerleri doğrulandı.
- AdSense HTML çıktısında `data-ad-client` ve `data-ad-slot` doğrulandı.
- QA sonrası geçici reklam kayıtları ve test `adsense_client_id` ayarı temizlendi.

## Son Fonksiyonel Smoke - 2026-05-11

Bu turda müşteri demosu değil, reklam altyapısının fonksiyonel çalışma zinciri kontrol edildi.

Geçici kayıtlar:

- `ADH_SMOKE_HEADER_MANUAL`
- `ADH_SMOKE_BETWEEN_MANUAL`
- `ADH_SMOKE_ARTICLE_TOP_MANUAL`
- `ADH_SMOKE_ARTICLE_BOTTOM_ADSENSE`
- `ADH_SMOKE_FOOTER_MANUAL`

Doğrulanan davranışlar:

- Header manuel banner desktop kreatif ile render edildi.
- Header manuel banner mobil viewportta mobil kreatif ile render edildi.
- Ana sayfa `between-news` manuel banner HTML çıktısında render edildi.
- Footer manuel banner HTML çıktısında render edildi.
- Haber detay `article-top` manuel banner render edildi.
- Haber detay `article-bottom` AdSense reklamı `data-ad-client` ve `data-ad-slot` ile render edildi.
- AdSense gerçek reklam dolumu localhost/test client üzerinde beklenmedi; bu üretim domaini, gerçek publisher hesabı ve Google onayı gerektirir.
- `api/ad-impression/{ad}` ve `api/ad-click/{ad}` endpointleri 200 döndü ve sayaçları artırdı.
- Smoke sonrası geçici reklam kayıtları, test `adsense_client_id` ayarı ve `adh-smoke-*.png` görselleri temizlendi.
- Temizlik sonrası public HTML içinde `ADH_SMOKE` ve `ca-pub-0000000000000000` izi kalmadığı doğrulandı.

Son test kanıtı:

```bash
php artisan test tests\Feature\Public\PublicPagesTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php tests\Feature\Filament\AdminMediaUploadHardeningTest.php
php artisan deploy:verify --base-url=http://127.0.0.1:8000
```

Sonuç:

- `26 passed`
- `181 assertions`
- Deploy verification: PASS

Admin UX kontrolü:

- Reklam formunda pozisyona göre `Slot Ölçü Rehberi` görünür.
- AdSense türünde `AdSense Client ID Durumu` alanı Client ID hazır/eksik bilgisini açık verir.
- Reklam listesinde pozisyonlar teknik anahtar yerine insan okunur etiketle gösterilir.
- Entegrasyon Ayarları ekranındaki AdSense Client ID açıklaması, değerin public AdSense scriptinde kullanılacağını açık söyler.
- Admin language-quality testleri reklam ve entegrasyon metinlerinde mojibake yakalayacak şekilde çalıştırıldı.

Backlog kaydı:

- V1 kapsamı yayın, manuel reklam, AdSense, responsive kreatif, slot koruması ve admin yönetimi olarak kapatıldı.
- Reklam veren, kampanya, CTR/performance raporu, CSV/PDF dışa aktarım, otomatik UTM ve dashboard widget işleri v1 blocker değildir.
- Bu ticari operasyon işleri `ADH-REKLAM-SLOT-PAKETLERI-2026-05-11.md` içinde `Reklam Ticari Operasyon Backlog'u` başlığıyla kayda alındı.

## Canlı Operasyon Gereklilikleri

Google AdSense için:

- AdSense hesabı müşteri adına doğrulanmalı.
- `adsense_client_id` admin panelde Entegrasyon Ayarları ekranına girilmeli.
- Her slot için Google panelinden alınan `adsense_slot` değeri reklam kaydına girilmeli.
- Gerçek publisher ID belli olduktan sonra domain için Google `ads.txt` gereksinimi ayrıca kontrol edilmeli ve gerekiyorsa production public kök dizinine eklenmeli.
- AdSense politika uygunluğu canlı alan adı üzerinde ayrıca doğrulanmalı.

Manuel reklam için:

- Reklam veren görseli doğru ölçü/oranla hazırlanmalı.
- Hedef link doğrulanmalı.
- Kampanya başlangıç/bitiş tarihleri girilmeli.
- Aynı slotta birden fazla kampanya varsa `sort_order` planlanmalı.

Canlı öncesi son kontrol:

- Gerçek bir manuel banner kaydı gir.
- Gerçek veya test AdSense slot kaydı gir.
- Ana sayfa, haber detay, desktop ve mobil görsel QA yap.
- Impression/click sayacı admin listesinde izlenebilir mi kontrol et.
