<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Adıyaman Dijital Haber'],
            ['group' => 'general', 'key' => 'contact_email', 'value' => ''],
            ['group' => 'seo', 'key' => 'default_meta_title', 'value' => 'Adıyaman Dijital Haber - Son Dakika Adıyaman Haberleri'],
            ['group' => 'seo', 'key' => 'robots_txt', 'value' => "User-agent: *\nAllow: /\nSitemap: https://adiyamandijitalhaber.com.tr/sitemap.xml"],
            ['group' => 'appearance', 'key' => 'primary_color', 'value' => '#1a365d'],
            ['group' => 'general', 'key' => 'contact_phone', 'value' => ''],
            ['group' => 'general', 'key' => 'address', 'value' => ''],
            ['group' => 'social', 'key' => 'links', 'value' => json_encode(['twitter' => '', 'facebook' => '', 'instagram' => '', 'youtube' => ''])],
            ['group' => 'integration', 'key' => 'iha_sync_interval', 'value' => '15'],
            ['group' => 'integration', 'key' => 'google_analytics_id', 'value' => 'G-WRTKSNRSBR'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
