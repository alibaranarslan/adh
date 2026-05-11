# ADH Reklam Slotları ve Satış Paketleri - 2026-05-11

## Amaç

Bu doküman, Adıyaman Dijital Haber reklam alanlarının müşteriyle konuşulabilir, admin panelden yönetilebilir ve teknik olarak sürdürülebilir bir reklam operasyonuna dönüşmesi için hazırlanmıştır.

Ana ilke:

> Public sitede kırık, boş, eksik veya güven zedeleyen reklam alanı görünmemeli; yalnız yayın kurallarından geçen reklam görünmelidir.

## Reklam Felsefesi

ADH için doğru model iki katmanlıdır:

- **Premium manuel reklamlar:** Yerel işletme, kurum, sponsor ve kampanya bazlı doğrudan satışlar.
- **Google AdSense fallback:** Doğrudan satılmamış envanterin otomatik dolumu.

Öncelik sırası:

1. Satılmış manuel kampanya
2. Planlı sponsorluk/kurumsal kampanya
3. AdSense fallback
4. Hiçbiri yoksa slotu gizle

Bu yaklaşım haber sitesinin güvenilirliğini korur. Reklam alanı var diye boş kutu göstermek, demo veya canlı yayında kalite kaybıdır.

## Mevcut Teknik Durum

Sistem şu iki reklam tipini destekler:

- `Manuel Banner`
- `Google AdSense`

Desteklenen public slotlar:

- `header`
- `footer`
- `between-news`
- `sidebar-top`
- `sidebar-bottom`
- `article-top`
- `article-bottom`

Admin panel:

- `/admin/advertisements`

Yayın durumu kontrolleri:

- `Yayına Hazır`
- `Pasif`
- `Planlı`
- `Süresi Doldu`
- `Eksik Görsel`
- `Eksik Slot ID`
- `Eksik Client ID`
- `Geçersiz`

## Slot Haritası

| Slot | Görünüm | Kullanım Amacı | Manuel Görsel Önerisi | AdSense Modeli |
|---|---|---|---|---|
| `header` | Header/nav altında geniş banner | En değerli üst görünürlük | 1200x150 veya 970x90 oranlı güvenli tasarım | Responsive display |
| `between-news` | Ana sayfa haber akışı içinde geniş alan | Akış içi kampanya, yerel sponsor | 1200x180 veya 970x250 oranlı tasarım | Responsive display |
| `sidebar-top` | Sağ blok üst reklam | Sürekli görünür yerel işletme alanı | 520x320, 336x280 veya 300x250 oranlı tasarım | Responsive display |
| `sidebar-bottom` | Sağ blok alt reklam | İkincil sponsor / kampanya devamı | 520x320, 336x280 veya 300x250 oranlı tasarım | Responsive display |
| `article-top` | Haber detayında ana içerik öncesi | Haber okuru yakalama, yüksek niyetli trafik | 900x150 veya 728x90 oranlı tasarım | Responsive display |
| `article-bottom` | Haber metni sonrası | Okuma sonrası teklif/CTA | 900x150 veya 728x90 oranlı tasarım | Responsive display |
| `footer` | Footer öncesi geniş alan | Kurumsal alt sponsorluk | 1200x140 veya 970x90 oranlı tasarım | Responsive display |

Not: Manuel banner sistemi desktop ve mobil için ayrı görsel destekler. Mobil görsel girilmezse desktop görsel responsive şekilde ölçeklenir. Bu nedenle her iki varyasyonda da kritik metin ve logo güvenli orta alanda tutulmalıdır.

## Slot Yükseklik Disiplini

Public reklam bileşeni manuel banner görsellerinde slot bazlı maksimum yükseklik uygular. Amaç, büyük veya hatalı oranlı kreatiflerin haber akışını bozmasını, mobilde ekranı kaplamasını ve müşteri sunumunda amatör görünmesini engellemektir.

| Slot | Desktop Public Sınır | Mobil Public Sınır | Public Oran | Admin Ölçü Rehberi |
|---|---:|---:|---|---|
| `header` | 180px | 150px | desktop `8 / 1`, mobil `3.3 / 1` | 1200x150 / 970x90, mobil 720x220 |
| `between-news` | 260px | 220px | desktop `5 / 1`, mobil `2.8 / 1` | 1200x180 / 970x250, mobil 720x260 |
| `sidebar-top` | 360px | 260px | desktop `1.25 / 1`, mobil `2.8 / 1` | 520x320 / 336x280 / 300x250, mobil 720x260 |
| `sidebar-bottom` | 360px | 260px | desktop `1.25 / 1`, mobil `2.8 / 1` | 520x320 / 336x280 / 300x250, mobil 720x260 |
| `article-top` | 180px | 150px | desktop `6 / 1`, mobil `3.3 / 1` | 900x150 / 728x90, mobil 720x220 |
| `article-bottom` | 180px | 150px | desktop `6 / 1`, mobil `3.3 / 1` | 900x150 / 728x90, mobil 720x220 |
| `footer` | 170px | 140px | desktop `8 / 1`, mobil `3.6 / 1` | 1200x140 / 970x90, mobil 720x200 |

