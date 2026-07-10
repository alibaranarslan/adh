<?php

namespace App\Services;

use App\Models\LayoutModule;
use App\Models\LayoutRevision;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class LayoutConfigService
{
    public const AREA_HOME = 'home';

    public function getModuleDefinitions(): array
    {
        return [
            'breaking_bar' => [
                'name' => 'Son Dakika Bandı',
                'description' => 'Üstteki acil gündem ve hızlı haber satırı.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'ticker',
                    'content_limit' => 10,
                    'padding_scale' => 'compact',
                ]),
            ],
            'hero' => [
                'name' => 'Güvenilir Yerel Manşet',
                'description' => 'Ana haber, kaynak güveni ve yan seçkiler.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'editorial',
                    'content_limit' => 5,
                    'image_ratio' => '16:10',
                    'card_density' => 'comfortable',
                    'container_width' => 'full',
                    'columns_desktop' => 12,
                ]),
            ],
            'local_news' => [
                'name' => 'Adıyaman Gündemi',
                'description' => 'Adıyaman merkezli yerel haber akışı.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'feature-list',
                    'content_limit' => 6,
                    'columns_desktop' => 2,
                ]),
            ],
            'highlights' => [
                'name' => 'Günün Önemli Gelişmeleri',
                'description' => 'Öne çıkan editoryal seçki.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'cards',
                    'content_limit' => 4,
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 2,
                    'image_ratio' => '16:10',
                ]),
            ],
            'asayis_news' => [
                'name' => 'Asayis',
                'description' => 'Kaza, yangin, operasyon ve guvenlik odakli haberler.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'cards',
                    'content_limit' => 6,
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 3,
                    'image_ratio' => '16:10',
                ]),
            ],
            'most_read' => [
                'name' => 'En Çok Okunan',
                'description' => 'Okunma skoruna göre liste.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'ranked-list',
                    'content_limit' => 5,
                    'padding_scale' => 'compact',
                ]),
            ],
            'region_news' => [
                'name' => 'Bölge Haberleri',
                'description' => 'Bölgesel haberlerin derli toplu akışı.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'cards',
                    'content_limit' => 6,
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 3,
                ]),
            ],
            'politics_economy' => [
                'name' => 'Siyaset ve Ekonomi',
                'description' => 'Karar, kurum, ekonomi ve kamu gundemi haberleri.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'cards',
                    'content_limit' => 6,
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 3,
                    'image_ratio' => '16:10',
                ]),
            ],
            'life_digest' => [
                'name' => 'Yasam',
                'description' => 'Egitim, saglik, kultur, teknoloji ve yasam haberleri.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'cards',
                    'content_limit' => 6,
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 3,
                    'image_ratio' => '16:10',
                ]),
            ],
            'latest_news' => [
                'name' => 'Son Haberler',
                'description' => 'Güncel haber akışı.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'lead-with-list',
                    'content_limit' => 12,
                ]),
            ],
            'news_river' => [
                'name' => 'Haber Akışı',
                'description' => 'Anasayfayı canlı tutan kompakt ek haber akışı.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'lead-with-list',
                    'content_limit' => 16,
                    'padding_scale' => 'compact',
                    'columns_mobile' => 1,
                    'columns_tablet' => 2,
                    'columns_desktop' => 4,
                ]),
            ],
            'category_shortcuts' => [
                'name' => 'Kategori Kısayolları',
                'description' => 'Kategorilere hızlı erişim bloğu.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'shortcut-grid',
                    'content_limit' => 9,
                    'columns_mobile' => 2,
                    'columns_tablet' => 3,
                    'columns_desktop' => 3,
                ]),
            ],
            'sidebar_widgets' => [
                'name' => 'Bilgi Widgetları',
                'description' => 'Hava durumu, eczane, namaz, yerel bilgi.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'stack',
                    'padding_scale' => 'compact',
                ]),
            ],
            'ads' => [
                'name' => 'Reklam Alanları',
                'description' => 'Sidebar ve vitrin reklam slotları.',
                'settings' => $this->defaultModuleSettings([
                    'variant' => 'slots',
                    'padding_scale' => 'compact',
                ]),
            ],
        ];
    }

    public function defaultAppearance(): array
    {
        return [
            'primary_color' => '#1a1a2e',
            'accent_color' => '#c62828',
            'background_color' => '#fafafa',
            'font_family' => 'inter',
            'sidebar_position' => 'right',
            'dark_mode_default' => false,
            'radius_preset' => 'soft',
            'shadow_preset' => 'subtle',
            'container_width' => '1280px',
            'default_theme_mode' => 'system',
            'rail_behavior' => 'sticky',
        ];
    }

    public function getDraftState(): array
    {
        $draft = LayoutRevision::query()
            ->area(self::AREA_HOME)
            ->draft()
            ->latest('updated_at')
            ->first();

        if ($draft) {
            return $this->normalizeStatePayload($draft->payload ?? []);
        }

        return $this->getBaseState();
    }

    private function getBaseState(): array
    {
        $modules = $this->getDraftModules()
            ->values()
            ->map(fn (LayoutModule $module, int $index) => $this->normalizeModule($module, $index))
            ->all();

        return [
            'area' => self::AREA_HOME,
            'name' => 'Anasayfa Taslagi',
            'modules' => $modules,
            'appearance' => $this->getAppearanceSettings(),
        ];
    }

    public function getPublishedState(): array
    {
        return $this->resolveState();
    }

    public function resolveState(?LayoutRevision $revision = null): array
    {
        $revision ??= LayoutRevision::query()
            ->area(self::AREA_HOME)
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if (! $revision) {
            return $this->getBaseState();
        }

        return $this->normalizeStatePayload($revision->payload ?? []);
    }

    public function getDraftRevision(): LayoutRevision
    {
        $draft = LayoutRevision::query()
            ->area(self::AREA_HOME)
            ->draft()
            ->latest('updated_at')
            ->first();

        if ($draft) {
            return $draft;
        }

        return $this->syncDraftRevision();
    }

    public function syncDraftRevision(?User $user = null): LayoutRevision
    {
        return LayoutRevision::query()->updateOrCreate(
            ['area' => self::AREA_HOME, 'status' => LayoutRevision::STATUS_DRAFT],
            [
                'name' => 'Aktif Taslak',
                'payload' => $this->getDraftState(),
                'created_by' => $user?->id,
            ]
        );
    }

    public function publishDraft(?User $user = null): LayoutRevision
    {
        $draft = $this->getDraftRevision();
        $state = $this->normalizeStatePayload($draft->payload ?? []);

        return DB::transaction(function () use ($draft, $state, $user) {
            LayoutRevision::query()
                ->area(self::AREA_HOME)
                ->published()
                ->update(['status' => LayoutRevision::STATUS_ARCHIVED]);

            $publishedRevision = LayoutRevision::query()->create([
                'area' => self::AREA_HOME,
                'name' => 'Canli Yayin '.now()->format('d.m.Y H:i'),
                'payload' => $state,
                'status' => LayoutRevision::STATUS_PUBLISHED,
                'created_by' => $draft->created_by,
                'published_by' => $user?->id,
                'published_at' => now(),
            ]);

            $this->applyStateToBase($state);
            $this->bumpPublicLayoutVersion();

            return $publishedRevision;
        });
    }

    public function storeDraftState(array $modules, array $appearance, ?User $user = null): LayoutRevision
    {
        $payload = $this->normalizeStatePayload([
            'area' => self::AREA_HOME,
            'name' => 'Anasayfa Taslagi',
            'modules' => $this->normalizeIncomingModules($modules),
            'appearance' => $this->normalizeAppearance($appearance),
        ]);

        return LayoutRevision::query()->updateOrCreate(
            ['area' => self::AREA_HOME, 'status' => LayoutRevision::STATUS_DRAFT],
            [
                'name' => 'Aktif Taslak',
                'payload' => $payload,
                'created_by' => $user?->id,
            ]
        );
    }

    public function restoreRevisionToDraft(LayoutRevision $revision, ?User $user = null): LayoutRevision
    {
        $state = $this->normalizeStatePayload($revision->payload ?? []);

        return LayoutRevision::query()->updateOrCreate(
            ['area' => self::AREA_HOME, 'status' => LayoutRevision::STATUS_DRAFT],
            [
                'name' => 'Aktif Taslak',
                'payload' => $state,
                'created_by' => $user?->id,
            ]
        );
    }

    public function getRevisionOptions(): array
    {
        return LayoutRevision::query()
            ->area(self::AREA_HOME)
            ->whereIn('status', [LayoutRevision::STATUS_PUBLISHED, LayoutRevision::STATUS_ARCHIVED])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (LayoutRevision $revision): array {
                $label = trim(implode(' • ', array_filter([
                    ucfirst($revision->status),
                    $revision->published_at?->format('d.m.Y H:i'),
                    $revision->name,
                ])));

                return [$revision->id => $label];
            })
            ->all();
    }

    public function getPublishedRevision(): ?LayoutRevision
    {
        return LayoutRevision::query()
            ->area(self::AREA_HOME)
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function getPreviewUrl(LayoutRevision $revision, string $locale = 'tr'): string
    {
        return URL::temporarySignedRoute(
            'layout.preview.home',
            now()->addMinutes(30),
            [
                'revision' => $revision->id,
                'locale' => $locale,
            ]
        );
    }

    public function getAppearanceSettings(): array
    {
        $defaults = $this->defaultAppearance();

        foreach (array_keys($defaults) as $key) {
            $defaults[$key] = Setting::get('appearance', $key, $defaults[$key]);
        }

        return $this->normalizeAppearance($defaults);
    }

    public function getAppearanceCssVariables(?array $appearance = null): array
    {
        $appearance ??= $this->getPublishedState()['appearance'] ?? $this->defaultAppearance();

        $radiusMap = [
            'sharp' => '0.55rem',
            'soft' => '1rem',
            'rounded' => '1.4rem',
        ];

        $shadowMap = [
            'none' => 'none',
            'subtle' => '0 18px 40px -24px rgba(15, 23, 42, 0.25)',
            'elevated' => '0 26px 60px -28px rgba(15, 23, 42, 0.35)',
        ];

        return [
            '--adh-brand-primary' => (string) ($appearance['primary_color'] ?? '#1a1a2e'),
            '--adh-brand-accent' => (string) ($appearance['accent_color'] ?? '#c62828'),
            '--adh-surface-bg' => (string) ($appearance['background_color'] ?? '#fafafa'),
            '--adh-radius' => $radiusMap[$appearance['radius_preset'] ?? 'soft'] ?? $radiusMap['soft'],
            '--adh-shadow' => $shadowMap[$appearance['shadow_preset'] ?? 'subtle'] ?? $shadowMap['subtle'],
            '--adh-content-width' => (string) ($appearance['container_width'] ?? '1280px'),
            '--adh-font-family' => match ($appearance['font_family'] ?? 'inter') {
                'lora' => '"Lora", serif',
                'poppins' => '"Poppins", sans-serif',
                'inter' => '"Inter", sans-serif',
                default => '"Inter", sans-serif',
            },
        ];
    }

    private function getDraftModules(): Collection
    {
        $this->ensureDefaultModules();

        return LayoutModule::query()->orderBy('sort_order')->get();
    }

    private function ensureDefaultModules(): void
    {
        foreach ($this->getModuleDefinitions() as $index => $definition) {
            LayoutModule::query()->firstOrCreate(
                ['key' => $index],
                [
                    'name' => $definition['name'],
                    'is_active' => true,
                    'sort_order' => $this->defaultSortOrder($index),
                    'settings' => $definition['settings'],
                ]
            );
        }
    }

    private function applyStateToBase(array $state): void
    {
        foreach ($state['modules'] as $index => $moduleState) {
            LayoutModule::query()->updateOrCreate(
                ['key' => $moduleState['key']],
                [
                    'name' => $moduleState['name'],
                    'is_active' => (bool) $moduleState['is_active'],
                    'sort_order' => $index + 1,
                    'settings' => $moduleState['settings'],
                ]
            );
        }

        foreach ($state['appearance'] as $key => $value) {
            Setting::set('appearance', $key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }
    }

    private function normalizeModule(LayoutModule $module, int $index): array
    {
        $definition = $this->getModuleDefinitions()[$module->key] ?? [
            'name' => $module->name,
            'description' => '',
            'settings' => $this->defaultModuleSettings(),
        ];

        return [
            'id' => $module->id,
            'key' => $module->key,
            'name' => $module->name ?: $definition['name'],
            'description' => $definition['description'],
            'is_active' => (bool) $module->is_active,
            'sort_order' => $module->sort_order ?: ($index + 1),
            'settings' => $this->normalizeModuleSettings(
                array_replace_recursive($definition['settings'], $module->settings ?? []),
                $definition['settings']
            ),
        ];
    }

    private function normalizeStatePayload(array $payload): array
    {
        $definitions = $this->getModuleDefinitions();
        $providedModules = collect($payload['modules'] ?? [])
            ->filter(fn ($module) => is_array($module) && array_key_exists((string) ($module['key'] ?? ''), $definitions))
            ->sortBy(fn (array $module, int $index): int => (int) ($module['sort_order'] ?? ($index + 1)))
            ->values();

        $seen = [];
        $modules = $providedModules
            ->map(function (array $module, int $index) use (&$seen): array {
                $key = (string) $module['key'];
                $seen[$key] = true;

                return $this->normalizeModuleState($key, $module, $index);
            })
            ->values()
            ->all();

        foreach (array_keys($definitions) as $key) {
            if (isset($seen[$key])) {
                continue;
            }

            $modules[] = $this->normalizeModuleState($key, ['key' => $key], count($modules));
        }

        return [
            'area' => $payload['area'] ?? self::AREA_HOME,
            'name' => $payload['name'] ?? 'Anasayfa Taslagi',
            'modules' => $modules,
            'appearance' => $this->normalizeAppearance(
                array_replace($this->defaultAppearance(), Arr::wrap($payload['appearance'] ?? []))
            ),
        ];
    }

    private function defaultModuleSettings(array $overrides = []): array
    {
        return array_replace([
            'variant' => 'default',
            'background_tone' => 'surface',
            'accent_mode' => 'brand',
            'padding_scale' => 'regular',
            'image_ratio' => '16:9',
            'card_density' => 'comfortable',
            'container_width' => 'content',
            'columns_mobile' => 1,
            'columns_tablet' => 2,
            'columns_desktop' => 3,
            'content_limit' => 6,
            'title_override' => [
                'tr' => '',
                'en' => '',
                'ku' => '',
            ],
            'subtitle_override' => [
                'tr' => '',
                'en' => '',
                'ku' => '',
            ],
            'cta_enabled' => false,
            'cta_label' => [
                'tr' => '',
                'en' => '',
                'ku' => '',
            ],
            'cta_url' => '',
            'show_on_mobile' => true,
            'show_on_tablet' => true,
            'show_on_desktop' => true,
        ], $overrides);
    }

    private function defaultSortOrder(string $key): int
    {
        return array_search($key, array_keys($this->getModuleDefinitions()), true) + 1;
    }

    private function normalizeAppearance(array $appearance): array
    {
        $normalized = array_replace($this->defaultAppearance(), $appearance);
        $normalized['primary_color'] = $this->normalizeHexColor($normalized['primary_color'] ?? null, '#1a1a2e');
        $normalized['accent_color'] = $this->normalizeHexColor($normalized['accent_color'] ?? null, '#c62828');
        $normalized['background_color'] = $this->normalizeHexColor($normalized['background_color'] ?? null, '#fafafa');
        $normalized['font_family'] = $this->normalizeEnum($normalized['font_family'] ?? null, ['inter', 'lora', 'poppins'], 'inter');
        $normalized['sidebar_position'] = $this->normalizeEnum($normalized['sidebar_position'] ?? null, ['left', 'right', 'none'], 'right');
        $normalized['dark_mode_default'] = (bool) filter_var(
            $normalized['dark_mode_default'],
            FILTER_VALIDATE_BOOL
        );
        $normalized['radius_preset'] = $this->normalizeEnum($normalized['radius_preset'] ?? null, ['sharp', 'soft', 'rounded'], 'soft');
        $normalized['shadow_preset'] = $this->normalizeEnum($normalized['shadow_preset'] ?? null, ['none', 'subtle', 'elevated'], 'subtle');
        $normalized['container_width'] = $this->normalizeAppearanceWidth($normalized['container_width'] ?? null, '1280px');
        $normalized['default_theme_mode'] = $this->normalizeEnum($normalized['default_theme_mode'] ?? null, ['system', 'light', 'dark'], 'system');
        $normalized['rail_behavior'] = $this->normalizeEnum($normalized['rail_behavior'] ?? null, ['sticky', 'static'], 'sticky');

        return $normalized;
    }

    private function normalizeIncomingModules(array $modules): array
    {
        $definitions = $this->getModuleDefinitions();
        $submitted = collect($modules)->values();
        $normalized = [];
        $seen = [];

        foreach ($submitted as $module) {
            if (! is_array($module)) {
                continue;
            }

            $key = (string) ($module['key'] ?? '');

            if ($key === '' || isset($seen[$key]) || ! array_key_exists($key, $definitions)) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = $this->normalizeModuleState($key, $module, count($normalized));
        }

        foreach (array_keys($definitions) as $key) {
            if (isset($seen[$key])) {
                continue;
            }

            $normalized[] = $this->normalizeModuleState($key, ['key' => $key], count($normalized));
        }

        return $normalized;
    }

    private function normalizeModuleState(string $key, array $module, int $index): array
    {
        $definition = $this->getModuleDefinitions()[$key] ?? [
            'name' => $module['name'] ?? $key,
            'description' => '',
            'settings' => $this->defaultModuleSettings(),
        ];

        return [
            'id' => $module['id'] ?? null,
            'key' => $key,
            'name' => $module['name'] ?? $definition['name'],
            'description' => $definition['description'],
            'is_active' => (bool) ($module['is_active'] ?? true),
            'sort_order' => $index + 1,
            'settings' => $this->normalizeModuleSettings(
                array_replace_recursive($definition['settings'], Arr::wrap($module['settings'] ?? [])),
                $definition['settings']
            ),
        ];
    }

    private function normalizeModuleSettings(array $settings, array $defaults = []): array
    {
        $normalized = array_replace_recursive($this->defaultModuleSettings(), $defaults, $settings);

        $normalized['variant'] = $this->normalizeEnum(
            $normalized['variant'] ?? null,
            ['default', 'ticker', 'editorial', 'cards', 'lead-with-list', 'feature-list', 'ranked-list', 'shortcut-grid', 'stack', 'slots'],
            $defaults['variant'] ?? 'default'
        );
        $normalized['background_tone'] = $this->normalizeEnum($normalized['background_tone'] ?? null, ['surface', 'muted', 'contrast'], 'surface');
        $normalized['accent_mode'] = $this->normalizeEnum($normalized['accent_mode'] ?? null, ['brand', 'neutral', 'soft', 'alert'], 'brand');
        $normalized['padding_scale'] = $this->normalizeEnum($normalized['padding_scale'] ?? null, ['compact', 'regular', 'relaxed'], 'regular');
        $normalized['image_ratio'] = $this->normalizeEnum($normalized['image_ratio'] ?? null, ['16:9', '16:10', '4:3', '1:1', '4:5', '5:6', '3:4', '21:9'], $defaults['image_ratio'] ?? '16:9');
        $normalized['card_density'] = $this->normalizeEnum($normalized['card_density'] ?? null, ['compact', 'comfortable', 'airy'], 'comfortable');
        $normalized['container_width'] = $this->normalizeEnum($normalized['container_width'] ?? null, ['content', 'wide', 'full'], $defaults['container_width'] ?? 'content');
        $normalized['columns_mobile'] = $this->clampInteger($normalized['columns_mobile'] ?? 1, 1, 4);
        $normalized['columns_tablet'] = $this->clampInteger($normalized['columns_tablet'] ?? 2, 1, 6);
        $normalized['columns_desktop'] = $this->clampInteger($normalized['columns_desktop'] ?? 3, 1, 12);
        $normalized['content_limit'] = $this->clampInteger($normalized['content_limit'] ?? 6, 1, 24);

        if (
            ($normalized['variant'] ?? null) === 'lead-with-list'
            && (int) ($defaults['content_limit'] ?? 0) === 12
            && (int) $normalized['content_limit'] === 8
        ) {
            $normalized['content_limit'] = 12;
        }

        $normalized['title_override'] = $this->normalizeTranslations($normalized['title_override'] ?? [], 120);
        $normalized['subtitle_override'] = $this->normalizeTranslations($normalized['subtitle_override'] ?? [], 240);
        $normalized['cta_enabled'] = (bool) ($normalized['cta_enabled'] ?? false);
        $normalized['cta_label'] = $this->normalizeTranslations($normalized['cta_label'] ?? [], 80);
        $normalized['cta_url'] = $this->normalizeActionUrl($normalized['cta_url'] ?? '');
        $normalized['show_on_mobile'] = (bool) ($normalized['show_on_mobile'] ?? true);
        $normalized['show_on_tablet'] = (bool) ($normalized['show_on_tablet'] ?? true);
        $normalized['show_on_desktop'] = (bool) ($normalized['show_on_desktop'] ?? true);

        return $normalized;
    }

    private function normalizeTranslations(mixed $translations, int $limit): array
    {
        $translations = is_array($translations) ? $translations : [];
        $normalized = [];

        foreach (['tr', 'en', 'ku'] as $locale) {
            $value = trim((string) ($translations[$locale] ?? ''));
            $normalized[$locale] = mb_substr($value, 0, $limit);
        }

        return $normalized;
    }

    private function normalizeActionUrl(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        foreach (['/', '#', 'http://', 'https://', 'mailto:', 'tel:'] as $allowedPrefix) {
            if (str_starts_with($value, $allowedPrefix)) {
                return mb_substr($value, 0, 255);
            }
        }

        return '';
    }

    private function normalizeHexColor(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }

    private function normalizeAppearanceWidth(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        if (preg_match('/^(9[6-9]\d|1\d{3}|1600)px$/', $value)) {
            return $value;
        }

        return $fallback;
    }

    private function normalizeEnum(mixed $value, array $allowed, string $fallback): string
    {
        $value = (string) $value;

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function clampInteger(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private function bumpPublicLayoutVersion(): void
    {
        Setting::set('system', 'layout_version', (string) now()->format('U.u'));
    }
}
