# ADH Dark Mode Smoke - 2026-05-11

## Scope

Customer demo scope degil; mevcut local public/admin tablarinda dark mode uyumlulugu kontrol edildi.

Kontrol edilen yuzeyler:

- `/`
- `/kategori/gundem`
- Haber detay sayfasi
- `/sayfa/hakkimizda`
- `/sayfa/gizlilik-politikasi`
- `/sayfa/cerez-politikasi`
- `/iletisim`
- `/admin/login`
- `/admin`
- `/admin/news-articles`
- `/admin/advertisements`
- `/admin/layout-studio`
- `/admin/media-library`
- `/admin/general-settings`
- `/admin/integration-settings`
- `/admin/iha-health`
- `/admin/iha-sync-logs`
- `/admin/analytics`
- `/admin/news-articles/create`
- `/admin/news-articles/1/edit`
- `/admin/advertisements/create`
- `/admin/pages/create`
- `/admin/pages/2/edit`
- `/admin/categories/create`
- `/admin/categories/1/edit`
- `/admin/tags/create`
- `/admin/tags/1/edit`
- `/admin/local-info-entries/create`
- `/admin/header-themes/create`
- `/admin/seo-settings`
- `/admin/email-settings`

## Finding

### PASS - Public genel dark mode

Ana sayfa, kategori, haber detay ve iletisim sayfalari dark mode'da okunur zemin, kontrastli metin ve tutarli kart yuzeyiyle aciliyor.

### FIXED - Statik sayfa kurumsal navigasyon karti acik zeminde kaliyordu

Statik bilgi sayfalarindaki `Kurumsal Navigasyon` karti dark mode'da acik/gri zeminde kalarak genel koyu tema ile uyumsuz gorunuyordu.

Duzeltme:

- `resources/views/pages/show.blade.php`
- Aside kartinin dark mode zemini `dark:bg-adh-blue` ile sabitlendi.

### PASS - Admin login dark mode

`/admin/login` dark mode yuzeyi okunur, form alanlari kontrastli ve hizalama sorunu gorulmedi.

### PASS - Admin ana operasyon yuzeyleri

Dashboard, haber listesi, reklam listesi, yerlesim studyosu, genel ayarlar, entegrasyon ayarlari, IHA saglik merkezi, IHA senkron kayitlari ve performans sayfalarinda kritik acik zemin / okunamaz metin / hizalama blocker'i gorulmedi.

### FIXED - Medya Kutuphanesi filtre butonu dark mode'da beyaz kaliyordu

`/admin/media-library` sayfasinda `Kullanilmayanlari goster` butonu ve gorunum toggle alanlari dark mode'da beyaz zemin fallback'ine dusuyordu.

Duzeltme:

- `resources/views/filament/pages/media-library.blade.php`
- `resources/css/admin-guide.css`
- Medya kontrol butonlari `admin-media-*` class'lariyla admin tema degiskenlerine baglandi.

Son dogrulama:

- `Kullanilmayanlari goster` buton zemini: `rgba(15, 23, 42, 0.9)`
- Buton metni: `rgb(248, 250, 252)`
- Aktif gorunum toggle zemini: `rgb(251, 191, 36)`

### PASS - Admin create/edit form dark mode

Asagidaki form yuzeylerinde kritik acik zemin, okunamaz yazi, bozuk rich editor toolbar'i veya belirgin hizalama problemi gorulmedi:

- Haber olustur / duzenle
- Reklam olustur
- Sayfa olustur / duzenle
- Kategori olustur / duzenle
- Etiket olustur / duzenle
- Yerel Bilgi olustur
- Milli Gun Temasi olustur
- SEO ayarlari
- E-posta ayarlari

Not: Bu tur yalnizca gorsel/read-only form acilis kontroludur; kaydetme/silme aksiyonlari tetiklenmedi.

## Build / Cache Note

`npm run build` sonrasi Laravel ilk istekte eski Vite CSS asset referansini dondu. `php artisan optimize:clear` ile view/config/cache temizlendi ve HTML yeni manifest asset'ine gecti.

Dogurlanan asset:

- `build/assets/app-B3RM0VS8.css`
- `build/assets/app-DVmLbeO5.js`

## Verification

- `npm run build` PASS
- `php artisan optimize:clear` PASS
- `php artisan test tests\Feature\Public\PublicPagesTest.php tests\Feature\Public\BrandingSettingsTest.php` PASS, 22 tests / 123 assertions
- `php artisan test tests\Feature\Filament\AdminSettingsOperationsFinalSmokeTest.php tests\Feature\Filament\AnalyticsAndOperationsPagesTest.php tests\Feature\Filament\IhaHealthPageTest.php tests\Feature\Filament\AdvertisementResourceCrudTest.php` PASS, 10 tests / 104 assertions
- `php artisan test tests\Feature\Filament` PASS, 58 tests / 578 assertions
- `php artisan deploy:verify --base-url=http://127.0.0.1:8000` PASS
- Browser visual smoke PASS for listed routes

## Status

Local dark mode smoke result: PASS

Remaining: Production sunucuya gecince ayni dark-mode smoke canli build ve production asset cache uzerinden tekrarlanmalidir.
