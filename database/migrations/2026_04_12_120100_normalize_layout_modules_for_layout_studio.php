<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $mapping = [
            'breaking_news' => ['key' => 'breaking_bar', 'name' => 'Son Dakika Bandi', 'sort_order' => 1],
            'hero_slider' => ['key' => 'hero', 'name' => 'Editoryal Manset', 'sort_order' => 2],
            'category_blocks' => ['key' => 'highlights', 'name' => 'Gunun Onemli Gelismeleri', 'sort_order' => 4],
            'most_read' => ['key' => 'most_read', 'name' => 'En Cok Okunan', 'sort_order' => 5],
            'local_widgets' => ['key' => 'sidebar_widgets', 'name' => 'Bilgi Widgetlari', 'sort_order' => 9],
            'latest_news' => ['key' => 'latest_news', 'name' => 'Son Haberler', 'sort_order' => 7],
            'advertisements' => ['key' => 'ads', 'name' => 'Reklam Alanlari', 'sort_order' => 10],
        ];

        foreach ($mapping as $oldKey => $newState) {
            $record = DB::table('layout_modules')->where('key', $oldKey)->first();

            if (! $record) {
                continue;
            }

            if ($oldKey !== $newState['key']) {
                DB::table('layout_modules')->where('id', $record->id)->update([
                    'key' => $newState['key'],
                    'name' => $newState['name'],
                    'sort_order' => $newState['sort_order'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('layout_modules')->where('id', $record->id)->update([
                    'name' => $newState['name'],
                    'sort_order' => $newState['sort_order'],
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('layout_modules')->where('key', 'video_news')->delete();

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
                    'settings' => json_encode([]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Intentionally left one-way because this normalizes legacy keys.
    }
};
