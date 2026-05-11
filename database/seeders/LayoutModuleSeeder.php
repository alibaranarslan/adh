<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LayoutModuleSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['key' => 'breaking_bar', 'name' => 'Son Dakika Bandi', 'sort_order' => 1],
            ['key' => 'hero', 'name' => 'Editoryal Manset', 'sort_order' => 2],
            ['key' => 'local_news', 'name' => 'Adiyaman Gundemi', 'sort_order' => 3],
            ['key' => 'highlights', 'name' => 'Gunun Onemli Gelismeleri', 'sort_order' => 4],
            ['key' => 'most_read', 'name' => 'En Cok Okunan', 'sort_order' => 5],
            ['key' => 'region_news', 'name' => 'Bolge Haberleri', 'sort_order' => 6],
            ['key' => 'latest_news', 'name' => 'Son Haberler', 'sort_order' => 7],
            ['key' => 'category_shortcuts', 'name' => 'Kategori Kisayollari', 'sort_order' => 8],
            ['key' => 'sidebar_widgets', 'name' => 'Bilgi Widgetlari', 'sort_order' => 9],
            ['key' => 'ads', 'name' => 'Reklam Alanlari', 'sort_order' => 10],
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
