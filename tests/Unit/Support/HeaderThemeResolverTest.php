<?php

namespace Tests\Unit\Support;

use App\Models\HeaderTheme;
use App\Support\HeaderThemeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class HeaderThemeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_fixed_theme_matches_current_date(): void
    {
        $theme = $this->createTheme([
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'show_flag' => true,
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertSame($theme->slug, data_get($resolved, 'id'));
    }

    public function test_range_theme_matches_current_date(): void
    {
        $theme = $this->createTheme([
            'slug' => 'ramazan-bayrami',
            'theme_type' => HeaderTheme::TYPE_RANGE,
            'starts_at' => '2026-03-20',
            'ends_at' => '2026-03-22',
            'illustration_asset' => 'hilal',
        ]);

        CarbonImmutable::setTestNow('2026-03-21 12:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertSame($theme->slug, data_get($resolved, 'id'));
    }

    public function test_manual_mode_overrides_automatic_theme(): void
    {
        $this->createTheme([
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'priority' => 120,
        ]);

        $manual = $this->createTheme([
            'slug' => '10-kasim',
            'mode' => HeaderTheme::MODE_MANUAL_ON,
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 11,
            'day' => 10,
            'show_flag' => true,
            'show_ataturk' => true,
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertSame($manual->slug, data_get($resolved, 'id'));
    }

    public function test_disabled_theme_is_suppressed(): void
    {
        $this->createTheme([
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'mode' => HeaderTheme::MODE_DISABLED,
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertNull($resolved);
    }

    public function test_ataturk_visibility_is_limited_to_allowed_slugs(): void
    {
        $this->createTheme([
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'show_ataturk' => true,
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertFalse((bool) data_get($resolved, 'show_ataturk'));
    }

    public function test_allowed_slug_can_render_ataturk_markup(): void
    {
        $this->createTheme([
            'slug' => '10-kasim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 11,
            'day' => 10,
            'show_ataturk' => true,
            'show_flag' => true,
        ]);

        CarbonImmutable::setTestNow('2026-11-10 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertTrue((bool) data_get($resolved, 'show_ataturk'));
        $this->assertStringContainsString('adh-theme-ataturk', (string) data_get($resolved, 'visual_markup'));
    }

    private function createTheme(array $overrides = []): HeaderTheme
    {
        return HeaderTheme::query()->create(array_merge([
            'slug' => 'varsayilan-tema',
            'name' => ['tr' => 'Varsayılan Tema'],
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 1,
            'day' => 1,
            'priority' => 100,
            'is_enabled' => true,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'banner_message' => ['tr' => 'Varsayılan mesaj'],
            'style_variant' => 'national',
            'illustration_mode' => 'inline_svg',
            'illustration_asset' => 'star-crescent',
            'show_flag' => false,
            'show_ataturk' => false,
            'decor_intensity' => 'medium',
        ], $overrides));
    }
}
