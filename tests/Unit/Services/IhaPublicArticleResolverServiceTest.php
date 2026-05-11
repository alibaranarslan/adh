<?php

namespace Tests\Unit\Services;

use App\Models\NewsArticle;
use App\Services\IhaPublicArticleResolverService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IhaPublicArticleResolverServiceTest extends TestCase
{
    public function test_it_resolves_public_iha_article_via_duckduckgo_and_json_ld(): void
    {
        Http::fake([
            'https://html.duckduckgo.com/html/*' => Http::response(
                '<a href="//duckduckgo.com/l/?uddg=https%3A%2F%2Fwww.iha.com.tr%2Fdiyarbakir-haberleri%2Fcermik-devlet-hastanesi-ogretmen-evi-oluyor-408829769">result</a>',
                200
            ),
            'https://www.iha.com.tr/diyarbakir-haberleri/cermik-devlet-hastanesi-ogretmen-evi-oluyor-408829769' => Http::response(
                <<<HTML
                <html>
                  <head></head>
                  <body>
                    <script type="application/ld+json">
                      {
                        "@context": "https://schema.org",
                        "@type": "NewsArticle",
                        "headline": "Çermik Devlet Hastanesi Öğretmen Evi oluyor",
                        "datePublished": "2026-04-16T09:29:29+03:00",
                        "description": "Diyarbakır’ın Çermik ilçesinde eski Devlet Hastanesi, Öğretmen Evine dönüştürülecek.",
                        "articleBody": "Diyarbakır’ın Çermik ilçesinde eski Devlet Hastanesi, Öğretmen Evine dönüştürülecek. İl Milli Eğitim Müdürü Salih Sadoğlu hastaneyi yerinde inceledi."
                      }
                    </script>
                  </body>
                </html>
                HTML,
                200
            ),
        ]);

        $article = new NewsArticle();
        $article->title = ['tr' => 'Çermik Devlet Hastanesi Öğretmen Evi oluyor'];
        $article->published_at = Carbon::parse('2026-04-16 09:29:29', 'Europe/Istanbul');

        $resolved = app(IhaPublicArticleResolverService::class)->resolve($article);

        $this->assertNotNull($resolved);
        $this->assertSame(
            'https://www.iha.com.tr/diyarbakir-haberleri/cermik-devlet-hastanesi-ogretmen-evi-oluyor-408829769',
            $resolved['url']
        );
        $this->assertStringContainsString('Öğretmen Evine dönüştürülecek', $resolved['content']);
    }
    public function test_it_extracts_article_body_from_realistic_json_ld_with_raw_line_breaks(): void
    {
        $html = <<<'HTML'
        <html>
          <body>
            <script type="application/ld+json">
              {
                "@context": "https://schema.org",
                "@type": "NewsArticle",
                "headline": "Tartıştığı kadını öldürdü, ardından bileklerini kesti",
                "datePublished": "2026-04-16T21:40:13+03:00",
                "description": "",
                "articleBody": "Gaziantep’te 4 çocuk annesi kadın, tartıştığı şahıs tarafından evinde bıçaklanarak öldürüldü.
        Çocuklardan biri \"annem evde yaralı\" diyerek yardım istedi.
        Olay, Nizip ilçesi Tahtani Mahallesi’nde akşam saatlerinde meydana geldi."
              }
            </script>
          </body>
        </html>
        HTML;

        $resolved = app(IhaPublicArticleResolverService::class)
            ->extractArticleDataFromHtml($html, 'https://www.iha.com.tr/gaziantep-haberleri/tartistigi-kadini-oldurdu-ardindan-bileklerini-kesti-409092304');

        $this->assertNotNull($resolved);
        $this->assertSame('Tartıştığı kadını öldürdü, ardından bileklerini kesti', $resolved['headline']);
        $this->assertStringContainsString('Gaziantep’te 4 çocuk annesi kadın', $resolved['content']);
        $this->assertStringContainsString('annem evde yaralı', $resolved['content']);
    }
}
