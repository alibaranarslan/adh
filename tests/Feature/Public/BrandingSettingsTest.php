<?php

namespace Tests\Feature\Public;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_settings_are_reflected_on_public_layout(): void
    {
        Setting::set('general', 'site_name', 'Adıyaman Test Haber');
        Setting::set('general', 'site_tagline', 'Şehrin canlı nabzı');
        Setting::set('general', 'logo_path', 'settings/adh-logo.png');
        Setting::set('general', 'dark_logo_path', 'settings/adh-logo-dark.png');
        Setting::set('general', 'favicon_path', 'settings/adh-favicon.png');
        Setting::set('social', 'links', json_encode([
            ['platform' => 'twitter', 'url' => 'https://x.com/adh-test'],
            ['platform' => 'linkedin', 'url' => 'https://www.linkedin.com/company/adh-test'],
        ]));

        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Adıyaman Test Haber')
            ->assertSee('Şehrin canlı nabzı')
            ->assertSee('/storage/settings/adh-logo-dark.png', false)
            ->assertSee('/storage/settings/adh-favicon.png', false)
            ->assertSee('https://www.linkedin.com/company/adh-test', false);
    }

    public function test_branding_fields_fall_back_to_default_public_assets(): void
    {
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://twitter.com/adiyamanhaber', false)
            ->assertSee('images/branding/favicon.svg', false);
    }

    public function test_localized_general_settings_are_resolved_for_selected_locale(): void
    {
        Setting::set('general', 'site_name', json_encode([
            'tr' => 'Adıyaman Test Haber',
            'en' => 'Adiyaman Test News',
            'ku' => 'Nuceyên Testê yên Adiyamanê',
        ], JSON_UNESCAPED_UNICODE));
        Setting::set('general', 'site_tagline', json_encode([
            'tr' => 'Şehrin canlı nabzı',
            'en' => 'The city\'s live pulse',
            'ku' => 'Nebza zindî ya bajarê',
        ], JSON_UNESCAPED_UNICODE));
        Setting::set('general', 'address', json_encode([
            'tr' => 'Adıyaman Merkez / Türkiye',
            'en' => 'Adiyaman Center / Turkey',
            'ku' => 'Navenda Adiyaman / Tirkiye',
        ], JSON_UNESCAPED_UNICODE));

        Cache::flush();

        $this->get('/en/')
            ->assertOk()
            ->assertSee('Adiyaman Test News')
            ->assertSee('The city&#039;s live pulse', false)
            ->assertSee('Adiyaman Center / Turkey');
    }
}
