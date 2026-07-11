<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        $metadata = [
            'hakkimizda' => [
                'meta_title' => [
                    'tr' => 'Hakkımızda | Adıyaman Dijital Haber',
                    'en' => 'About Us | Adıyaman Digital News',
                    'ku' => 'Derbarê Me | Adıyaman Dijital Haber',
                ],
                'meta_description' => [
                    'tr' => 'Adıyaman Dijital Haber yayın vizyonu, yerel haber odağı ve kurumsal yaklaşımı hakkında bilgi.',
                    'en' => 'Information about Adıyaman Dijital Haber, its local news focus and editorial approach.',
                    'ku' => 'Agahî li ser Adıyaman Dijital Haber, bala wê ya nûçeyên herêmî û nêzîkatiya weşanê.',
                ],
            ],
            'iletisim' => [
                'meta_title' => [
                    'tr' => 'İletişim | Adıyaman Dijital Haber',
                    'en' => 'Contact | Adıyaman Digital News',
                    'ku' => 'Pêwendî | Adıyaman Dijital Haber',
                ],
                'meta_description' => [
                    'tr' => 'Adıyaman Dijital Haber iletişim bilgileri, haber ihbarı, reklam ve kurumsal başvuru kanalları.',
                    'en' => 'Contact details for Adıyaman Dijital Haber, news tips, advertising and corporate requests.',
                    'ku' => 'Agahiyên pêwendiyê yên Adıyaman Dijital Haber ji bo nûçe, reklam û daxwazên fermî.',
                ],
            ],
            'kvkk-aydinlatma' => [
                'meta_title' => [
                    'tr' => 'KVKK Aydınlatma Metni | Adıyaman Dijital Haber',
                    'en' => 'KVKK Disclosure Text | Adıyaman Digital News',
                    'ku' => 'Metna Ronîkirina KVKK | Adıyaman Dijital Haber',
                ],
                'meta_description' => [
                    'tr' => 'Adıyaman Dijital Haber kişisel verilerin korunması ve KVKK aydınlatma metni.',
                    'en' => 'Adıyaman Dijital Haber personal data protection and KVKK disclosure text.',
                    'ku' => 'Parastina daneyên kesane û metna ronîkirina KVKK ya Adıyaman Dijital Haber.',
                ],
            ],
        ];

        foreach ($metadata as $slug => $values) {
            DB::table('pages')
                ->where('slug', $slug)
                ->where(function ($query): void {
                    $query->whereNull('meta_title')
                        ->orWhereNull('meta_description');
                })
                ->update([
                    'meta_title' => DB::raw('COALESCE(meta_title, ' . DB::getPdo()->quote(json_encode($values['meta_title'], JSON_UNESCAPED_UNICODE)) . ')'),
                    'meta_description' => DB::raw('COALESCE(meta_description, ' . DB::getPdo()->quote(json_encode($values['meta_description'], JSON_UNESCAPED_UNICODE)) . ')'),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Metadata backfill is intentionally non-destructive.
    }
};
