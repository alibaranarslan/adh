<?php

namespace Tests\Unit\Support;

use App\Models\HeaderTheme;
use App\Support\HeaderThemeResolver;
use App\Support\HeaderThemeVisuals;
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
        HeaderThemeVisuals::resetAssetManifestCache();

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
            'illustration_asset' => 'bayram-crescent',
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

    public function test_ataturk_visibility_is_suppressed_for_holiday_slugs(): void
    {
        $this->createTheme([
            'slug' => 'ramazan-bayrami',
            'theme_type' => HeaderTheme::TYPE_RANGE,
            'starts_at' => '2026-03-20',
            'ends_at' => '2026-03-22',
            'show_ataturk' => true,
        ]);

        CarbonImmutable::setTestNow('2026-03-21 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertFalse((bool) data_get($resolved, 'show_ataturk'));
    }

    public function test_all_national_day_slugs_render_event_badges_without_masthead_overlay(): void
    {
        foreach (HeaderTheme::NATIONAL_SLUGS as $index => $slug) {
            $this->createTheme([
                'slug' => $slug,
                'theme_type' => HeaderTheme::TYPE_FIXED,
                'month' => 1,
                'day' => $index + 1,
                'priority' => 100 + $index,
                'show_ataturk' => true,
                'show_flag' => true,
            ]);

            CarbonImmutable::setTestNow(sprintf('2026-01-%02d 09:00:00', $index + 1));

            $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

            $this->assertSame($slug, data_get($resolved, 'id'));
            $this->assertNotEmpty(data_get($resolved, 'event_badge_markup'));
            $this->assertStringContainsString('adh-event-seal', (string) data_get($resolved, 'event_badge_markup'));
            $this->assertStringNotContainsString('adh-event-seal__ring', (string) data_get($resolved, 'event_badge_markup'));
            $this->assertNull(data_get($resolved, 'visual_markup'));
        }
    }

    public function test_tenth_of_november_message_is_not_celebratory(): void
    {
        $this->createTheme([
            'slug' => '10-kasim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 11,
            'day' => 10,
            'banner_message' => ['tr' => 'Saygı, özlem ve minnetle anıyoruz.'],
            'show_ataturk' => true,
        ]);

        CarbonImmutable::setTestNow('2026-11-10 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertStringNotContainsString('kutlu olsun', mb_strtolower((string) data_get($resolved, 'message')));
        $this->assertStringContainsString('adh-event-seal--remembrance', (string) data_get($resolved, 'event_badge_markup'));
        $this->assertStringContainsString('ataturk-pd-tr-cutout.png', (string) data_get($resolved, 'event_badge_markup'));
    }

    public function test_event_message_is_limited_for_strip_layout(): void
    {
        $this->createTheme([
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'banner_message' => ['tr' => str_repeat('Uzun mesaj ', 20)],
        ]);

        CarbonImmutable::setTestNow('2026-10-29 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertLessThanOrEqual(90, mb_strlen((string) data_get($resolved, 'message')));
    }

    public function test_asset_manifest_entries_point_to_existing_files_and_have_license_evidence(): void
    {
        foreach (HeaderThemeVisuals::assetManifest() as $asset) {
            $this->assertFileExists(base_path($asset['local_path']));
            $this->assertNotEmpty($asset['source_url']);
            $this->assertNotEmpty($asset['license']);
        }
    }

    public function test_unknown_custom_preset_does_not_create_fake_masthead_art(): void
    {
        $this->createTheme([
            'slug' => 'ozel-test',
            'illustration_asset' => 'missing-preset-key',
            'show_flag' => false,
            'show_ataturk' => false,
        ]);

        CarbonImmutable::setTestNow('2026-01-01 09:00:00');

        $resolved = app(HeaderThemeResolver::class)->resolve(Request::create('/', 'GET'));

        $this->assertNull(data_get($resolved, 'visual_markup'));
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
            'illustration_mode' => 'preset_asset',
            'illustration_asset' => 'ataturk-portrait-cutout',
            'show_flag' => false,
            'show_ataturk' => false,
            'decor_intensity' => 'medium',
        ], $overrides));
    }
}
