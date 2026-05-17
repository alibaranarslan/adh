# ADH Public Mobil Görünüm İyileştirme Raporu

Tarih: 2026-05-14  
Kapsam: Public haber sitesi mobil yüzeyi. Admin panel mobil QA kapsam dışıdır.  
Karar: Local mobile public readiness `PASS`, kalan küçük tap-target borçları `post-pass polish` olarak ayrıldı.

## Uygulanan Düzeltmeler

- Mobil header sadeleştirildi; yatay kategori şeridi mobilde kaldırıldı ve kategori erişimi hamburger/drawer modeline taşındı.
- Mobil header tap target'ları büyütüldü; arama, dil ve menü üst barda 44px hedefe yaklaştırıldı.
- Masthead mobil yüksekliği düşürüldü; çift arama aksiyonu kaldırıldı.
- Son dakika barı mobilde 44px kontrollü satır haline getirildi, uzun başlıklar dar ekranda truncate edildi.
- Ana sayfa hero mobil akışı tek kolon için sertleştirildi; "Hızlı Gündem Akışı" kartının hero metni üstüne binmesi giderildi.
- Haber detay mobil başlığı küçültüldü, detay sayfasında ana görsel ve gövdeye erişim hızlandırıldı.
- Public Blade yüzeylerinde görünen mojibake riski taşıyan metinler temizlendi.
- Kurumsal/statik sayfa mobil kart boşlukları ve güven navigasyonu sıkılaştırıldı.

## Kanıt Özeti

Browser plugin bağlantısı kurulum aşamasında timeout verdi; render kanıtları Playwright fallback ile alındı.

| Kontrol | Sonuç | Kanıt |
| --- | --- | --- |
| `/` mobile 390x844 | PASS | `01-home-mobile-top.png` |
| `/kategori/gundem` mobile | PASS | `03-category-mobile.png` |
| örnek haber detay mobile | PASS | `04-detail-mobile.png` |
| `/arama?q=adiyaman` mobile | PASS | `05-search-mobile.png` |
| `/iletisim` mobile | PASS | `06-contact-mobile.png` |
| `/hakkimizda` mobile | PASS | `07-about-mobile.png` |
| hamburger/drawer open | PASS | `08-mobile-menu-open.png` |
| dark mode homepage | PASS | `09-home-mobile-dark.png` |
| dark mode detail | PASS | `10-detail-mobile-dark.png` |
| 360/430/tablet/desktop smoke | PASS | `11-home-360.png`, `12-home-430.png`, `13-home-tablet.png`, `14-home-desktop-reference.png` |

## Ölçüm Karşılaştırması

- Önceki mobil header yüksekliği: yaklaşık `218-220px`.
- Yeni mobil header yüksekliği: `170px` (`390px` viewport), `147px` (`430px` viewport).
- Önceki ana içerik başlangıcı: yaklaşık `373-375px`.
- Yeni ana içerik başlangıcı: `262px` (`390px` viewport), `239px` (`430px` viewport).
- Mobil yatay nav: önce görünür ve gizli scroll bağımlıydı; şimdi mobile `navVisible=false`.
- Son dakika barı: `44px`.
- Tüm smoke sayfalarında `horizontalOverflow=false`.
- Tüm smoke sayfalarında `bodyMojibake=false`.
- Tüm smoke sayfalarında console/page error yok.

## Drawer Kontrolü

Hamburger menü açık durumda şu kritik erişimler doğrulandı:

- `Haber ara`: var.
- Kategoriler: var.
- `İller`: var.
- `Hakkımızda` ve `İletişim`: var.

Bu nedenle mobil kategori erişimi artık gizli yatay scroll'a bağlı değildir.

## Kalan Riskler

- Kart içi bazı haber başlığı linkleri ve footer sosyal ikonları ölçümde 44px altında görünüyor. Bunlar artık kritik header/nav blocker değil; ayrı erişilebilirlik polish batch'i olarak ele alınmalı.
- Tablet `768px` görünümünde desktop nav devreye giriyor. Görsel smoke PASS, ancak tablet özel UX daha sonra ayrıca incelenebilir.
- Browser plugin timeout verdiği için kanıtlar Playwright fallback ile üretildi.

## Komutlar

```bash
npm run build
php artisan test tests/Feature/Public/HeaderThemeTest.php tests/Feature/Public/NewsDetailPresentationTest.php
php artisan optimize:clear
node %TEMP%/adh-mobile-final-smoke-2026-05-14.mjs
```

## Screenshot Klasörü

`docs/reports/assets/mobile-public-audit-2026-05-14/`
