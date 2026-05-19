# ADH SEO Güçlendirme Uygulama Raporu

## Hedef

Türkçe odaklı Adıyaman yerel haber liderliği için teknik SEO temelini güçlendirmek ve editoryal SEO üretim hattını netleştirmek.

## Uygulanan Teknik Omurga

- Sitemap sistemi sitemap index modeline çekildi:
  - `/sitemap.xml`
  - `/sitemap-pages.xml`
  - `/sitemap-categories.xml`
  - `/sitemap-articles.xml`
  - `/sitemap-news.xml`
- Google News sitemap yalnız son 48 saat Türkçe haberleri üretir.
- Production ortamında sitemap/canonical URL üretimi HTTPS standardına zorlandı.
- `robots.txt` canonical sitemap index URL'sini gösterir.
- Haber detay `NewsArticle` JSON-LD yapısı güçlendirildi.
- `BreadcrumbList`, `Organization` ve `WebSite/SearchAction` structured data çıktıları eklendi.
- Placeholder/stok görsel haber schema görseli olarak kullanılmaz.
- Admin SEO ekranına canonical, sitemap, News sitemap ve son haber SEO sağlık göstergeleri eklendi.
- Admin dashboard'a SEO Sağlığı kartları eklendi.
- Sitemap üretimi günlük yerine 30 dakikada bir çalışacak şekilde scheduler'a bağlandı.

## Editoryal SEO Anahtar Kelime Kümeleri

- Ana yerel sorgular:
  - Adıyaman haberleri
  - Adıyaman son dakika
  - Adıyaman gündem
  - Adıyaman asayiş
  - Adıyaman trafik kazası
  - Adıyaman deprem
- İlçe sorguları:
  - Besni haberleri
  - Kahta haberleri
  - Gölbaşı haberleri
  - Gerger haberleri
  - Çelikhan haberleri
  - Sincik haberleri
  - Samsat haberleri
  - Tut haberleri
- Kategori sorguları:
  - Adıyaman sağlık haberleri
  - Adıyaman eğitim haberleri
  - Adıyaman belediye haberleri
  - Adıyaman ekonomi haberleri
  - Adıyaman spor haberleri

## Haftalık İçerik Üretim Takvimi

Haftalık hedef: 3-5 kaliteli yerel evergreen/editoryal içerik.

İlk içerik seti:

1. Adıyaman nöbetçi eczane nasıl takip edilir?
2. Adıyaman hava durumu ve yerel uyarılar
3. Kahta son dakika haberleri
4. Adıyaman deprem ve afet rehberi
5. Adıyaman belediye duyuruları

İçerik formatı:

- 900-1400 kelime arası.
- Güncel haber listesine iç link.
- İlgili kategori/ilçe landing sayfasına iç link.
- Son güncelleme tarihi görünür.
- Gereksiz anahtar kelime doldurma yapılmaz.

## Search Console ve Analytics Checklist

- Domain property doğrulanacak.
- `/sitemap.xml` submit edilecek.
- `/sitemap-news.xml` ayrıca submit edilecek.
- Ana sayfa, bir kategori, bir ilçe ve son 3 haber URL Inspection ile kontrol edilecek.
- Performans raporunda ilk takip seti:
  - Sorgular: Adıyaman haberleri, Adıyaman son dakika, Kahta haberleri, Besni haberleri.
  - Sayfalar: ana sayfa, kategori/gundem, kategori/asayis, il/adiyaman, il/kahta.

## Kalan Operasyonel Notlar

- Google sıralaması garanti edilemez; ölçülebilir hedef ilk 30-60 gün içinde indeks kapsaması, haber keşif hızı ve yerel sorgularda gösterim artışıdır.
- En/ku sayfalar bu fazda SEO büyüme hedefi değildir; Türkçe sitemap ana kaynak kabul edilir.
- Gerçek Search Console ve Analytics doğrulaması erişim geldikten sonra tamamlanmalıdır.
