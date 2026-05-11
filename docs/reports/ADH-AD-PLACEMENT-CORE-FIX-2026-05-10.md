# ADH Reklam Yerleşimi Core Fix Raporu - 2026-05-10

## Karar

Durum: **PASS - core reklam yerleşim altyapısı çalışır durumda.**

Bu çalışma, admin panelde tanımlanabilen reklam pozisyonlarının public sitede karşılık bulmaması riskini kapattı. Reklam kayıtları girildiğinde aşağıdaki pozisyonlar artık render ediliyor:

- `header`
- `footer`
- `between-news`
- `sidebar-top`
- `sidebar-bottom`
- `article-top`
- `article-bottom`

## Yapılan Değişiklikler

- Reklam pozisyonları için ortak kaynak oluşturuldu: `app/Support/AdvertisementPlacement.php`.
- Admin reklam formu ortak pozisyon listesini kullanacak hale getirildi.
- Ana sayfa reklam modülü `between-news`, `sidebar-top`, `sidebar-bottom` pozisyonlarını render edecek hale getirildi.
- Global layout'a `header` ve `footer` reklam slotları eklendi.
- Haber detay sayfasına `article-top` ve `article-bottom` reklam slotları eklendi.
- Filament upload path'i `advertisements/foo.jpg` formatında geldiğinde public URL `/storage/advertisements/foo.jpg` olarak çözülecek hale getirildi.
- AdSense reklamlarında `adsense_slot` boşsa boş/bozuk AdSense bloğu basılması engellendi.
- Aktif ama görseli olmayan banner kayıtları için güvenli placeholder davranışı korundu.

## Kanıtlar

### HTTP Smoke

- `GET /` status: `200`
- Ana sayfada görülen slotlar:
  - `header`: present
  - `between-news`: present
  - `sidebar-top`: present
  - `sidebar-bottom`: present
  - `footer`: present
- `GET /mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu` status: `200`
- Haber detayda görülen slotlar:
  - `article-top`: present
  - `article-bottom`: present

### Browser Smoke

- URL: `http://127.0.0.1:8000/`
- Title: `Adıyaman Dijital Haber`
- Ana sayfa slotları tekil ve görünür:
  - `header`
  - `between-news`
  - `sidebar-top`
  - `sidebar-bottom`
  - `footer`
- Haber detay URL: `http://127.0.0.1:8000/mardinde-sampiyonluk-gecesinin-adresi-mardian-mall-oldu`
- Haber detay title: `Mardin’de şampiyonluk gecesinin adresi Mardian Mall oldu | Adıyaman Dijital Haber`
- Haber detay slotları tekil ve görünür:
  - `header`
  - `article-top`
  - `article-bottom`
  - `footer`
- İlgili browser console hata/uyarı sayısı: `0`

### Testler

Komut:

```bash
php artisan test tests\Feature\Public\PublicPagesTest.php tests\Unit\Services\HomeModuleDataServiceTest.php tests\Feature\Filament\AdminMediaUploadHardeningTest.php tests\Feature\Filament\ContentOperationsAndLayoutTest.php
```

Sonuç:

- `28 passed`
- `162 assertions`

## Veri Temizliği

Doğrulama için oluşturulan geçici kayıtlar temizlendi:

- Silinen geçici reklam kayıtları: `7`
- Kaldırılan geçici görsel: `storage/app/public/advertisements/adh-core-ad.jpg`
- Kalan `ADH-UAT-AD-CORE%` kayıt: `0`

## Kalan Ticari Gereklilikler

Core altyapı hazır olsa da gelir üretmeye hazır reklam operasyonu için aşağıdaki veriler gerekir:

- Gerçek reklam veren görselleri ve hedef linkleri admin panelden girilmeli.
- AdSense kullanılacaksa `adsense_client_id` ve her reklam için `adsense_slot` doğru girilmeli.
- Hangi pozisyonun hangi fiyatlandırma/paketle satılacağı operasyonel olarak belirlenmeli.
- Canlıya alım sonrası gerçek reklam görselleriyle desktop ve mobil görsel QA yapılmalı.

## Not

Gerçek reklam kaydı yoksa slot bileşeni public sitede boş/kırık alan üretmez. Bu doğru davranıştır; reklam yüzeyi ancak aktif ve tarih aralığı geçerli bir reklam kaydı olduğunda görünür.
