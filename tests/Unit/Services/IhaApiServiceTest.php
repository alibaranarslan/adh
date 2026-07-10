<?php

namespace Tests\Unit\Services;

use App\Services\IhaApiService;
use App\Services\IhaSyncException;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class IhaApiServiceTest extends TestCase
{
    #[Test]
    public function parse_xml_response_uses_description_as_content_when_no_explicit_body_field_exists(): void
    {
        $service = (new ReflectionClass(IhaApiService::class))->newInstanceWithoutConstructor();

        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
  <channel>
    <item>
      <HaberKodu>IHA-BODY-1</HaberKodu>
      <Kategori>GÜNDEM</Kategori>
      <Sehir>ADIYAMAN</Sehir>
      <title>Örnek Başlık</title>
      <description><![CDATA[İlk paragraf.<br/>İkinci paragraf.]]></description>
      <pubDate>18.04.2026 20:00:00</pubDate>
    </item>
  </channel>
</rss>
XML;

        $articles = $service->parseXmlResponse($xml);

        $this->assertCount(1, $articles);
        $this->assertSame('İlk paragraf.'."\n".'İkinci paragraf.', $articles[0]['content']);
    }
    #[Test]
    public function parse_xml_response_sanitizes_iha_credential_rejection_logs(): void
    {
        Log::spy();

        $service = (new ReflectionClass(IhaApiService::class))->newInstanceWithoutConstructor();
        $body = 'Musteri Bilgisi kullanici adi sifreden bulunamadi. [ UserCode=21718 , UserName=adiyamandijital , UserPassword=very-secret ]';

        try {
            $service->parseXmlResponse($body);
            $this->fail('Expected IHA credential rejection exception.');
        } catch (IhaSyncException $exception) {
            $this->assertStringContainsString('IHA kimlik bilgileri reddedildi', $exception->getMessage());
        }

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('IHA credential rejection', Mockery::on(function (array $context): bool {
                $snippet = $context['xml_snippet'] ?? '';

                return str_contains($snippet, 'UserCode=***')
                    && str_contains($snippet, 'UserName=***')
                    && str_contains($snippet, 'UserPassword=***')
                    && ! str_contains($snippet, '21718')
                    && ! str_contains($snippet, 'adiyamandijital')
                    && ! str_contains($snippet, 'very-secret');
            }));
    }
}