Admin panelde pozisyon seçildiğinde bu rehber metni gösterilir. Kreatif ölçüleri bu sınırlara göre hazırlanmalı; public taraf son savunma katmanı olarak görseli slot içinde tutar.

## Manuel Banner Kuralları

Manuel banner için zorunlu:

- Reklam adı
- Pozisyon
- Banner görseli
- Aktiflik durumu

Opsiyonel:

- Tıklama linki
- Başlangıç tarihi
- Bitiş tarihi
- Sıra değeri

Profesyonel kullanım:

- Görselde çok küçük metin kullanılmamalı.
- CTA net olmalı: `Detaylı Bilgi`, `Hemen Ara`, `Kampanyayı İncele`.
- Haber sitesi güvenilirliğini düşürecek agresif veya yanıltıcı görsel kullanılmamalı.
- Aynı slotta çok fazla kampanya döndürülmemeli; en fazla 2-3 aktif kampanya mantıklı olur.
- Süresi biten kampanyalar arşivlenmeli veya pasife alınmalı.

## Google AdSense Kuralları

AdSense için zorunlu:

- Entegrasyon ayarında global `adsense_client_id`
- Reklam kaydında `adsense_slot`
- Reklam tipi: `Google AdSense`
- Aktiflik ve tarih kurallarından geçme

AdSense davranışı:

- Client ID yoksa AdSense reklam publicte görünmez.
- Slot ID yoksa AdSense reklam publicte görünmez.
- AdSense reklamda manuel click tracking uygulanmaz; gelir/performans Google AdSense panelinden takip edilir.
- ADH tarafında impression sayımı slotun publicte render edildiğini izlemek için kullanılabilir.

Resmi Google notları:

- Google, responsive display reklamların sayfa düzeni ve cihazlara uyum sağladığını belirtir.
- Google, fixed-size ünitelerin kullanılabildiğini ancak responsive reklamlara göre daha sınırlı reklam havuzu riski taşıyabileceğini belirtir.

Kaynaklar:

