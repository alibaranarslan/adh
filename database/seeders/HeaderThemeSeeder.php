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
                    'tr' => '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı için daha aydınlık ama ölçülü bir yayın tonu aktif.',
                    'en' => 'A brighter yet restrained editorial tone is active for 23 April National Sovereignty and Children’s Day.',
                    'ku' => 'Ji bo 23 Nîsanê tona weşanê ya ronak lê ölçülü çalak e.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'star-crescent',
                'show_flag' => true,
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
                    'tr' => '19 Mayıs Atatürk’ü Anma, Gençlik ve Spor Bayramı için dinamik ama saygın bir masthead aktif.',
                    'en' => 'A dynamic yet respectful masthead is active for 19 May Commemoration of Atatürk, Youth and Sports Day.',
                    'ku' => 'Ji bo 19 Gulanê sernavê dinamik lê bi rêz çalak e.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'star-crescent',
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
                    'tr' => '30 Ağustos Zafer Bayramı için güçlü ama gazetecilik dilini bozmayan bir tema yayında.',
                    'en' => 'A strong yet editorially restrained theme is live for Victory Day on 30 August.',
                    'ku' => 'Ji bo 30 Tebaxê temaek hêzdar lê bêyî ku zimanê rojnamegeriyê bixeşkîne li ser e.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'star-crescent',
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
                    'tr' => '29 Ekim Cumhuriyet Bayramı için kırmızı-beyaz aksanlı resmi bir header düzeni aktif.',
                    'en' => 'A formal red-and-white accented header is active for Republic Day on 29 October.',
                    'ku' => 'Ji bo 29 Cotmehê sernavê fermî yê bi aksanên sor û spî çalak e.',
                ],
                'style_variant' => 'national',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'star-crescent',
                'show_flag' => true,
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
                    'tr' => '10 Kasım Atatürk’ü Anma Günü için sessiz, ölçülü ve saygı vurgulu bir tema yayında.',
                    'en' => 'A quiet, restrained and respectful theme is live for 10 November, the day of remembrance for Atatürk.',
                    'ku' => 'Ji bo 10 Mijdarê temaek bêdeng, ölçülü û bi hurmet li ser e.',
                ],
                'style_variant' => 'commemoration',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => '10-kasim',
                'show_flag' => true,
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
                    'tr' => 'Ramazan Bayramınız mübarek olsun. Yayında daha aydınlık ve toplumsal bir ton aktif.',
                    'en' => 'Blessed Eid al-Fitr. A brighter and more communal editorial tone is active.',
                    'ku' => 'Pîroz be Cejna Ramazanê. Tona weşanê ya ronaktir û civakî çalak e.',
                ],
                'style_variant' => 'bayram',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'hilal',
                'show_flag' => false,
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
                    'tr' => 'Kurban Bayramınız mübarek olsun. Dengeli, temiz ve toplumsal dayanışma vurgulu bir tema yayında.',
                    'en' => 'Blessed Eid al-Adha. A balanced, clean and community-minded theme is live.',
                    'ku' => 'Pîroz be Cejna Qurbanê. Temaek hevseng, paqij û bi nîşana hevrêziya civakî li ser e.',
                ],
                'style_variant' => 'bayram',
                'illustration_mode' => 'inline_svg',
                'illustration_asset' => 'hilal',
                'show_flag' => false,
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
