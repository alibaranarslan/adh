<?php

namespace Tests\Feature\Public;

use App\Models\HeaderTheme;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HeaderThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_republic_day_theme_renders_editorial_strip_without_masthead_overlay(): void
    {
        HeaderTheme::query()->create([
            'slug' => '29-ekim',
            'name' => ['tr' => '29 Ekim'],
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'priority' => 190,
            'is_enabled' => true,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'banner_message' => ['tr' => '29 Ekim Cumhuriyet Bayramı kutlu olsun.'],
            'style_variant' => 'national',
            'illustration_mode' => 'preset_asset',
            'illustration_asset' => 'ataturk-portrait-cutout',
            'show_flag' => true,
            'show_ataturk' => true,
            'decor_intensity' => 'strong',
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('adh-theme-29-ekim', false)
            ->assertSee('adh-event-strip__badge', false)
            ->assertSee('adh-event-seal--republic', false)
            ->assertSee('29 Ekim')
            ->assertSee('29 Ekim Cumhuriyet Bayramı kutlu olsun.')
            ->assertSee('turkish-flag-official.svg', false)
            ->assertDontSee('adh-theme-visual', false)
            ->assertDontSee('adh-theme-ataturk', false);
    }

    public function test_signed_preview_renders_commemoration_strip(): void
    {
        $theme = HeaderTheme::query()->create([
            'slug' => '10-kasim',
            'name' => ['tr' => '10 Kasım'],
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 11,
            'day' => 10,
            'priority' => 200,
            'is_enabled' => true,
            'mode' => HeaderTheme::MODE_DISABLED,
            'banner_message' => ['tr' => 'Saygı, özlem ve minnetle anıyoruz.'],
            'style_variant' => 'commemoration',
            'illustration_mode' => 'preset_asset',
            'illustration_asset' => 'ataturk-portrait-cutout',
            'show_flag' => true,
            'show_ataturk' => true,
            'decor_intensity' => 'soft',
        ]);

        $url = URL::temporarySignedRoute('header-theme.preview.home', now()->addMinutes(30), [
            'headerTheme' => $theme->id,
            'locale' => 'tr',
            'preview_date' => '2026-11-10',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('adh-tone-commemoration', false)
            ->assertSee('Saygı, özlem ve minnetle anıyoruz.')
            ->assertSee('Önizleme')
            ->assertSee('adh-event-seal--remembrance', false)
            ->assertSee('ataturk-pd-tr-cutout.png', false)
            ->assertDontSee('kutlu olsun');
    }

    public function test_bayram_theme_does_not_render_ataturk_or_flag_markup(): void
    {
        HeaderTheme::query()->create([
            'slug' => 'ramazan-bayrami',
            'name' => ['tr' => 'Ramazan Bayramı'],
            'theme_type' => HeaderTheme::TYPE_RANGE,
            'starts_at' => '2026-03-20',
            'ends_at' => '2026-03-22',
            'priority' => 170,
            'is_enabled' => true,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'banner_message' => ['tr' => 'Ramazan Bayramınız mübarek olsun.'],
            'style_variant' => 'bayram',
            'illustration_mode' => 'preset_asset',
            'illustration_asset' => 'bayram-crescent',
            'show_flag' => true,
            'show_ataturk' => true,
            'decor_intensity' => 'soft',
        ]);

        CarbonImmutable::setTestNow('2026-03-21 09:00:00');
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('adh-tone-bayram', false)
            ->assertSee('adh-event-seal--ramazan', false)
            ->assertSee('bayram-crescent.svg', false)
            ->assertDontSee('ataturk-pd-tr-cutout.png', false)
            ->assertDontSee('turkish-flag-official.svg', false);
    }

    public function test_english_locale_uses_translated_theme_message(): void
    {
        HeaderTheme::query()->create([
            'slug' => '29-ekim',
            'name' => ['tr' => '29 Ekim', 'en' => 'Republic Day'],
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'priority' => 190,
            'is_enabled' => true,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'banner_message' => ['tr' => 'Türkçe tema mesajı', 'en' => 'English republic message'],
            'style_variant' => 'national',
            'illustration_mode' => 'preset_asset',
            'illustration_asset' => 'ataturk-portrait-cutout',
            'show_flag' => true,
            'decor_intensity' => 'medium',
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');
        Cache::flush();

        $this->get('/en/')
            ->assertOk()
            ->assertSee('English republic message');
    }
}
