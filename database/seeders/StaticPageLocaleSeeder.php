<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class StaticPageLocaleSeeder extends Seeder
{
    /**
     * Missing static-page translations are filled without overwriting customer-provided content.
     */
    public function run(): void
    {
        $pages = [
            'hakkimizda' => [
                'title' => [
                    'en' => 'About Us',
                    'ku' => 'Derbarê Me',
                ],
                'content' => [
                    'en' => '<h2>Adıyaman Digital News</h2><p>Adıyaman Digital News is a local digital news platform focused on delivering developments from Adıyaman and its region quickly, accurately and responsibly.</p><p>With its editorial principles and agency integrations, ADH aims to present local news, public updates and social developments on a reliable digital surface.</p>',
                    'ku' => '<h2>Nûçeyên Dijîtal ên Adıyamanê</h2><p>Nûçeyên Dijîtal ên Adıyamanê platformeke nûçeyan a herêmî ye ku pêşketinên Adıyaman û derdora wê bi awayekî lez, rast û berpirsiyar digihîne xwendevanan.</p><p>ADH bi prensîbên edîtoryal û entegrasyonên ajansê dixwaze nûçeyên herêmî, agahiyên giştî û pêşketinên civakî li ser rûyeke dijîtal a pêbawer pêşkêş bike.</p>',
                ],
                'meta_title' => [
                    'en' => 'About Us | Adıyaman Digital News',
                    'ku' => 'Derbarê Me | Nûçeyên Dijîtal ên Adıyamanê',
                ],
                'meta_description' => [
                    'en' => 'About Adıyaman Digital News and its editorial mission.',
                    'ku' => 'Derbarê Nûçeyên Dijîtal ên Adıyamanê û armanca wê ya edîtoryal.',
                ],
            ],
            'gizlilik-politikasi' => [
                'title' => [
                    'en' => 'Privacy Policy',
                    'ku' => 'Polîtîkaya Nepenîtiyê',
                ],
                'content' => [
                    'en' => '<h2>Privacy Policy</h2><p>Adıyaman Digital News processes reader and visitor data for communication, service security, legal obligations and improving the publishing experience.</p><p>Personal data is handled with care and shared only where legally required or necessary for technical service delivery.</p>',
                    'ku' => '<h2>Polîtîkaya Nepenîtiyê</h2><p>Nûçeyên Dijîtal ên Adıyamanê daneyên xwendevan û mêvanan ji bo têkilî, ewlehiya xizmetê, erkên qanûnî û başkirina ezmûna weşanê bi kar tîne.</p><p>Daneyên kesane bi baldarî tên parastin û tenê dema ku qanûnî an ji bo xizmeta teknîkî pêwîst be tên parvekirin.</p>',
                ],
                'meta_title' => [
                    'en' => 'Privacy Policy | Adıyaman Digital News',
                    'ku' => 'Polîtîkaya Nepenîtiyê | Nûçeyên Dijîtal ên Adıyamanê',
                ],
                'meta_description' => [
                    'en' => 'Privacy and personal data policy for Adıyaman Digital News.',
                    'ku' => 'Polîtîkaya nepenîtî û daneyên kesane ya Nûçeyên Dijîtal ên Adıyamanê.',
                ],
            ],
            'kvkk-aydinlatma' => [
                'title' => [
                    'en' => 'Personal Data Notice',
                    'ku' => 'Agahdariya Parastina Daneyan',
                ],
                'content' => [
                    'en' => '<h2>Personal Data Notice</h2><p>This notice explains how Adıyaman Digital News processes personal data, which purposes are involved and how readers may exercise their legal rights.</p><p>Requests about personal data can be sent through the contact channels published on the site.</p>',
                    'ku' => '<h2>Agahdariya Parastina Daneyan</h2><p>Ev agahdarî rave dike ku Nûçeyên Dijîtal ên Adıyamanê daneyên kesane çawa dişopîne, ji bo kîjan armancan bi kar tîne û xwendevan çawa dikarin mafên xwe bi kar bînin.</p><p>Daxwazên derbarê daneyên kesane dikarin ji rêyên têkilî yên li malperê hatine weşandin bên şandin.</p>',
                ],
                'meta_title' => [
                    'en' => 'Personal Data Notice | Adıyaman Digital News',
                    'ku' => 'Agahdariya Parastina Daneyan | Nûçeyên Dijîtal ên Adıyamanê',
                ],
                'meta_description' => [
                    'en' => 'Personal data notice for Adıyaman Digital News readers.',
                    'ku' => 'Agahdariya daneyên kesane ji bo xwendevanên Nûçeyên Dijîtal ên Adıyamanê.',
                ],
            ],
            'cerez-politikasi' => [
                'title' => [
                    'en' => 'Cookie Policy',
                    'ku' => 'Polîtîkaya Çerezan',
                ],
                'content' => [
                    'en' => '<h2>Cookie Policy</h2><p>Adıyaman Digital News uses cookies to provide essential site functions, remember user preferences, measure performance and improve the reading experience.</p><p>Visitors can manage optional cookies through the consent panel or browser settings.</p>',
                    'ku' => '<h2>Polîtîkaya Çerezan</h2><p>Nûçeyên Dijîtal ên Adıyamanê çerezan ji bo fonksiyonên bingehîn ên malperê, bîranîna vebijarkan, pîvandina performansê û başkirina ezmûna xwendinê bi kar tîne.</p><p>Mêvan dikarin çerezên bijarte ji panela razîbûnê an mîhengên gerokê birêve bibin.</p>',
                ],
                'meta_title' => [
                    'en' => 'Cookie Policy | Adıyaman Digital News',
                    'ku' => 'Polîtîkaya Çerezan | Nûçeyên Dijîtal ên Adıyamanê',
                ],
                'meta_description' => [
                    'en' => 'Cookie policy and preference information for Adıyaman Digital News.',
                    'ku' => 'Polîtîkaya çerezan û agahiyên vebijarkan ji bo Nûçeyên Dijîtal ên Adıyamanê.',
                ],
            ],
        ];

        foreach ($pages as $slug => $translations) {
            $page = Page::query()->where('slug', $slug)->first();

            if (! $page) {
                continue;
            }

            foreach ($translations as $field => $locales) {
                foreach ($locales as $locale => $value) {
                    if (blank($page->getTranslation($field, $locale, false))) {
                        $page->setTranslation($field, $locale, $value);
                    }
                }
            }

            if ($page->isDirty()) {
                $page->save();
            }
        }
    }
}
