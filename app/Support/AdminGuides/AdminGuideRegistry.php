<?php

namespace App\Support\AdminGuides;

use App\Filament\Pages\Analytics;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EmailSettings;
use App\Filament\Pages\GeneralSettings;
use App\Filament\Pages\IhaHealth;
use App\Filament\Pages\IntegrationSettings;
use App\Filament\Pages\LayoutStudio;
use App\Filament\Pages\MediaLibrary;
use App\Filament\Pages\SeoSettings;
use App\Filament\Resources\AdvertisementResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\HeaderThemeResource;
use App\Filament\Resources\IhaSyncLogResource;
use App\Filament\Resources\LocalInfoEntryResource;
use App\Filament\Resources\NewsArticleResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Support\AdminPrivileges;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminGuideRegistry
{
    private const ROLE_RANKS = [
        'writer' => 1,
        'editor' => 2,
        'super_admin' => 3,
    ];

    public function catalogForUser(?User $user): array
    {
        return array_values(array_filter(
            array_map(fn (array $guide): array => $this->normalizeGuide($guide, $user), $this->definitions($user)),
            fn (array $guide): bool => $this->isVisibleToUser($guide, $user)
        ));
    }

    public function forRequest(Request $request, ?User $user): ?array
    {
        foreach ($this->catalogForUser($user) as $guide) {
            if ($this->matchesRequest($request, $guide)) {
                return $guide;
            }
        }

        return null;
    }

    public function forPage(?string $pageClass, ?User $user): ?array
    {
        if (blank($pageClass)) {
            return null;
        }

        foreach ($this->catalogForUser($user) as $guide) {
            if (($guide['page_match'] ?? null) === $pageClass) {
                return $guide;
            }
        }

        return null;
    }

    public function findVisibleByKey(string $guideKey, ?User $user): ?array
    {
        foreach ($this->catalogForUser($user) as $guide) {
            if (($guide['guide_key'] ?? null) === $guideKey) {
                return $guide;
            }
        }

        return null;
    }

    public function currentTier(?User $user): string
    {
        if (! $user?->exists || ! (bool) $user->is_active) {
            return 'guest';
        }

        if (AdminPrivileges::canPublishConfiguration($user)) {
            return 'super_admin';
        }

        if (AdminPrivileges::canAccessConfiguration($user)) {
            return 'editor';
        }

        if (AdminPrivileges::canAccessAdminPanel($user)) {
            return 'writer';
        }

        return 'guest';
    }

    private function normalizeGuide(array $guide, ?User $user): array
    {
        $minimumTier = $guide['minimum_tier'] ?? 'writer';

        return [
            'guide_key' => $guide['guide_key'],
            'title' => $guide['title'],
            'summary' => $guide['summary'],
            'why_it_matters' => $guide['why_it_matters'],
            'impact' => $guide['impact'],
            'minimum_tier' => $minimumTier,
            'minimum_tier_rank' => $this->tierRank($minimumTier),
            'page_match' => $guide['page_match'] ?? null,
            'route_names' => $guide['route_names'] ?? [],
            'path_patterns' => $guide['path_patterns'] ?? [],
            'quick_actions' => array_values(array_filter(
                $guide['quick_actions'] ?? [],
                fn (array $action): bool => $this->tierRank($this->currentTier($user)) >= $this->tierRank($action['minimum_tier'] ?? 'writer')
            )),
            'steps' => $guide['steps'] ?? [],
            'coachmarks' => $guide['coachmarks'] ?? [],
            'related_guides' => $guide['related_guides'] ?? [],
        ];
    }

    private function matchesRequest(Request $request, array $guide): bool
    {
        $routeName = $request->route()?->getName();

        foreach ($guide['route_names'] ?? [] as $routePattern) {
            if ($routeName !== null && Str::is($routePattern, $routeName)) {
                return true;
            }
        }

        foreach ($guide['path_patterns'] ?? [] as $pathPattern) {
            if ($request->is($pathPattern)) {
                return true;
            }
        }

        return false;
    }

    private function isVisibleToUser(array $guide, ?User $user): bool
    {
        return $this->tierRank($this->currentTier($user)) >= ($guide['minimum_tier_rank'] ?? self::ROLE_RANKS['writer']);
    }

    private function tierRank(string $tier): int
    {
        return self::ROLE_RANKS[$tier] ?? 0;
    }

    private function definitions(?User $user): array
    {
        return [
            $this->dashboardGuide(),
            $this->newsGuide(),
            $this->categoryGuide(),
            $this->tagGuide(),
            $this->pageGuide(),
            $this->advertisementGuide(),
            $this->localInfoGuide(),
            $this->layoutStudioGuide($user),
            $this->mediaGuide(),
            $this->analyticsGuide(),
            $this->ihaHealthGuide(),
            $this->integrationGuide(),
            $this->generalSettingsGuide(),
            $this->seoSettingsGuide(),
            $this->emailSettingsGuide(),
            $this->userManagementGuide(),
            $this->headerThemeGuide(),
            $this->ihaSyncLogsGuide(),
        ];
    }

    private function dashboardGuide(): array
    {
        return [
            'guide_key' => 'dashboard-overview',
            'title' => 'Haber Masası',
            'summary' => 'Acil dikkat isteyen sinyalleri, yayın kuyruğunu ve vitrin farkını tek ekranda toplar.',
            'why_it_matters' => 'Güne bu ekranla başlamak, haber akışını ve İHA sağlığını dağılmadan yönetmenizi sağlar.',
            'impact' => 'Bu ekran doğrudan public değişiklik yapmaz; editoryal ve operasyonel öncelikleri sıralar.',
            'minimum_tier' => 'writer',
            'page_match' => Dashboard::class,
            'route_names' => ['filament.admin.pages.dashboard'],
            'path_patterns' => ['admin'],
            'quick_actions' => [
                [
                    'label' => 'Yeni haber oluştur',
                    'description' => 'Yeni bir içerik akışını buradan başlatın.',
                    'url' => NewsArticleResource::getUrl('create', panel: 'admin'),
                ],
                [
                    'label' => 'Yerleşim Stüdyosu',
                    'description' => 'Anasayfa blok sırası ve vitrin kararlarını kontrol edin.',
                    'url' => LayoutStudio::getUrl(panel: 'admin'),
                    'minimum_tier' => 'editor',
                ],
                [
                    'label' => 'İHA Sağlığı',
                    'description' => 'İçerik akışında tazelik ve hata kontrolü yapın.',
                    'url' => IhaHealth::getUrl(panel: 'admin'),
                    'minimum_tier' => 'super_admin',
                ],
            ],
            'steps' => [
                ['title' => 'Önce acil sinyalleri okuyun', 'description' => 'Stale İHA, failed job ve çeviri backlog’u burada en üstte toplanır.'],
                ['title' => 'Sonra yayın kuyruğuna geçin', 'description' => 'Taslak, zamanlı yayın ve vitrin adayları tek listede görünür.'],
                ['title' => 'Vitrin ve trafik bağlamını birlikte okuyun', 'description' => 'Anasayfa durumu ile ilgi toplayan içerik aynı akışta görünür.'],
            ],
            'coachmarks' => [
                ['anchor' => 'dashboard.attention', 'title' => 'Şimdi dikkat gerekenler', 'body' => 'Bugün akışı tıkayan sinyaller bu alanda en üstte görünür.'],
                ['anchor' => 'dashboard.queue', 'title' => 'Yayın kuyruğu', 'body' => 'Taslak, zamanlı yayın ve vitrin adayları tek tabloda toplanır.'],
                ['anchor' => 'dashboard.health', 'title' => 'İHA ve çeviri akışı', 'body' => 'Senkron özeti, tazelik farkı ve backlog burada birlikte okunur.'],
                ['anchor' => 'dashboard.publish', 'title' => 'Anasayfa ve vitrin', 'body' => 'Taslak ile canlı arasındaki farkı ve son yayın izini burada görürsünüz.'],
                ['anchor' => 'dashboard.guide-entry', 'title' => 'Öğretici girişi', 'body' => 'Panel akışını hızlıca öğrenmek için bu tetikleyiciyi kullanın.'],
            ],
            'related_guides' => ['news-desk', 'layout-studio', 'iha-health'],
        ];
    }

    private function newsGuide(): array    {
        return [
            'guide_key' => 'news-desk',
            'title' => 'Haber Havuzu',
            'summary' => 'Haber listeleme, yeni haber oluşturma ve mevcut haberleri düzenleme akışını tek rehberde toplar.',
            'why_it_matters' => 'Başlık, slug, yayın durumu, editoryal skor ve vitrin görünürlüğü doğrudan public yüzeyi etkiler.',
            'impact' => 'Bu değişiklik sitede görünür. Yanlış yayın durumu veya kategori seçimi anasayfa ve liste akışını bozar.',
            'minimum_tier' => 'writer',
            'route_names' => [
                'filament.admin.resources.news-articles.index',
                'filament.admin.resources.news-articles.create',
                'filament.admin.resources.news-articles.edit',
            ],
            'path_patterns' => ['admin/news-articles', 'admin/news-articles/create', 'admin/news-articles/*/edit'],
            'quick_actions' => [
                ['label' => 'Haber listesine git', 'description' => 'Yayın, taslak ve arşiv içeriklerini birlikte yönetin.', 'url' => NewsArticleResource::getUrl(panel: 'admin')],
                ['label' => 'Yeni haber oluştur', 'description' => 'Manuel haber üretim akışını başlatın.', 'url' => NewsArticleResource::getUrl('create', panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Başlık ve slug uyumunu kontrol edin', 'description' => 'Public URL ve SEO görünürlüğü için başlık, slug ve özet birlikte düşünülmelidir.'],
                ['title' => 'Kategori ve yayın ayarlarını dikkatle seçin', 'description' => 'Yanlış kategori veya durum seçimi, haberin yanlış bölümde görünmesine yol açar.'],
                ['title' => 'Editoryal puanı bağlamla okuyun', 'description' => 'Bu skor tek başına karar vermez; manşet ve anasayfa için destek sinyalidir.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-header', 'title' => 'Sayfa başlığı', 'body' => 'Liste, yeni kayıt ve düzenleme ekranlarında ana bağlamı bu üst başlıktan anlarsınız.'],
                ['selector' => '.fi-ta', 'title' => 'Haber tablosu', 'body' => 'Filtreleme, sıralama ve toplu işlemler bu tablo üzerinden yürür.'],
                ['selector' => '.fi-fo', 'title' => 'Haber formu', 'body' => 'Create ve edit ekranlarında haberin temel, görsel ve SEO alanları burada toplanır.'],
                ['selector' => '[role="tablist"]', 'title' => 'Sekmeler', 'body' => 'Form sekmeleri haber düzenleme akışını parçalara ayırır; temel bilgilerden sonra yayın ve SEO alanını kontrol edin.'],
            ],
            'related_guides' => ['categories', 'tags', 'layout-studio'],
        ];
    }

    private function categoryGuide(): array
    {
        return [
            'guide_key' => 'categories',
            'title' => 'Kategori Yönetimi',
            'summary' => 'Kategori başlıklarını, sıralamayı ve hiyerarşiyi düzenlemenizi sağlar.',
            'why_it_matters' => 'Kategori yapısı anasayfa bloklarını, haber listelerini ve breadcrumb akışını etkiler.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.categories.*'],
            'path_patterns' => ['admin/categories*'],
            'quick_actions' => [
                ['label' => 'Kategori listesi', 'description' => 'Mevcut kategori yapısını gözden geçirin.', 'url' => CategoryResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Önce aktif/pasif durumunu kontrol edin', 'description' => 'Pasif kategoriler public kısayollarda ve listelerde görünmez.'],
                ['title' => 'Sıralama mantığını koruyun', 'description' => 'Kök kategoriler ve öncelik sırası anasayfa modüllerinin beklediği akışı desteklemelidir.'],
                ['title' => 'Çok dilli adı boş bırakmayın', 'description' => 'TR alanı dolu olmalı; diğer diller boşsa fallback devreye girer.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Kategori tablosu', 'body' => 'Sıralama ve görünürlük kararlarını bu liste üzerinden takip edersiniz.'],
                ['selector' => '.fi-fo', 'title' => 'Kategori formu', 'body' => 'Düzenleme ekranında isim, slug ve görünürlük alanları burada toplanır.'],
            ],
            'related_guides' => ['news-desk', 'layout-studio'],
        ];
    }

    private function tagGuide(): array
    {
        return [
            'guide_key' => 'tags',
            'title' => 'Etiket Yönetimi',
            'summary' => 'Etiketlerin arşiv, arama ve keşif yüzeylerinde tutarlı kalmasını sağlar.',
            'why_it_matters' => 'Dağınık etiket yapısı arama keşfini ve içerik kümelerini zayıflatır.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.tags.*'],
            'path_patterns' => ['admin/tags*'],
            'quick_actions' => [
                ['label' => 'Etiket listesi', 'description' => 'Etiketleri temizleyin ve gerekirse birleştirme akışını kullanın.', 'url' => TagResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Tekrarlayan etiketleri temizleyin', 'description' => 'Benzer anlamlı etiketler tek bir çatı altında toplanmalıdır.'],
                ['title' => 'Haber akışıyla birlikte düşünün', 'description' => 'Etiketlerin amacı içerik kümelerini güçlendirmek olmalı; çok ince taneli etiketlerden kaçının.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Etiket tablosu', 'body' => 'Birleştirme ve görünürlük kararları bu listede yönetilir.'],
            ],
            'related_guides' => ['news-desk'],
        ];
    }

    private function pageGuide(): array
    {
        return [
            'guide_key' => 'static-pages',
            'title' => 'Statik Sayfalar',
            'summary' => 'Kurumsal sayfa, yasal metin ve landing içeriklerini yönetmenizi sağlar.',
            'why_it_matters' => 'İletişim ve yasal sayfalar güven sinyali taşır; yanlış içerik kurumsal algıyı zedeler.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.pages.*'],
            'path_patterns' => ['admin/pages*'],
            'quick_actions' => [
                ['label' => 'Sayfa listesi', 'description' => 'Kurumsal içerikleri gözden geçirin.', 'url' => PageResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Başlık ve slug eşleşmesini koruyun', 'description' => 'Public rota ve SEO görünürlüğü buna bağlıdır.'],
                ['title' => 'Metinleri locale bazında düşünün', 'description' => 'TR alanı tam olmalı; EN ve KU eksikse kontrollü fallback kullanılacaktır.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Sayfa listesi', 'body' => 'Hakkımızda, KVKK ve diğer statik içerikler burada listelenir.'],
                ['selector' => '.fi-fo', 'title' => 'Sayfa formu', 'body' => 'Başlık, içerik ve görünürlük alanlarını bu formdan yönetin.'],
            ],
            'related_guides' => ['general-settings', 'seo-settings'],
        ];
    }

    private function advertisementGuide(): array
    {
        return [
            'guide_key' => 'advertisements',
            'title' => 'Reklam Yönetimi',
            'summary' => 'Reklam slotları, yayın tarihleri ve aktif/pasif davranışını yönetir.',
            'why_it_matters' => 'Boş veya yanlış zamanlanmış reklam kaydı public kırık görüntü riski yaratabilir.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.advertisements.*'],
            'path_patterns' => ['admin/advertisements*'],
            'quick_actions' => [
                ['label' => 'Reklam listesi', 'description' => 'Aktif kampanya ve slot doluluklarını kontrol edin.', 'url' => AdvertisementResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Önce slot ve tarih penceresini doğrulayın', 'description' => 'Yanlış slot veya tarih, reklamın görünmemesine yol açar.'],
                ['title' => 'Boş medya kaydı bırakmayın', 'description' => 'Aktif manuel banner görselsizse public tarafta gizlenir; yayın durumu sütununu kontrol edin.'],
                ['title' => 'AdSense için iki parçayı birlikte tamamlayın', 'description' => 'Client ID Entegrasyon Ayarları ekranında, Slot ID ise reklam kaydında tutulur. İkisinden biri eksikse reklam görünmez.'],
                ['title' => 'Slot ölçü rehberine göre kreatif hazırlayın', 'description' => 'Admin formundaki slot rehberi public maksimum yükseklik ve oran sınırlarını gösterir.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Reklam tablosu', 'body' => 'Slot, tür, yayın durumu, tarih ve aktiflik kontrolleri burada özetlenir.'],
                ['selector' => '.fi-fo', 'title' => 'Reklam formu', 'body' => 'Manuel banner görsel/link veya Google AdSense slot bilgisi bu formda tanımlanır.'],
            ],
            'related_guides' => ['analytics'],
        ];
    }

    private function localInfoGuide(): array
    {
        return [
            'guide_key' => 'local-info',
            'title' => 'Yerel Bilgi Girdileri',
            'summary' => 'Kesinti, yol durumu ve hızlı duyuru gibi yerel bilgi bloklarını yönetir.',
            'why_it_matters' => 'Bu ekran editoryal içeriği destekleyen anlık servis bilgilerini besler.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.local-info-entries.*'],
            'path_patterns' => ['admin/local-info-entries*'],
            'quick_actions' => [
                ['label' => 'Yerel bilgi listesi', 'description' => 'Aktif servis duyurularını yönetin.', 'url' => LocalInfoEntryResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Zaman penceresini net tanımlayın', 'description' => 'Geçerlilik süresi dolan kayıtlar public güven duygusunu zedeler.'],
                ['title' => 'Başlıkları kısa ve işlevsel tutun', 'description' => 'Bu bloklar haber değil, hızlı servis bilgisidir.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Yerel bilgi tablosu', 'body' => 'Aktif kayıtlar ve görünürlük durumu bu listede takip edilir.'],
            ],
            'related_guides' => ['dashboard-overview'],
        ];
    }

    private function layoutStudioGuide(?User $user): array
    {
        $quickActions = [
            ['label' => 'Taslağı aç', 'description' => 'Blok sırası ve görünüm kararlarını düzenleyin.', 'url' => LayoutStudio::getUrl(panel: 'admin')],
        ];

        if ($this->currentTier($user) === 'super_admin') {
            $quickActions[] = [
                'label' => 'Yayına alma notu',
                'description' => 'Taslak kaydı sonrası önizleme ile doğrulayıp canlıya alabilirsiniz.',
                'url' => LayoutStudio::getUrl(panel: 'admin'),
                'minimum_tier' => 'super_admin',
            ];
        }

        return [
            'guide_key' => 'layout-studio',
            'title' => 'Yerleşim Stüdyosu',
            'summary' => 'Anasayfa blok sırası, görünüm varyantları ve yayın akışını tek ekranda yönetir.',
            'why_it_matters' => 'Bu ekran anasayfanın editoryal yoğunluğunu ve kullanıcı deneyimini doğrudan belirler.',
            'impact' => 'Bu değişiklik sitede görünür. Publish ve rollback akışı operasyonel dikkat gerektirir.',
            'minimum_tier' => 'editor',
            'page_match' => LayoutStudio::class,
            'route_names' => ['filament.admin.pages.layout-studio'],
            'path_patterns' => ['admin/layout-studio'],
            'quick_actions' => $quickActions,
            'steps' => [
                ['title' => 'Önce modül akışını kurun', 'description' => 'Hangi blokların açık olacağına ve hangi sırada akacağına burada karar verin.'],
                ['title' => 'Seçili modül ayarlarını düzenleyin', 'description' => 'Başlık, CTA, yoğunluk ve cihaz görünürlüğü tek blok üzerinden ayarlanır.'],
                ['title' => 'Önizleme olmadan yayın yapmayın', 'description' => 'TR/EN/KU önizleme bağlantıları taslak kalitenin son kontrol noktasıdır.'],
            ],
            'coachmarks' => [
                ['anchor' => 'layout.hero', 'title' => 'Yerleşim özeti', 'body' => 'Taslak, canlı sürüm ve önizleme linkleri burada toplanır.'],
                ['anchor' => 'layout.modules', 'title' => 'Modül akışı', 'body' => 'Blok sırası ve aktif/pasif kararları soldaki akıştan yönetilir.'],
                ['anchor' => 'layout.settings', 'title' => 'Seçili modül ayarları', 'body' => 'Her blok için başlık, CTA, yoğunluk ve cihaz görünürlüğü bu panelde değişir.'],
                ['anchor' => 'layout.appearance', 'title' => 'Global görünüm', 'body' => 'Sayfanın genel tonunu etkileyen kontrollü appearance ayarları burada bulunur.'],
                ['anchor' => 'layout.publish', 'title' => 'Yayın ve geri alma', 'body' => 'Taslağı kaydedin, önizleyin ve sadece doğruysa yayınlayın.'],
            ],
            'related_guides' => ['dashboard-overview', 'news-desk', 'header-themes'],
        ];
    }

    private function mediaGuide(): array
    {
        return [
            'guide_key' => 'media-library',
            'title' => 'Medya Kütüphanesi',
            'summary' => 'Görsel ve medya dosyalarının düzenli, tekrar kullanılabilir bir yapıda kalmasını sağlar.',
            'why_it_matters' => 'Bozuk medya yönetimi haber ve reklam akışlarında kalite kaybına yol açar.',
            'impact' => 'Bu değişiklik sitede görünür olabilir; ancak asıl etkisi içerik operasyonunu hızlandırmaktır.',
            'minimum_tier' => 'editor',
            'page_match' => MediaLibrary::class,
            'route_names' => ['filament.admin.pages.media-library'],
            'path_patterns' => ['admin/media-library'],
            'quick_actions' => [
                ['label' => 'Medya kütüphanesi', 'description' => 'Yüklenen medya dosyalarını gözden geçirin.', 'url' => MediaLibrary::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Dosya kalitesi ve isimlendirmeyi kontrol edin', 'description' => 'Tekrar kullanım için açıklayıcı dosya adları ve temiz medya seçimi önemlidir.'],
                ['title' => 'Public etkili medya değişikliklerini temkinli yapın', 'description' => 'Kullanımdaki medya kaldırılırsa haber ya da reklam yüzeyinde boş alan riski doğar.'],
            ],
            'coachmarks' => [
                ['anchor' => 'media.hero', 'title' => 'Kütüphane özeti', 'body' => 'Bu üst bölüm medya kütüphanesinin amacını ve kullanım sınırlarını özetler.'],
                ['anchor' => 'media.browser', 'title' => 'Medya tarayıcısı', 'body' => 'Dosya seçimi, yükleme ve yeniden kullanım akışı burada ilerler.'],
            ],
            'related_guides' => ['news-desk', 'advertisements'],
        ];
    }

    private function analyticsGuide(): array
    {
        return [
            'guide_key' => 'analytics',
            'title' => 'Performans ve Analitik',
            'summary' => 'Dönemsel trafik, cihaz kırılımı ve kategori etkisini tek ekrandan okumanızı sağlar.',
            'why_it_matters' => 'Editoryal kararların veriyle desteklenmesi için en hızlı referans ekranıdır.',
            'impact' => 'Bu ekran yalnız operasyonel izleme içindir.',
            'minimum_tier' => 'editor',
            'page_match' => Analytics::class,
            'route_names' => ['filament.admin.pages.analytics'],
            'path_patterns' => ['admin/analytics'],
            'quick_actions' => [
                ['label' => 'Performans ekranı', 'description' => 'Dönemsel görünümü karşılaştırmalı olarak açın.', 'url' => Analytics::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Dönemi net seçin', 'description' => 'Trafik yorumu yapmadan önce gün, hafta ya da özel aralık bağlamını netleştirin.'],
                ['title' => 'Kaynak ve cihaz kırılımını birlikte okuyun', 'description' => 'Sadece toplam görüntüleme değil, trafik kalitesi de önemlidir.'],
                ['title' => 'Export’u karar notu için kullanın', 'description' => 'Ekip içi raporlama veya müşteri sunumu için CSV çıktısını kaydedebilirsiniz.'],
            ],
            'coachmarks' => [
                ['anchor' => 'analytics.hero', 'title' => 'Özet görünüm', 'body' => 'Mevcut dönem ve önceki dönem kıyası bu alanda görünür.'],
                ['anchor' => 'analytics.comparison', 'title' => 'Delta kartları', 'body' => 'Artış ve düşüş yönünü hızlıca okumanızı sağlar.'],
                ['anchor' => 'analytics.sources', 'title' => 'Trafik kaynakları', 'body' => 'Doğrudan, arama ve sosyal kaynak ayrımı burada izlenir.'],
                ['anchor' => 'analytics.devices', 'title' => 'Cihaz kırılımı', 'body' => 'Mobil/tablet/desktop davranışını burada kıyaslarsınız.'],
                ['anchor' => 'analytics.export', 'title' => 'Dışa aktarım', 'body' => 'Karşılaştırmalı görünümü CSV olarak dışarı alabilirsiniz.'],
            ],
            'related_guides' => ['dashboard-overview', 'news-desk'],
        ];
    }

    private function ihaHealthGuide(): array
    {
        return [
            'guide_key' => 'iha-health',
            'title' => 'İHA Sağlık Merkezi',
            'summary' => 'İHA senkron tazeliğini, hata özetlerini ve çeviri backlog’unu yönetir.',
            'why_it_matters' => 'ADH içerik akışının sürekliliği bu ekranın doğru okunmasına bağlıdır.',
            'impact' => 'Bu ekran yalnız operasyonel izleme içindir; ancak buradaki sorunlar public içerik tazeliğini etkiler.',
            'minimum_tier' => 'super_admin',
            'page_match' => IhaHealth::class,
            'route_names' => ['filament.admin.pages.iha-health'],
            'path_patterns' => ['admin/iha-health'],
            'quick_actions' => [
                ['label' => 'Sağlık ekranını aç', 'description' => 'Son başarılı sync, hata ve backlog durumunu görün.', 'url' => IhaHealth::getUrl(panel: 'admin')],
                ['label' => 'Senkron loglarını aç', 'description' => 'Detaylı kayıtları ayrı listede inceleyin.', 'url' => IhaSyncLogResource::getUrl(panel: 'admin'), 'minimum_tier' => 'super_admin'],
            ],
            'steps' => [
                ['title' => 'Önce son başarılı senkrona bakın', 'description' => 'Tazelik gecikmesi burada anında okunur.'],
                ['title' => 'Ardından son hata ve kimlik hazır durumunu kontrol edin', 'description' => 'Upstream ya da credential sorunu varsa önce burada görünür.'],
                ['title' => 'Backlog varsa yeniden kuyruğa alma aksiyonunu düşünün', 'description' => 'Eksik çeviri veya tekrar deneme ihtiyacı bu ekranda özetlenir.'],
            ],
            'coachmarks' => [
                ['anchor' => 'iha.health.summary', 'title' => 'Sağlık özeti', 'body' => 'Son başarılı senkron, freshness lag ve özet sayaçlar burada bulunur.'],
                ['anchor' => 'iha.health.credentials', 'title' => 'Hazırlık durumu', 'body' => 'İHA ve çeviri kimlik bilgilerinin hazır olup olmadığı bu blokta görünür.'],
                ['anchor' => 'iha.health.backlog', 'title' => 'Backlog ve retry okuması', 'body' => 'Eksik çeviri, kuyruk yoğunluğu ve tekrar deneme ihtiyacı burada özetlenir.'],
                ['anchor' => 'iha.health.logs', 'title' => 'Son senkron kayıtları', 'body' => 'Durum, sayaçlar ve güvenli hata metinleri bu tabloda incelenir.'],
            ],
            'related_guides' => ['iha-sync-logs', 'integration-settings', 'dashboard-overview'],
        ];
    }

    private function integrationGuide(): array
    {
        return [
            'guide_key' => 'integration-settings',
            'title' => 'Entegrasyon Ayarları',
            'summary' => 'İHA, Instagram ve harici servis ayarlarını dürüst görünürlükle yönetmenizi sağlar.',
            'why_it_matters' => 'Yanlış entegrasyon ayarı sessiz veri kaybına değil, açık sağlık sinyaline dönüşmelidir.',
            'impact' => 'Bu değişiklik işlem akışını etkiler.',
            'minimum_tier' => 'super_admin',
            'page_match' => IntegrationSettings::class,
            'route_names' => ['filament.admin.pages.integration-settings'],
            'path_patterns' => ['admin/integration-settings'],
            'quick_actions' => [
                ['label' => 'Entegrasyon ayarları', 'description' => 'Credential ve davranış ayarlarını kontrol edin.', 'url' => IntegrationSettings::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Credential alanlarını dikkatle girin', 'description' => 'Eksik veya hatalı değerler sağlık ekranlarında açıkça görünür.'],
                ['title' => 'Operasyonel notları okuyun', 'description' => 'Bazı ayarlar panelde sadece görünürlük içindir; gerçek cron veya queue modeli sabit olabilir.'],
            ],
            'coachmarks' => [
                ['anchor' => 'settings.form', 'title' => 'Ayar formu', 'body' => 'Entegrasyon alanları ve helper text’ler bu formda toplanır.'],
                ['anchor' => 'settings.save', 'title' => 'Kaydet aksiyonu', 'body' => 'Değişiklikleri kaydettikten sonra ilgili sağlık ekranından sonucu doğrulayın.'],
            ],
            'related_guides' => ['iha-health', 'email-settings'],
        ];
    }

    private function generalSettingsGuide(): array
    {
        return [
            'guide_key' => 'general-settings',
            'title' => 'Genel Ayarlar',
            'summary' => 'Site adı, slogan, logo, favicon ve iletişim alanlarını yönetir.',
            'why_it_matters' => 'Bu ekran marka görünürlüğünün ana kaynağıdır; header, footer ve meta katmanı burada beslenir.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'super_admin',
            'page_match' => GeneralSettings::class,
            'route_names' => ['filament.admin.pages.general-settings'],
            'path_patterns' => ['admin/general-settings'],
            'quick_actions' => [
                ['label' => 'Genel ayarlar', 'description' => 'Marka ve iletişim alanlarını yönetin.', 'url' => GeneralSettings::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Önce Türkçe alanları tamamlayın', 'description' => 'Diğer diller boş kalsa bile fallback’in güvenli çalışması için TR alanı tam olmalıdır.'],
                ['title' => 'Logo ve favicon değişikliğini public tarafta doğrulayın', 'description' => 'Bu ekran public header, footer ve meta katmanını besler.'],
            ],
            'coachmarks' => [
                ['anchor' => 'settings.form', 'title' => 'Ayar formu', 'body' => 'Marka ve iletişim alanlarının tamamı bu formda toplanır.'],
                ['anchor' => 'settings.save', 'title' => 'Kaydet', 'body' => 'Kaydettikten sonra public header ve footer görünümünü kontrol edin.'],
            ],
            'related_guides' => ['seo-settings', 'header-themes'],
        ];
    }

    private function seoSettingsGuide(): array
    {
        return [
            'guide_key' => 'seo-settings',
            'title' => 'SEO Ayarları',
            'summary' => 'Site düzeyi SEO başlıkları, açıklamaları ve indeksleme tercihlerini yönetir.',
            'why_it_matters' => 'Yanlış SEO ayarı public görünürlüğü ve indeksleme kalitesini doğrudan etkiler.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'super_admin',
            'page_match' => SeoSettings::class,
            'route_names' => ['filament.admin.pages.seo-settings'],
            'path_patterns' => ['admin/seo-settings'],
            'quick_actions' => [
                ['label' => 'SEO ayarları', 'description' => 'Site düzeyi meta ve indeksleme kararlarını yönetin.', 'url' => SeoSettings::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Site geneli ile içerik bazlı SEO’yu karıştırmayın', 'description' => 'Bu ekran global ayarlar içindir; haber bazlı SEO ayrı form alanlarında yönetilir.'],
                ['title' => 'Robots ve meta değişikliklerini kontrollü yapın', 'description' => 'İndeksleme kararları geniş etkiye sahiptir.'],
            ],
            'coachmarks' => [
                ['anchor' => 'settings.form', 'title' => 'SEO formu', 'body' => 'Global title, description ve robots mantığı bu formda yönetilir.'],
                ['anchor' => 'settings.save', 'title' => 'Kaydet ve doğrula', 'body' => 'Kaydettikten sonra representative sayfalarda meta çıktısını doğrulayın.'],
            ],
            'related_guides' => ['general-settings', 'static-pages'],
        ];
    }

    private function emailSettingsGuide(): array
    {
        return [
            'guide_key' => 'email-settings',
            'title' => 'E-posta Ayarları',
            'summary' => 'SMTP ve gönderici ayarlarını panelden yönetmenizi sağlar.',
            'why_it_matters' => 'Bildirim, test gönderimi ve operasyonel e-posta akışları bu ekrandaki değerlere bağlıdır.',
            'impact' => 'Bu değişiklik işlem akışını etkiler.',
            'minimum_tier' => 'super_admin',
            'page_match' => EmailSettings::class,
            'route_names' => ['filament.admin.pages.email-settings'],
            'path_patterns' => ['admin/email-settings'],
            'quick_actions' => [
                ['label' => 'E-posta ayarları', 'description' => 'SMTP ve gönderici bilgilerini düzenleyin.', 'url' => EmailSettings::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'SMTP ve göndericiyi birlikte düşünün', 'description' => 'Host, port ve kullanıcı bilgisi kadar from adı ve from e-posta da önemlidir.'],
                ['title' => 'Test e-postasını gerçek runtime ayarıyla doğrulayın', 'description' => 'Panel test aksiyonu, kayıtlı değerleri çalışma zamanına uygular.'],
            ],
            'coachmarks' => [
                ['anchor' => 'settings.form', 'title' => 'SMTP alanları', 'body' => 'Bağlantı ve gönderici bilgileri tek form altında toplanır.'],
                ['selector' => '.fi-page-actions', 'title' => 'Test gönderimi', 'body' => 'Üst aksiyonlardan test e-postası yollayarak konfigürasyonu doğrulayın.'],
                ['anchor' => 'settings.save', 'title' => 'Kaydet', 'body' => 'Önce kaydedin, sonra test gönderimini kullanın.'],
            ],
            'related_guides' => ['integration-settings'],
        ];
    }

    private function userManagementGuide(): array
    {
        return [
            'guide_key' => 'user-management',
            'title' => 'Kullanıcı ve Yetki Yönetimi',
            'summary' => 'Admin kullanıcılarını, rollerini ve aktiflik durumunu güvenli biçimde yönetir.',
            'why_it_matters' => 'Yanlış yetki ataması kritik panel aksiyonlarının yanlış kişilere açılmasına yol açabilir.',
            'impact' => 'Bu ekran işlem akışını etkiler.',
            'minimum_tier' => 'super_admin',
            'route_names' => ['filament.admin.resources.users.*'],
            'path_patterns' => ['admin/users*'],
            'quick_actions' => [
                ['label' => 'Kullanıcı listesi', 'description' => 'Panel kullanıcılarını ve rollerini yönetin.', 'url' => UserResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Önce aktiflik durumunu kontrol edin', 'description' => 'Pasif kullanıcı panel erişimi alamaz.'],
                ['title' => 'Rol yükseltmelerini dikkatle yapın', 'description' => 'Super admin ve editor yetkileri public’i etkileyen ekranlara erişim açar.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Kullanıcı listesi', 'body' => 'Rol, durum ve temel kullanıcı verileri bu listede görünür.'],
                ['selector' => '.fi-fo', 'title' => 'Kullanıcı formu', 'body' => 'Yeni kullanıcı veya rol güncelleme işlemi burada yapılır.'],
            ],
            'related_guides' => ['dashboard-overview'],
        ];
    }

    private function headerThemeGuide(): array
    {
        return [
            'guide_key' => 'header-themes',
            'title' => 'Header Temaları',
            'summary' => 'Takvim, manuel açma ve kapatma modlarıyla dönemsel header görünümünü yönetir.',
            'why_it_matters' => 'Bu ekran public başlık alanında dönemsel deneyimi ve marka tonunu doğrudan etkiler.',
            'impact' => 'Bu değişiklik sitede görünür.',
            'minimum_tier' => 'editor',
            'route_names' => ['filament.admin.resources.header-themes.*'],
            'path_patterns' => ['admin/header-themes*'],
            'quick_actions' => [
                ['label' => 'Header tema listesi', 'description' => 'Aktif ve planlı temaları yönetin.', 'url' => HeaderThemeResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Önce mod mantığını seçin', 'description' => 'Automatic, manual_on ve disabled modları tema davranışının temelini belirler.'],
                ['title' => 'Tarih ve öncelik çakışmalarını dikkatle kurun', 'description' => 'Aynı dönemde birden fazla tema varsa öncelik alanı devreye girer.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Tema listesi', 'body' => 'Planlı, aktif ve kapalı temalar bu listede görünür.'],
                ['selector' => '.fi-fo', 'title' => 'Tema formu', 'body' => 'Mesaj, mod, tarih ve görsel yoğunluk ayarları burada tanımlanır.'],
            ],
            'related_guides' => ['general-settings', 'layout-studio'],
        ];
    }

    private function ihaSyncLogsGuide(): array
    {
        return [
            'guide_key' => 'iha-sync-logs',
            'title' => 'İHA Senkron Kayıtları',
            'summary' => 'İHA senkronunun geçmiş kayıtlarını, sayaçlarını ve hata özetlerini listeler.',
            'why_it_matters' => 'Sorun çözme sırasında sağlık ekranından sonra bakılacak en önemli detay ekranıdır.',
            'impact' => 'Bu ekran yalnız operasyonel izleme içindir.',
            'minimum_tier' => 'super_admin',
            'route_names' => ['filament.admin.resources.iha-sync-logs.*'],
            'path_patterns' => ['admin/iha-sync-logs*'],
            'quick_actions' => [
                ['label' => 'Senkron logları', 'description' => 'Geçmiş İHA kayıtlarını açın.', 'url' => IhaSyncLogResource::getUrl(panel: 'admin')],
            ],
            'steps' => [
                ['title' => 'Durum alanını ve sayaçları birlikte okuyun', 'description' => 'Fetched, created, updated ve skipped değerleri bağlamı açıklar.'],
                ['title' => 'Hata metnine tek başına değil, zaman damgasıyla birlikte bakın', 'description' => 'Aynı tip hata tekrarlı mı yoksa tekil mi burada anlaşılır.'],
            ],
            'coachmarks' => [
                ['selector' => '.fi-ta', 'title' => 'Senkron kayıt tablosu', 'body' => 'Durum, sayaç ve hata özetleri bu listede bir araya gelir.'],
            ],
            'related_guides' => ['iha-health', 'integration-settings'],
        ];
    }
}