- [Google AdSense Help - Create a display ad unit](https://support.google.com/adsense/answer/9274025)
- [Google AdSense Help - Guidelines for fixed-sized display ad units](https://support.google.com/adsense/answer/9185043)
- [Google AdSense Help - Modify responsive ad code](https://support.google.com/adsense/answer/9183363)

## Sıra Değeri Standardı

Önerilen `sort_order` standardı:

| Aralık | Kullanım |
|---|---|
| `1-99` | Satılmış premium manuel reklamlar |
| `100-199` | Planlı sponsorluk / özel kampanya |
| `500-599` | Düşük öncelikli manuel dolgu |
| `900-999` | AdSense fallback |

Bu sayede aynı slotta:

- Önce satılmış manuel reklam görünür.
- Manuel reklam eksik veya süresi dolmuşsa sonraki geçerli kayıt denenir.
- En sonda AdSense fallback çalışır.

## Satış Paketleri

### Paket 1: Yerel Görünürlük

Hedef:

- Küçük işletme, lokal kampanya, kısa dönem duyuru.

Slotlar:

- `sidebar-bottom`
- `article-bottom`

Önerilen süre:

- 7 gün
- 15 gün
- 30 gün

Teslim edilecekler:

- 1 banner görseli
- 1 hedef link
- Başlangıç/bitiş tarihi

### Paket 2: Gündem Sponsoru

Hedef:

- Daha görünür yerel işletme, etkinlik, kampanya.

Slotlar:

- `sidebar-top`
- `between-news`

Önerilen süre:

- 15 gün
- 30 gün

Teslim edilecekler:

- 2 banner varyasyonu
- Ana hedef link
- Kampanya metni veya CTA

### Paket 3: Haber Detay Sponsoru

Hedef:

- Haber okuruna doğrudan ulaşmak isteyen kurum/işletme.

Slotlar:

- `article-top`
- `article-bottom`

Önerilen süre:

- 15 gün
- 30 gün

Teslim edilecekler:

- Haber detay üst banner
- Haber detay alt CTA banner
- UTM'li hedef link

### Paket 4: Ana Sponsor

Hedef:

- Büyük kampanya, kurumsal görünürlük, şehir ölçekli marka algısı.

Slotlar:

- `header`
- `between-news`
- `sidebar-top`
- `article-top`

Önerilen süre:

- 7 gün yüksek görünürlük
- 15 gün kampanya
- 30 gün ana sponsorluk

Teslim edilecekler:

- Header banner
- Akış içi banner
- Sidebar banner
- Haber detay banner
- UTM'li hedef link seti

### Paket 5: AdSense Dolgu Paketi

Hedef:

- Satılmamış reklam alanlarını boş bırakmamak.

Slotlar:

- Tüm slotlar için ayrı AdSense unit önerilir.

Önerilen sıra:

- `900+`

Not:

- Bu paket müşteri/okur açısından görünür bir sponsorluk değil, envanter dolum mekanizmasıdır.

## Müşteriden İstenecek Materyal Listesi

Manuel reklam için:

- Reklam veren adı
- Kampanya başlığı
- Hedef link
- Kampanya başlangıç ve bitiş tarihi
- Logo
- Desktop banner görseli
- Opsiyonel mobil banner görseli
- Varsa telefon/WhatsApp
- Varsa UTM parametreli link

Görsel yoksa:

- ADH tasarım ekibi slot ölçüsüne uygun banner hazırlamalıdır.
- Görsel hazırlanmadan reklam aktif edilmemelidir.

AdSense için:

- Onaylı AdSense hesabı
- `ca-pub-...` Client ID
- Her slot için ayrı Slot ID
- Canlı alan adı doğrulaması

## Admin Operasyon Akışı

1. Reklam veren bilgisi alınır.
2. Slot ve paket seçilir.
3. Görsel/link veya AdSense Slot ID hazırlanır.
4. Admin panelden reklam kaydı açılır.
5. `Yayın Durumu` sütunu kontrol edilir.
6. Public site desktop ve mobil kontrol edilir.
7. Kampanya sonunda kayıt pasife alınır veya bitiş tarihiyle otomatik düşer.

## UTM Standardı

Manuel reklam linkleri için önerilen UTM formatı:

```text
https://reklamveren-site.com/kampanya?utm_source=adh&utm_medium=banner&utm_campaign=kampanya_adi&utm_content=slot_adi
```

Örnek:

```text
https://example.com/kampanya?utm_source=adh&utm_medium=banner&utm_campaign=mayis_kampanyasi&utm_content=header
```

## Canlıya Alım Checklist

- Admin reklam listesi açılıyor mu?
- `Yayın Durumu` doğru mu?
- Manuel banner görseli publicte 200 dönüyor mu?
- Link yeni sekmede doğru hedefe gidiyor mu?
- AdSense Client ID girildi mi?
- AdSense Slot ID girildi mi?
- Header reklam mobilde taşma yapıyor mu?
- Haber detay reklamı haber metnini bölmeden görünüyor mu?
- Footer reklam footer ile çakışıyor mu?
- Boş/eksik reklam publicte gizleniyor mu?
- Impression/click sayacı beklenen şekilde artıyor mu?

## Uygulanan İyileştirmeler

- Manuel banner için ayrı `desktop_image_path` ve `mobile_image_path` alanları eklendi.
- Public render tarafında mobil görsel varsa `<picture>` / `<source media="(max-width: 767px)">` kullanılır.
- Eski `image_path` alanı geriye uyum için desktop fallback olarak korunur.
- Admin panelde `Desktop Banner Görseli` ve `Mobil Banner Görseli` ayrı alanlar olarak yönetilir.
- Slot bazlı maksimum yükseklik ve oran kuralları public reklam bileşenine eklendi.
- Admin reklam formunda slot ölçü rehberi ve AdSense Client ID hazır/eksik uyarısı eklendi.

## Reklam Ticari Operasyon Backlog'u

Bu maddeler v1 yayın/yönetim hazırlığının blocker'ı değildir. Reklam satış operasyonu büyüdükçe ayrı batch olarak ele alınmalıdır.

| Öncelik | Madde | Amaç | Kabul Kriteri |
|---|---|---|---|
| P2 | Reklam veren bazlı kayıt modeli | Aynı reklam verene ait kampanyaları tek müşteri profili altında izlemek | Reklam kaydı reklam verenle ilişkilendirilir; firma adı, iletişim ve fatura/not alanı yönetilir |
| P2 | Kampanya modeli | Bir kampanyanın birden fazla slot, tarih ve kreatif varyantını birlikte yönetmek | Kampanya altında birden çok reklam slotu planlanabilir; kampanya aktif/pasif kararı alt kayıtları etkiler |
| P2 | Reklam performans raporu | Satış sonrası gösterim, tıklama ve CTR kanıtı sunmak | Tarih aralığına göre gösterim, tıklama, CTR, slot ve kampanya kırılımı listelenir |
| P2 | CSV/PDF dışa aktarım | Reklam verene dönemsel performans raporu vermek | Seçili kampanya veya reklam veren için rapor dışa aktarılır |
| P3 | Otomatik UTM üretimi | Reklam linklerinde ölçüm standardı sağlamak | Slot, kampanya ve reklam veren adına göre `utm_source=adh`, `utm_medium=banner`, `utm_campaign`, `utm_content` otomatik önerilir |
| P3 | AdSense/manual dashboard widget | Operatöre envanter doluluk ve gelir modeli ayrımını göstermek | Dashboard'da manuel/AdSense aktif slot sayısı, eksik slotlar ve hazır olmayan reklamlar özetlenir |
| P3 | Kreatif teslim checklist'i | Hatalı görsel ölçüsü, link veya tarihle yayın açılmasını azaltmak | Admin formunda görsel, link, tarih, mobil kreatif ve onay notu için tamamlandı sinyali gösterilir |

## Sonuç

Mevcut sistem reklam operasyonu için teknik olarak kullanılabilir durumdadır. Profesyonel işletim için ana şart, her kampanya kaydının `Yayın Durumu` üzerinden kontrol edilmesi ve gerçek reklam materyali girilmeden publicte görünür reklam açılmamasıdır.
