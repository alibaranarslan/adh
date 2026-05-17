<?php

namespace Database\Seeders;

use App\Models\HeaderTheme;
use Illuminate\Database\Seeder;

class HeaderThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'slug' => '23-nisan',
                'name' => ['tr' => '23 Nisan', 'en' => '23 April', 'ku' => '23 Nîsan'],
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 4,
                'day' => 23,
                'priority' => 175,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı kutlu olsun.',
                    'en' => 'Happy 23 April National Sovereignty and Children’s Day.',
                    'ku' => '23 Nîsan pîroz be.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'turkish-flag-official',
                'show_flag' => true,
                'show_ataturk' => false,
                'decor_intensity' => 'soft',
            ],
            [
                'slug' => '19-mayis',
                'name' => ['tr' => '19 Mayıs', 'en' => '19 May', 'ku' => '19 Gulan'],
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 5,
                'day' => 19,
                'priority' => 180,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => '19 Mayıs Atatürk’ü Anma, Gençlik ve Spor Bayramı kutlu olsun.',
                    'en' => 'Happy 19 May Commemoration of Atatürk, Youth and Sports Day.',
                    'ku' => '19 Gulan pîroz be.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'ataturk-portrait-cutout',
                'show_flag' => true,
                'show_ataturk' => true,
                'decor_intensity' => 'medium',
            ],
            [
                'slug' => '30-agustos',
                'name' => ['tr' => '30 Ağustos', 'en' => '30 August', 'ku' => '30 Tebax'],
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 8,
                'day' => 30,
                'priority' => 185,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => '30 Ağustos Zafer Bayramı kutlu olsun.',
                    'en' => 'Happy 30 August Victory Day.',
                    'ku' => '30 Tebax pîroz be.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'turkish-flag-official',
                'show_flag' => true,
                'show_ataturk' => true,
                'decor_intensity' => 'medium',
            ],
            [
                'slug' => '29-ekim',
                'name' => ['tr' => '29 Ekim', 'en' => '29 October', 'ku' => '29 Cotmeh'],
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 10,
                'day' => 29,
                'priority' => 190,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => '29 Ekim Cumhuriyet Bayramı kutlu olsun.',
                    'en' => 'Happy 29 October Republic Day.',
                    'ku' => '29 Cotmeh pîroz be.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'turkish-flag-official',
                'show_flag' => true,
                'show_ataturk' => true,
                'decor_intensity' => 'strong',
            ],
            [
                'slug' => '10-kasim',
                'name' => ['tr' => '10 Kasım', 'en' => '10 November', 'ku' => '10 Mijdar'],
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 11,
                'day' => 10,
                'priority' => 200,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => 'Saygı, özlem ve minnetle anıyoruz.',
                    'en' => 'We remember with respect, longing and gratitude.',
                    'ku' => 'Bi rêz û spasdarî tê bîranîn.',
                ],
                'style_variant' => 'commemoration',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'ataturk-portrait-cutout',
                'show_flag' => false,
                'show_ataturk' => true,
                'decor_intensity' => 'soft',
            ],
            [
                'slug' => 'ramazan-bayrami',
                'name' => ['tr' => 'Ramazan Bayramı', 'en' => 'Eid al-Fitr', 'ku' => 'Cejna Ramazanê'],
                'theme_type' => HeaderTheme::TYPE_RANGE,
                'starts_at' => '2026-03-20',
                'ends_at' => '2026-03-22',
                'priority' => 170,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => 'Ramazan Bayramınız mübarek olsun.',
                    'en' => 'Blessed Eid al-Fitr.',
                    'ku' => 'Cejna Ramazanê pîroz be.',
                ],
                'style_variant' => 'bayram',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'bayram-crescent',
                'show_flag' => false,
                'show_ataturk' => false,
                'decor_intensity' => 'soft',
            ],
            [
                'slug' => 'kurban-bayrami',
                'name' => ['tr' => 'Kurban Bayramı', 'en' => 'Eid al-Adha', 'ku' => 'Cejna Qurbanê'],
                'theme_type' => HeaderTheme::TYPE_RANGE,
                'starts_at' => '2026-05-27',
                'ends_at' => '2026-05-30',
                'priority' => 168,
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'banner_message' => [
                    'tr' => 'Kurban Bayramınız mübarek olsun.',
                    'en' => 'Blessed Eid al-Adha.',
                    'ku' => 'Cejna Qurbanê pîroz be.',
                ],
                'style_variant' => 'bayram',
                'illustration_mode' => 'preset_asset',
                'illustration_asset' => 'bayram-crescent',
                'show_flag' => false,
                'show_ataturk' => false,
                'decor_intensity' => 'soft',
            ],
        ];

        foreach ($themes as $theme) {
            HeaderTheme::query()->updateOrCreate(
                ['site' => HeaderTheme::SITE, 'slug' => $theme['slug']],
                $theme,
            );
        }
    }
}
