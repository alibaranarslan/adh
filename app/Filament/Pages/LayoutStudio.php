<?php

namespace App\Filament\Pages;

use App\Models\LayoutRevision;
use App\Services\LayoutConfigService;
use App\Support\AdminOperationAuditor;
use App\Support\AdminPrivileges;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LayoutStudio extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Yerleşim Stüdyosu';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $title = 'Yerleşim Stüdyosu';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.layout-studio';

    private const EDITORIAL_MODULE_KEYS = [
        'hero',
        'local_news',
        'highlights',
        'most_read',
        'region_news',
        'latest_news',
        'category_shortcuts',
    ];

    private const ACTION_URL_PREFIXES = ['/', '#', 'http://', 'https://', 'mailto:', 'tel:'];

    public array $modules = [];

    public array $appearance = [];

    public ?string $selectedModuleKey = null;

    public ?string $restoreRevisionId = null;

    public bool $hasUnsavedChanges = false;

    public string $previewDevice = 'desktop';

    public function mount(LayoutConfigService $layoutConfigService): void
    {
        $state = $layoutConfigService->getDraftState();

        $this->modules = $state['modules'];
        $this->appearance = $state['appearance'];
        $this->selectedModuleKey = $this->modules[0]['key'] ?? null;
    }

    public static function canAccess(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'modules') || str_starts_with($property, 'appearance')) {
            $this->hasUnsavedChanges = true;
        }
    }

    public function selectModule(string $key): void
    {
        $this->selectedModuleKey = $key;
    }

    public function updateModuleOrder(array $order): void
    {
        $orderedIds = array_map('strval', $order);

        $this->modules = collect($this->modules)
            ->sortBy(function (array $module) use ($orderedIds): int {
                $position = array_search((string) ($module['id'] ?? ''), $orderedIds, true);

                return $position === false ? PHP_INT_MAX : $position;
            })
            ->values()
            ->map(function (array $module, int $index): array {
                $module['sort_order'] = $index + 1;

                return $module;
            })
            ->all();
        $this->hasUnsavedChanges = true;

        Notification::make()->success()->title('Sıralama taslakta güncellendi')->send();
    }

    public function toggleModule(int $id): void
    {
        foreach ($this->modules as $index => $module) {
            if ((int) ($module['id'] ?? 0) !== $id) {
                continue;
            }

            $this->modules[$index]['is_active'] = ! (bool) ($module['is_active'] ?? true);
            $this->selectedModuleKey = $module['key'] ?? $this->selectedModuleKey;
            $this->hasUnsavedChanges = true;

            Notification::make()->success()->title('Modül taslakta güncellendi')->send();

            return;
        }
    }

    public function moveModuleUp(int $id): void
    {
        $this->moveModule($id, -1);
    }

    public function moveModuleDown(int $id): void
    {
        $this->moveModule($id, 1);
    }

    public function setPreviewDevice(string $device): void
    {
        if (! in_array($device, ['desktop', 'mobile'], true)) {
            return;
        }

        $this->previewDevice = $device;
    }

    public function applyModulePreset(string $preset): void
    {
        $index = $this->getSelectedModuleIndex();

        if ($index === null) {
            return;
        }

        $module = $this->modules[$index];
        $definition = app(LayoutConfigService::class)->getModuleDefinitions()[$module['key']] ?? [];
        $baseSettings = $definition['settings'] ?? [];
        $currentSettings = $module['settings'] ?? [];
        $currentLimit = max(1, (int) data_get($currentSettings, 'content_limit', 6));

        $overrides = match ($preset) {
            'balanced' => [
                'background_tone' => 'surface',
                'accent_mode' => 'brand',
                'padding_scale' => 'regular',
                'card_density' => 'comfortable',
                'container_width' => 'content',
            ],
            'feature' => [
                'background_tone' => 'contrast',
                'accent_mode' => 'alert',
                'padding_scale' => 'relaxed',
                'card_density' => 'airy',
                'container_width' => in_array($module['key'], ['hero', 'breaking_bar'], true) ? 'full' : 'wide',
                'content_limit' => min($currentLimit, in_array($module['key'], ['hero', 'breaking_bar'], true) ? 5 : 6),
            ],
            'compact' => [
                'background_tone' => 'muted',
                'accent_mode' => 'neutral',
                'padding_scale' => 'compact',
                'card_density' => 'compact',
                'container_width' => 'content',
                'content_limit' => max(1, min($currentLimit, 4)),
            ],
            default => [],
        };

        if ($overrides === []) {
            return;
        }

        $this->modules[$index]['settings'] = array_replace_recursive($baseSettings, $currentSettings, $overrides);
        $this->hasUnsavedChanges = true;

        Notification::make()
            ->success()
            ->title('Modül ön ayarı taslağa işlendi')
            ->body('Önizleme son kaydedilen taslağı gösterir; değişikliği görmek için taslağı kaydedin.')
            ->send();
    }

    public function resetSelectedModuleSettings(): void
    {
        $index = $this->getSelectedModuleIndex();

        if ($index === null) {
            return;
        }

        $module = $this->modules[$index];
        $definition = app(LayoutConfigService::class)->getModuleDefinitions()[$module['key']] ?? [];

        if (! isset($definition['settings'])) {
            return;
        }

        $this->modules[$index]['settings'] = $definition['settings'];
        $this->hasUnsavedChanges = true;

        Notification::make()
            ->success()
            ->title('Modül varsayılanına döndü')
            ->body('Seçilen bloğun ayarları taslakta başlangıç değerlerine alındı.')
            ->send();
    }

    public function applyAppearancePreset(string $preset): void
    {
        $defaults = app(LayoutConfigService::class)->defaultAppearance();

        $overrides = match ($preset) {
            'gazete' => [
                'primary_color' => '#1a1a2e',
                'accent_color' => '#b91c1c',
                'background_color' => '#fafafa',
                'font_family' => 'lora',
                'radius_preset' => 'sharp',
                'shadow_preset' => 'subtle',
                'container_width' => '1280px',
                'default_theme_mode' => 'light',
                'sidebar_position' => 'right',
                'rail_behavior' => 'sticky',
                'dark_mode_default' => false,
            ],
            'temiz' => [
                'primary_color' => '#0f172a',
                'accent_color' => '#c2410c',
                'background_color' => '#ffffff',
                'font_family' => 'inter',
                'radius_preset' => 'soft',
                'shadow_preset' => 'none',
                'container_width' => '1240px',
                'default_theme_mode' => 'light',
                'sidebar_position' => 'right',
                'rail_behavior' => 'static',
                'dark_mode_default' => false,
            ],
            'gece' => [
                'primary_color' => '#0f172a',
                'accent_color' => '#f59e0b',
                'background_color' => '#0b1120',
                'font_family' => 'lora',
                'radius_preset' => 'soft',
                'shadow_preset' => 'elevated',
                'container_width' => '1280px',
                'default_theme_mode' => 'dark',
                'sidebar_position' => 'right',
                'rail_behavior' => 'sticky',
                'dark_mode_default' => true,
            ],
            default => [],
        };

        if ($overrides === []) {
            return;
        }

        $this->appearance = array_replace($defaults, $this->appearance, $overrides);
        $this->hasUnsavedChanges = true;

        Notification::make()
            ->success()
            ->title('Görünüm ön ayarı taslağa işlendi')
            ->body('Public görünüm değişmez; önce taslağı kaydedip önizleyin, sonra süper admin canlıya alabilir.')
            ->send();
    }

    public function getSelectedModuleWarnings(): array
    {
        $module = $this->getSelectedModuleState();
        $settings = $module['settings'] ?? [];
        $warnings = [];

        if (! $module) {
            return $warnings;
        }

        if (
            ! data_get($settings, 'show_on_mobile', true)
            && ! data_get($settings, 'show_on_tablet', true)
            && ! data_get($settings, 'show_on_desktop', true)
        ) {
            $warnings[] = 'Bu modül hiçbir cihazda görünmeyecek.';
        }

        if (data_get($settings, 'cta_enabled') && blank(data_get($settings, 'cta_url'))) {
            $warnings[] = 'Buton açık ancak bağlantı girilmemiş.';
        }

        if (
            data_get($settings, 'cta_enabled')
            && blank(data_get($settings, 'cta_label.tr'))
            && blank(data_get($settings, 'cta_label.en'))
            && blank(data_get($settings, 'cta_label.ku'))
        ) {
            $warnings[] = 'Buton açık ancak hiçbir dil için etiket yazılmamış.';
        }

        if ((int) data_get($settings, 'content_limit', 0) < 1) {
            $warnings[] = 'İçerik limiti en az 1 olmalı.';
        }

        return $warnings;
    }

    public function getLayoutReadiness(): array
    {
        $errors = [];
        $warnings = [];
        $activeModules = collect($this->modules)
            ->filter(fn (array $module): bool => (bool) ($module['is_active'] ?? true))
            ->values();

        if ($activeModules->whereIn('key', self::EDITORIAL_MODULE_KEYS)->isEmpty()) {
            $errors[] = 'En az bir editoryal anasayfa modülü aktif olmalı.';
        }

        $hero = collect($this->modules)->firstWhere('key', 'hero');
        $heroSettings = $hero['settings'] ?? [];

        if (
            ! $hero
            || ! (bool) ($hero['is_active'] ?? true)
            || (
                ! data_get($heroSettings, 'show_on_mobile', true)
                && ! data_get($heroSettings, 'show_on_tablet', true)
                && ! data_get($heroSettings, 'show_on_desktop', true)
            )
        ) {
            $errors[] = 'Hero modülü en az bir cihaz kırılımında görünür kalmalı.';
        }

        foreach ($activeModules as $module) {
            $settings = $module['settings'] ?? [];
            $moduleName = (string) ($module['name'] ?? $module['key'] ?? 'Modul');

            if (
                ! data_get($settings, 'show_on_mobile', true)
                && ! data_get($settings, 'show_on_tablet', true)
                && ! data_get($settings, 'show_on_desktop', true)
            ) {
                $errors[] = "{$moduleName} aktif, ancak hiçbir cihazda görünür değil.";
            }

            if ((int) data_get($settings, 'content_limit', 1) < 1) {
                $errors[] = "{$moduleName} içerik limiti en az 1 olmalı.";
            }

            if ((bool) data_get($settings, 'cta_enabled', false)) {
                $ctaUrl = trim((string) data_get($settings, 'cta_url', ''));
                $ctaLabel = trim((string) data_get($settings, 'cta_label.tr', ''));

                if ($ctaLabel === '') {
                    $errors[] = "{$moduleName} CTA açık, ancak TR buton etiketi yok.";
                }

                if ($ctaUrl === '' || ! $this->isAllowedActionUrl($ctaUrl)) {
                    $errors[] = "{$moduleName} CTA açık, ancak geçerli bir bağlantı yok.";
                }
            }

            if ((int) data_get($settings, 'content_limit', 0) > 16) {
                $warnings[] = "{$moduleName} içerik limiti mobil yoğunluk riski oluşturabilir.";
            }

            if (($module['key'] ?? null) === 'ads') {
                $warnings[] = 'Reklam modülü aktif; yayından önce render edilebilir reklam kaydı olduğu kontrol edilmeli.';
            }

            if (($module['key'] ?? null) === 'sidebar_widgets') {
                $warnings[] = 'Yan panel modülü aktif; mobil önizlemede aşağı akışta kontrol edilmeli.';
            }
        }

        return [
            'status' => $errors === [] ? 'ready' : 'blocked',
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public function saveDraft(): void
    {
        app(LayoutConfigService::class)->storeDraftState($this->modules, $this->appearance, auth()->user());
        $this->refreshFromDraft(app(LayoutConfigService::class));

        AdminOperationAuditor::record('layout.save_draft', null, [
            'module_count' => count($this->modules),
            'selected_module' => $this->selectedModuleKey,
        ]);

        Notification::make()
            ->success()
            ->title('Taslak kaydedildi')
            ->body('Düzen değişiklikleri kaydedildi. İmzalı önizleme artık bu taslakla çalışır.')
            ->send();
    }

    public function publishDraft(): void
    {
        if (! $this->canPublishLayout()) {
            Notification::make()
                ->warning()
                ->title('Yayın yetkisi gerekli')
                ->body('Canlıya alma işlemi yalnızca yetkili yönetici rolüyle yapılabilir.')
                ->send();

            AdminOperationAuditor::record('layout.publish_blocked', null, [
                'reason' => 'missing_publish_authority',
            ], 'blocked', 'Layout yayını yetki nedeniyle engellendi');

            return;
        }

        $readiness = $this->getLayoutReadiness();

        if ($readiness['errors'] !== []) {
            Notification::make()
                ->danger()
                ->title('Yayın kalite kapısı geçilmedi')
                ->body(implode(' ', $readiness['errors']))
                ->send();

            AdminOperationAuditor::record('layout.publish_blocked', null, [
                'reason' => 'readiness_errors',
                'errors' => $readiness['errors'],
            ], 'blocked', 'Layout yayını kalite kapısı nedeniyle engellendi');

            return;
        }

        $service = app(LayoutConfigService::class);
        $service->storeDraftState($this->modules, $this->appearance, auth()->user());
        $revision = $service->publishDraft(auth()->user());
        $this->refreshFromDraft($service);

        AdminOperationAuditor::record('layout.publish_draft', $revision, [
            'revision_name' => $revision->name,
            'warnings' => $readiness['warnings'],
        ]);

        $body = 'Yeni düzen yayınlandı: '.$revision->name;

        if ($readiness['warnings'] !== []) {
            $body .= ' Uyarılar: '.implode(' ', $readiness['warnings']);
        }

        Notification::make()
            ->success()
            ->title('Canlıya alındı')
            ->body($body)
            ->send();
    }

    public function restoreRevision(): void
    {
        if (! $this->canPublishLayout()) {
            Notification::make()
                ->warning()
                ->title('Yayın yetkisi gerekli')
                ->body('Geri alma işlemi yalnızca yetkili yönetici rolüyle yapılabilir.')
                ->send();

            AdminOperationAuditor::record('layout.restore_blocked', null, [
                'reason' => 'missing_publish_authority',
            ], 'blocked', 'Layout geri alma yetki nedeniyle engellendi');

            return;
        }

        if (! filled($this->restoreRevisionId)) {
            Notification::make()
                ->warning()
                ->title('Revizyon seçilmedi')
                ->body('Taslağı geri yüklemek için bir revizyon seçin.')
                ->send();

            return;
        }

        $revision = LayoutRevision::query()->find($this->restoreRevisionId);

        if (! $revision) {
            Notification::make()->danger()->title('Revizyon bulunamadı')->send();

            return;
        }

        $service = app(LayoutConfigService::class);
        $service->restoreRevisionToDraft($revision, auth()->user());
        $this->refreshFromDraft($service);
        $this->restoreRevisionId = null;

        AdminOperationAuditor::record('layout.restore_revision', $revision, [
            'revision_name' => $revision->name,
        ]);

        Notification::make()
            ->success()
            ->title('Taslak geri yüklendi')
            ->body('Seçilen revizyon taslak düzene yüklendi.')
            ->send();
    }

    public function canPublishLayout(): bool
    {
        return AdminPrivileges::canPublishConfiguration(auth()->user());
    }

    public function isPublishRestricted(): bool
    {
        return self::canAccess() && ! $this->canPublishLayout();
    }

    public function draftSyncLabel(): string
    {
        return $this->hasUnsavedChanges
            ? 'Kaydedilmemiş değişiklik var'
            : 'Taslak veritabanı ile senkron';
    }

    public function publishAuthorityLabel(): string
    {
        return $this->canPublishLayout()
            ? 'Canlıya alma yetkisi var'
            : 'Yalnız taslak düzenleme';
    }

    public function readinessStatusLabel(): string
    {
        $readiness = $this->getLayoutReadiness();

        return $readiness['status'] === 'ready'
            ? 'Kalite kapısı hazır'
            : 'Kalite kapısı blokluyor';
    }

    public function previewFreshnessLabel(): string
    {
        return $this->hasUnsavedChanges
            ? 'Önizleme son kaydedilen taslağı gösterir'
            : 'Önizleme güncel taslakla çalışır';
    }

    public function getSelectedModuleIndex(): ?int
    {
        foreach ($this->modules as $index => $module) {
            if (($module['key'] ?? null) === $this->selectedModuleKey) {
                return $index;
            }
        }

        return null;
    }

    public function getSelectedModuleState(): ?array
    {
        $index = $this->getSelectedModuleIndex();

        return $index === null ? null : $this->modules[$index];
    }

    public function getPublishedRevision(): ?LayoutRevision
    {
        return app(LayoutConfigService::class)->getPublishedRevision();
    }

    public function getDraftRevision(): LayoutRevision
    {
        return app(LayoutConfigService::class)->getDraftRevision();
    }

    public function getPreviewUrls(): array
    {
        $service = app(LayoutConfigService::class);
        $draft = $service->getDraftRevision();

        return [
            'tr' => $service->getPreviewUrl($draft, 'tr'),
            'en' => $service->getPreviewUrl($draft, 'en'),
            'ku' => $service->getPreviewUrl($draft, 'ku'),
        ];
    }

    public function getRevisionOptions(): array
    {
        return app(LayoutConfigService::class)->getRevisionOptions();
    }

    private function refreshFromDraft(LayoutConfigService $layoutConfigService): void
    {
        $state = $layoutConfigService->getDraftState();
        $this->modules = $state['modules'];
        $this->appearance = $state['appearance'];
        $this->selectedModuleKey = $this->selectedModuleKey ?: ($this->modules[0]['key'] ?? null);
        $this->hasUnsavedChanges = false;
    }

    private function moveModule(int $id, int $direction): void
    {
        $index = collect($this->modules)->search(
            fn (array $module): bool => (int) ($module['id'] ?? 0) === $id
        );

        if ($index === false) {
            return;
        }

        $targetIndex = $index + $direction;

        if ($targetIndex < 0 || $targetIndex >= count($this->modules)) {
            return;
        }

        $modules = $this->modules;
        [$modules[$index], $modules[$targetIndex]] = [$modules[$targetIndex], $modules[$index]];

        $this->modules = collect($modules)
            ->values()
            ->map(function (array $module, int $position): array {
                $module['sort_order'] = $position + 1;

                return $module;
            })
            ->all();
        $this->hasUnsavedChanges = true;

        Notification::make()->success()->title('Modül sırası taslakta güncellendi')->send();
    }

    private function isAllowedActionUrl(string $value): bool
    {
        foreach (self::ACTION_URL_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
