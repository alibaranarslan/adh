<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'breaking_bar' => 'Son Dakika Bandı',
            'hero' => 'Güvenilir Yerel Manşet',
            'local_news' => 'Adıyaman Gündemi',
            'highlights' => 'Günün Önemli Gelişmeleri',
            'most_read' => 'En Çok Okunan',
            'region_news' => 'Bölge Haberleri',
            'latest_news' => 'Son Haberler',
            'category_shortcuts' => 'Kategori Kısayolları',
            'sidebar_widgets' => 'Bilgi Widgetları',
            'ads' => 'Reklam Alanları',
        ];

        foreach ($names as $key => $name) {
            DB::table('layout_modules')->where('key', $key)->update([
                'name' => $name,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // One-way polish migration; old mojibake/ascii names should not be restored.
    }
};
