# ADH Advertising Tracking Recheck - 2026-05-13

## Karar

Durum: **PASS - reklam altyapısı korunuyor, impression tracking bağımsız hale getirildi.**

Bu turda reklam yönetimi, AdSense/manüel banner modeli ve public slot render zinciri tekrar
kontrol edildi. V1 reklam altyapısı genel olarak önceki readiness raporuyla uyumlu kaldı.
Ek olarak, gösterim sayacı için tespit edilen bir frontend bağımlılık riski kapatıldı.

## Tespit Edilen Risk

Reklam slot component'i gösterim sayacı için `x-intersect` kullanıyordu. Projede Alpine
Intersect plugin bağımlılığı yoktu. Bu durumda reklam publicte görünse bile impression
tracking tarayıcıda çalışmayabilirdi.

Risk etkisi:

- Reklam veren performans kanıtında gösterim sayacı eksik kalabilirdi.
- Admin listesinde `view_count` gerçek gösterimleri yansıtmayabilirdi.
- Manuel endpoint testleri geçse bile gerçek public sayfa tracking'i sessiz bozulabilirdi.

## Uygulanan Düzeltme

- `resources/views/components/ad-slot.blade.php`
  - `x-intersect` bağımlılığı kaldırıldı.
  - Her render edilen reklam slotuna `data-impression-url` eklendi.
  - Tek seferlik vanilla `IntersectionObserver` script'i eklendi.
  - Eski tarayıcı fallback'i olarak slotlar doğrudan track edilir.
  - CSRF header yalnız token varsa eklenir.
  - `keepalive: true` ile sayfa geçişlerinde tracking isteği daha dayanıklı hale getirildi.

- `tests/Feature/Public/PublicPagesTest.php`
  - Public reklam HTML'inde `data-impression-url` üretildiği doğrulandı.
  - `IntersectionObserver` tracking script'i doğrulandı.
  - `x-intersect` bağımlılığının geri gelmemesi için regresyon assertion'ı eklendi.

## Korunan Reklam Kabiliyetleri

- Admin panelden manüel banner yönetimi
- Desktop ve mobil ayrı kreatif desteği
- Hedef link ve click tracking
- Google AdSense Client ID + Slot ID modeli
- Eksik Client ID veya Slot ID durumunda boş/kırık reklam basmama
- Slot bazlı ölçü rehberi ve public yükseklik/oran guardrail'leri
- Header, footer, between-news, sidebar-top, sidebar-bottom, article-top, article-bottom slotları
- Admin upload MIME hardening
- Admin metinlerinde mojibake kalite kapısı

## Test Kanıtı

Komut:

```bash
php artisan test tests/Feature/Public/PublicPagesTest.php tests/Feature/Filament/AdvertisementResourceCrudTest.php tests/Unit/Services/HomeModuleDataServiceTest.php tests/Feature/Filament/AdminMediaUploadHardeningTest.php tests/Feature/Filament/AdminLanguageQualityTest.php
```

Sonuç:

- `30 passed`
- `310 assertions`

Ek kontrol:

```bash
git diff --check
```

Sonuç:

- whitespace/diff hatası yok

## Production Notları

Canlı reklam gelir modeli için halen production tarafında gerekenler:

- Müşteri AdSense hesabı ve publisher doğrulaması
- Admin panelde gerçek `adsense_client_id`
- Her AdSense reklam kaydında gerçek `adsense_slot`
- Gerekirse domain kökünde Google `ads.txt`
- Gerçek reklam görselleriyle desktop ve mobil QA
- Gerçek kampanya linkleri ve UTM standardının müşteriyle netleştirilmesi

Bu maddeler kod blocker'ı değil; canlı reklam operasyonu ve müşteri hesabı gereksinimidir.

