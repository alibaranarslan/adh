<?php

namespace Tests\Unit\Services;

use App\Services\EditorialScoreService;
use App\Services\IhaCategoryMapper;
use Tests\TestCase;

class EditorialScoreServiceTest extends TestCase
{
    public function test_regional_critical_visual_story_can_outrank_daily_local_story(): void
    {
        $localDaily = EditorialScoreService::computeFromRaw([
            'title' => 'Adiyaman belediyesinden yeni proje aciklamasi',
            'summary' => 'Kent merkezindeki rutin calisma kamuoyuna duyuruldu.',
            'category_name' => 'gundem',
            'image_url' => '/images/local.jpg',
            'published_at' => now()->subMinutes(20)->toDateTimeString(),
        ], IhaCategoryMapper::LOCALITY_LOCAL);

        $regionalCritical = EditorialScoreService::computeFromRaw([
            'title' => 'Malatya yolunda kaza: 3 yarali',
            'summary' => 'Bolge ulasimini etkileyen trafik kazasinda yaralilar hastaneye kaldirildi.',
            'category_name' => 'asayis',
            'image_url' => '/images/region.jpg',
            'published_at' => now()->subMinutes(45)->toDateTimeString(),
        ], IhaCategoryMapper::LOCALITY_REGION);

        $this->assertGreaterThan($localDaily, $regionalCritical);
    }

    public function test_locality_keeps_adiyaman_story_ahead_when_news_value_is_otherwise_similar(): void
    {
        $adiyaman = EditorialScoreService::computeFromRaw([
            'title' => 'Kahta ilcesinde trafik duzenlemesi',
            'summary' => 'Ilce merkezinde ulasimi ilgilendiren karar aciklandi.',
            'category_name' => 'gundem',
            'image_url' => '/images/adiyaman.jpg',
            'published_at' => now()->subMinutes(30)->toDateTimeString(),
        ], IhaCategoryMapper::LOCALITY_LOCAL);

        $national = EditorialScoreService::computeFromRaw([
            'title' => 'Ulusal gundemde trafik duzenlemesi',
            'summary' => 'Genel nitelikteki karar kamuoyuna duyuruldu.',
            'category_name' => 'gundem',
            'image_url' => '/images/national.jpg',
            'published_at' => now()->subMinutes(30)->toDateTimeString(),
        ], IhaCategoryMapper::LOCALITY_NATIONAL);

        $this->assertGreaterThan($national, $adiyaman);
    }
}
