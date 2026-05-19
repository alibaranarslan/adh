<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class AiVisibilityContentSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'yayin-ilkeleri'],
            [
                'title' => [
                    'tr' => 'Yayın İlkeleri ve Haber Kaynaklarımız',
                    'en' => 'Editorial Principles and News Sources',
                    'ku' => 'Prensipên Weşanê û Çavkaniyên Nûçeyan',
                ],
                'content' => [
                    'tr' => '<p>Adıyaman Dijital Haber, Adıyaman ve çevresindeki güncel gelişmeleri kamu yararı, doğruluk ve erişilebilirlik ilkeleriyle takip eder.</p><p>İHA kaynaklı haberler ile editoryal içerikler haber detaylarında kaynak, tarih, kategori ve içerik bütünlüğü korunarak yayımlanır.</p><p>Düzeltme, tekzip, haber ihbarı ve reklam iş birlikleri için iletişim sayfasındaki kanallar kullanılabilir.</p>',
                    'en' => '<p>Adıyaman Dijital Haber follows current developments in Adıyaman and the surrounding region with a focus on public interest, accuracy and accessibility.</p><p>IHA-sourced stories and editorial content are published with visible source, date, category and article body context.</p><p>Correction, right-of-reply, news tip and advertising requests can be sent through the contact page.</p>',
                    'ku' => '<p>Adıyaman Dijital Haber geşedanên li Adıyaman û derdorê bi balê li berjewendiya giştî, rastî û gihîştinê dişopîne.</p><p>Nûçeyên ji IHA û naveroka edîtoryal bi çavkanî, dîrok, kategorî û naveroka nûçeyê bi awayekî xuya tên weşandin.</p><p>Ji bo sererastkirin, bersiv, îhbarên nûçeyan û hevkariyên reklamê dikarin rêyên di rûpela têkiliyê de bên bikaranîn.</p>',
                ],
                'meta_title' => [
                    'tr' => 'Yayın İlkeleri ve Haber Kaynaklarımız | Adıyaman Dijital Haber',
                    'en' => 'Editorial Principles and News Sources | Adıyaman Dijital Haber',
                    'ku' => 'Prensipên Weşanê û Çavkaniyên Nûçeyan | Adıyaman Dijital Haber',
                ],
                'meta_description' => [
                    'tr' => 'Adıyaman Dijital Haber yayın ilkeleri, haber kaynakları, İHA entegrasyonu, düzeltme ve iletişim yaklaşımı.',
                    'en' => 'Editorial principles, news sources, IHA integration, correction and contact approach of Adıyaman Dijital Haber.',
                    'ku' => 'Prensipên weşanê, çavkaniyên nûçeyan, entegrasyona IHA, sererastkirin û nêzîkatiya têkiliyê ya Adıyaman Dijital Haber.',
                ],
                'is_published' => true,
                'sort_order' => 15,
            ],
        );
    }
}
