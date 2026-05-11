<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IhaCategoryMapper
{
    public const LOCALITY_LOCAL   = 3;
    public const LOCALITY_REGION  = 2;
    public const LOCALITY_NATIONAL = 1;

    private const ADIYAMAN_NAMES = ['ADIYAMAN', 'ADIYAMAN MERKEZ', 'BESNİ', 'ÇELİKHAN', 'GERGER', 'GÖLBAŞI', 'KAHTA', 'SAMSAT', 'SİNCİK', 'TUT'];

    private const ADIYAMAN_TITLE_KEYWORDS = ['Adıyaman', 'Besni', 'Kahta', 'Gölbaşı', 'Çelikhan', 'Gerger', 'Samsat', 'Sincik', 'Nemrut'];

    private const REGION_TITLE_KEYWORDS = ['Gaziantep', 'Diyarbakır', 'Çermik', 'Şanlıurfa', 'Malatya', 'Pütürge', 'Kahramanmaraş', 'Kilis', 'Elazığ', 'Batman', 'Siirt', 'Şırnak', 'Mardin', 'Bingöl'];

    public const CITY_SLUGS = [
        'ADIYAMAN'       => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'ADIYAMAN MERKEZ'=> ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'BESNİ'          => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'ÇELİKHAN'       => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'GERGER'         => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'GÖLBAŞI'        => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'KAHTA'          => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'SAMSAT'         => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'SİNCİK'         => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'TUT'            => ['slug' => 'adiyaman',       'name' => 'Adıyaman'],
        'GAZİANTEP'      => ['slug' => 'gaziantep',      'name' => 'Gaziantep'],
        'DİYARBAKIR'     => ['slug' => 'diyarbakir',     'name' => 'Diyarbakır'],
        'ŞANLIURFA'      => ['slug' => 'sanliurfa',      'name' => 'Şanlıurfa'],
        'MALATYA'        => ['slug' => 'malatya',        'name' => 'Malatya'],
        'KAHRAMANMARAŞ'  => ['slug' => 'kahramanmaras',  'name' => 'Kahramanmaraş'],
        'KİLİS'          => ['slug' => 'kilis',          'name' => 'Kilis'],
        'ELAZIĞ'         => ['slug' => 'elazig',         'name' => 'Elazığ'],
        'BATMAN'         => ['slug' => 'batman',         'name' => 'Batman'],
        'SİİRT'          => ['slug' => 'siirt',          'name' => 'Siirt'],
        'ŞIRNAK'         => ['slug' => 'sirnak',         'name' => 'Şırnak'],
        'MARDİN'         => ['slug' => 'mardin',         'name' => 'Mardin'],
        'BİNGÖL'         => ['slug' => 'bingol',         'name' => 'Bingöl'],
    ];

    private const TITLE_TO_CITY_SLUG = [
        'Adıyaman' => 'adiyaman', 'Besni' => 'adiyaman', 'Kahta' => 'adiyaman',
        'Gölbaşı' => 'adiyaman', 'Çelikhan' => 'adiyaman', 'Gerger' => 'adiyaman',
        'Samsat' => 'adiyaman', 'Sincik' => 'adiyaman', 'Nemrut' => 'adiyaman',
        'Çermik' => 'diyarbakir', 'Pütürge' => 'malatya',
        'Gaziantep' => 'gaziantep', 'Şahinbey' => 'gaziantep', 'Şehitkamil' => 'gaziantep',
        'Nizip' => 'gaziantep', 'İslahiye' => 'gaziantep',
        'Diyarbakır' => 'diyarbakir', 'Ergani' => 'diyarbakir', 'Bismil' => 'diyarbakir',
        'Şanlıurfa' => 'sanliurfa', 'Viranşehir' => 'sanliurfa', 'Siverek' => 'sanliurfa',
        'Malatya' => 'malatya', 'Battalgazi' => 'malatya', 'Yeşilyurt' => 'malatya',
        'Kahramanmaraş' => 'kahramanmaras', 'Elbistan' => 'kahramanmaras',
        'Kilis' => 'kilis', 'Elazığ' => 'elazig', 'Batman' => 'batman',
        'Siirt' => 'siirt', 'Şırnak' => 'sirnak', 'Mardin' => 'mardin',
        'Bingöl' => 'bingol',
    ];

    private const REGION_CITY_NAMES = [
        'MALATYA', 'KAHRAMANMARAŞ', 'GAZİANTEP', 'ŞANLIURFA', 'DİYARBAKIR',
        'KİLİS', 'ELAZIĞ', 'BİNGÖL', 'BATMAN', 'SİİRT', 'ŞIRNAK', 'MARDİN',
    ];

    private const IHA_CATEGORY_MAP = [
        'GÜNDEM'           => 'gundem',
        'SİYASET'          => 'siyaset',
        'EKONOMİ'          => 'ekonomi',
        'SPOR'             => 'spor',
        'EĞİTİM'          => 'egitim',
        'SAĞLIK'           => 'saglik',
        'KÜLTÜR SANAT'     => 'kultur-sanat',
        'TEKNOLOJİ'        => 'teknoloji',
        'YAŞAM'            => 'yasam',
        'MAGAZİN'          => 'magazin',
        'ASAYİŞ'           => 'asayis',
        'HABERDE İNSAN'    => 'gundem',
        'POLİTİKA'         => 'siyaset',
        'DÜNYA'            => 'gundem',
        'ÇEVRE'            => 'yasam',
        'BİLİM'            => 'teknoloji',
        'OTOMOBİL'         => 'ekonomi',
    ];

    private ?int $localCategoryId   = null;
    private ?int $regionCategoryId  = null;
    private ?int $defaultCategoryId = null;

    public function mapFromArticle(array $articleData): int
    {
        $this->loadSystemCategories();

        $cityName     = mb_strtoupper(trim($articleData['city_name'] ?? ''));
        $categoryName = mb_strtoupper(trim($articleData['category_name'] ?? ''));
        $ustKategori  = mb_strtoupper(trim($articleData['ust_kategori'] ?? ''));

        // 1) IHA Kategori name → local slug mapping (highest priority)
        $mappedSlug = self::IHA_CATEGORY_MAP[$categoryName] ?? null;
        if ($mappedSlug) {
            $catId = $this->getCategoryBySlug($mappedSlug);
            if ($catId !== null) return $catId;
        }

        // 2) Adıyaman haberleri → Gündem (yerel ana kategori)
        if (in_array($cityName, self::ADIYAMAN_NAMES)) {
            return $this->localCategoryId ?? $this->defaultCategoryId ?? 1;
        }

        // 3) UstKategori: YEREL HABER → gündem
        if (str_contains($ustKategori, 'YEREL')) {
            return $this->defaultCategoryId ?? 1;
        }

        // 4) Fallback: admin-configured mapping
        $categoryCode = (int) ($articleData['category_code'] ?? 0);
        if ($categoryCode > 0) {
            $mappedId = $this->getFromSettingsMap($categoryCode);
            if ($mappedId !== null) return $mappedId;

            $directCategoryId = $this->getCategoryByIhaCode($categoryCode);
            if ($directCategoryId !== null) {
                return $directCategoryId;
            }
        }

        return $this->defaultCategoryId ?? 1;
    }

    /**
     * Yerellik puanı: 3=Adıyaman, 2=Bölge, 1=Ulusal/Diğer.
     * city_code alanına yazılarak anasayfa öncelemesinde kullanılır.
     */
    public function localityScore(array $articleData): int
    {
        $cityName = mb_strtoupper(trim($articleData['city_name'] ?? ''));
        $title    = $articleData['title'] ?? '';

        if (in_array($cityName, self::ADIYAMAN_NAMES)) {
            return self::LOCALITY_LOCAL;
        }

        foreach (self::ADIYAMAN_TITLE_KEYWORDS as $kw) {
            if (mb_stripos($title, $kw) !== false) {
                return self::LOCALITY_LOCAL;
            }
        }

        if (in_array($cityName, self::REGION_CITY_NAMES)) {
            return self::LOCALITY_REGION;
        }

        foreach (self::REGION_TITLE_KEYWORDS as $kw) {
            if (mb_stripos($title, $kw) !== false) {
                return self::LOCALITY_REGION;
            }
        }

        return self::LOCALITY_NATIONAL;
    }

    /**
     * IHA makale verisinden hangi ile ait olduğunu tespit eder.
     */
    public function detectCitySlug(array $articleData): ?string
    {
        $cityName = mb_strtoupper(trim($articleData['city_name'] ?? ''));
        $title    = $articleData['title'] ?? '';

        if (!empty($cityName) && isset(self::CITY_SLUGS[$cityName])) {
            return self::CITY_SLUGS[$cityName]['slug'];
        }

        foreach (self::TITLE_TO_CITY_SLUG as $keyword => $slug) {
            if (mb_stripos($title, $keyword) !== false) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Haberi olan il slug'larını ve adlarını döndürür.
     */
    public static function getActiveCities(): array
    {
        $seen = [];
        $cities = [];
        foreach (self::CITY_SLUGS as $data) {
            if (!in_array($data['slug'], $seen)) {
                $seen[] = $data['slug'];
                $cities[$data['slug']] = $data['name'];
            }
        }
        return $cities;
    }

    public function mapCategory(int $ihaCategoryCode, ?int $cityCode = null): int
    {
        $this->loadSystemCategories();
        return $this->defaultCategoryId ?? 1;
    }

    private function getCategoryBySlug(string $slug): ?int
    {
        return Cache::remember("iha.category_slug.{$slug}", 3600, function () use ($slug) {
            return Category::where('slug', $slug)->value('id');
        });
    }

    private function loadSystemCategories(): void
    {
        if ($this->localCategoryId !== null) {
            return;
        }

        $this->localCategoryId = Cache::remember('iha.category.local', 3600, function () {
            return Category::where('slug', 'yerel')->orWhere('slug', 'adiyaman')->value('id');
        });

        $this->regionCategoryId = Cache::remember('iha.category.region', 3600, function () {
            return Category::where('slug', 'bolge')->orWhere('slug', 'bölge')->value('id');
        });

        $this->defaultCategoryId = Cache::remember('iha.category.default', 3600, function () {
            return Category::where('slug', 'gundem')
                ->orWhere('slug', 'genel')
                ->value('id') ?? Category::first()?->id;
        });
    }

    private function getFromSettingsMap(int $ihaCategoryCode): ?int
    {
        $map = Cache::remember('iha.category_map', 1800, function () {
            $json = Setting::get('integration', 'iha_category_map');
            if (empty($json)) return [];
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : [];
        });

        return isset($map[$ihaCategoryCode]) ? (int) $map[$ihaCategoryCode] : null;
    }

    private function getCategoryByIhaCode(int $code): ?int
    {
        return Cache::remember("iha.category_by_code.{$code}", 3600, function () use ($code) {
            return Category::where('iha_category_code', $code)->value('id');
        });
    }

    public function clearCache(): void
    {
        Cache::forget('iha.category.local');
        Cache::forget('iha.category.region');
        Cache::forget('iha.category.default');
        Cache::forget('iha.category_map');
    }
}
