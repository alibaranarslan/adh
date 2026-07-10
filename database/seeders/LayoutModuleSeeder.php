<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LayoutModuleSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['key' => 'breaking_bar', 'name' => 'Son Dakika Bandı', 'sort_order' => 1],
            ['key' => 'hero', 'name' => 'Güvenilir Yerel Manşet', 'sort_order' => 2],
            ['key' => 'local_news', 'name' => 'Adıyaman Gündemi', 'sort_order' => 3],
            ['key' => 'highlights', 'name' => 'Günün Önemli Gelişmeleri', 'sort_order' => 4],
            ['key' => 'most_read', 'name' => 'En Çok Okunan', 'sort_order' => 5],
            ['key' => 'region_news', 'name' => 'Bölge Haberleri', 'sort_order' => 6],
            ['key' => 'latest_news', 'name' => 'Son Haberler', 'sort_order' => 7],
            ['key' => 'news_river', 'name' => 'Haber Akışı', 'sort_order' => 8],
            ['key' => 'category_shortcuts', 'name' => 'Kategori Kısayolları', 'sort_order' => 9],
            ['key' => 'sidebar_widgets', 'name' => 'Bilgi Widgetları', 'sort_order' => 10],
            ['key' => 'ads', 'name' => 'Reklam Alanları', 'sort_order' => 11],
        ];

        foreach ($items as $item) {
            DB::table('layout_modules')->updateOrInsert(
                ['key' => $item['key']],
                [
                    'name' => $item['name'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                    'settings' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
