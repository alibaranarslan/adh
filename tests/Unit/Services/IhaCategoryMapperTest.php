<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Services\IhaCategoryMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IhaCategoryMapperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_uses_direct_iha_category_code_mapping_when_category_exists(): void
    {
        Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $target = Category::query()->create([
            'name' => ['tr' => 'Spor'],
            'slug' => 'spor',
            'iha_category_code' => 77,
            'is_active' => true,
        ]);

        $mapper = new IhaCategoryMapper();

        $mappedId = $mapper->mapFromArticle([
            'category_name' => 'Bilinmeyen',
            'category_code' => 77,
            'city_name' => '',
            'ust_kategori' => '',
        ]);

        $this->assertSame($target->id, $mappedId);
    }

    public function test_it_marks_adiyaman_district_news_as_local_and_assigns_city_slug(): void
    {
        $mapper = new IhaCategoryMapper();

        $article = [
            'city_name' => 'Kahta',
            'title' => 'Kahta ilçesinde önemli gelişme',
        ];

        $this->assertSame(IhaCategoryMapper::LOCALITY_LOCAL, $mapper->localityScore($article));
        $this->assertSame('adiyaman', $mapper->detectCitySlug($article));
    }

    public function test_it_marks_regional_news_as_region_and_detects_city_slug(): void
    {
        $mapper = new IhaCategoryMapper();

        $article = [
            'city_name' => 'Gaziantep',
            'title' => 'Gaziantep merkezli bölgesel gelişme',
        ];

        $this->assertSame(IhaCategoryMapper::LOCALITY_REGION, $mapper->localityScore($article));
        $this->assertSame('gaziantep', $mapper->detectCitySlug($article));
    }

    public function test_it_does_not_treat_cermik_or_puturge_as_adiyaman_local_news(): void
    {
        $mapper = new IhaCategoryMapper();

        $cermik = [
            'city_name' => '',
            'title' => 'Çermik Devlet Hastanesi Öğretmen Evi oluyor',
        ];
        $puturge = [
            'city_name' => '',
            'title' => 'Pütürge yolunda ulaşım çalışması başladı',
        ];

        $this->assertSame(IhaCategoryMapper::LOCALITY_REGION, $mapper->localityScore($cermik));
        $this->assertSame('diyarbakir', $mapper->detectCitySlug($cermik));
        $this->assertSame(IhaCategoryMapper::LOCALITY_REGION, $mapper->localityScore($puturge));
        $this->assertSame('malatya', $mapper->detectCitySlug($puturge));
    }
}
