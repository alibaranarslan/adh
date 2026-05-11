<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'tr' => 'Gündem',
                'en' => 'Agenda',
                'ku' => 'Rojeva',
            ],
            [
                'tr' => 'Siyaset',
                'en' => 'Politics',
                'ku' => 'Siyaset',
            ],
            [
                'tr' => 'Ekonomi',
                'en' => 'Economy',
                'ku' => 'Aborî',
            ],
            [
                'tr' => 'Spor',
                'en' => 'Sports',
                'ku' => 'Werzîş',
            ],
            [
                'tr' => 'Eğitim',
                'en' => 'Education',
                'ku' => 'Perwerde',
            ],
            [
                'tr' => 'Sağlık',
                'en' => 'Health',
                'ku' => 'Tenduristî',
            ],
            [
                'tr' => 'Kültür-Sanat',
                'en' => 'Culture & Art',
                'ku' => 'Çand û Huner',
            ],
            [
                'tr' => 'Teknoloji',
                'en' => 'Technology',
                'ku' => 'Teknolojî',
            ],
            [
                'tr' => 'Yaşam',
                'en' => 'Lifestyle',
                'ku' => 'Jiyan',
            ],
            [
                'tr' => 'Magazin',
                'en' => 'Magazine',
                'ku' => 'Magazîn',
            ],
            [
                'tr' => 'Asayiş',
                'en' => 'Public Safety',
                'ku' => 'Asayîş',
            ],
        ];

        foreach ($categories as $names) {
            DB::table('categories')->updateOrInsert(
                ['slug' => Str::slug($names['tr'])],
                [
                    'name'              => json_encode($names, JSON_UNESCAPED_UNICODE),
                    'description'       => null,
                    'color'             => null,
                    'icon'              => null,
                    'iha_category_code' => null,
                    'parent_id'         => null,
                    'sort_order'        => 0,
                    'is_active'         => true,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]
            );
        }
    }
}
