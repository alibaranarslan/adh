<?php

namespace App\Console\Commands;

use App\Services\LayoutConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PublishFullHomeLayoutCommand extends Command
{
    protected $signature = 'layout:publish-full-home {--dry-run : Show the resulting module order without publishing}';

    protected $description = 'Publish the homepage with every storefront block active and news-heavy limits.';

    public function handle(LayoutConfigService $layoutConfigService): int
    {
        $currentState = $layoutConfigService->getPublishedState();
        $definitions = $layoutConfigService->getModuleDefinitions();
        $currentModules = collect($currentState['modules'] ?? [])->keyBy('key');
        $limits = [
            'breaking_bar' => 10,
            'hero' => 5,
            'local_news' => 8,
            'highlights' => 8,
            'most_read' => 8,
            'region_news' => 9,
            'latest_news' => 16,
            'news_river' => 20,
            'category_shortcuts' => 12,
        ];

        $modules = collect(array_keys($definitions))
            ->values()
            ->map(function (string $key, int $index) use ($definitions, $currentModules, $limits): array {
                $existing = $currentModules->get($key, []);
                $settings = array_replace_recursive(
                    $definitions[$key]['settings'] ?? [],
                    $existing['settings'] ?? []
                );

                $settings['show_on_mobile'] = true;
                $settings['show_on_tablet'] = true;
                $settings['show_on_desktop'] = true;

                if (isset($limits[$key])) {
                    $settings['content_limit'] = $limits[$key];
                }

                if ($key === 'ads') {
                    $settings['padding_scale'] = 'compact';
                }

                return [
                    'id' => $existing['id'] ?? null,
                    'key' => $key,
                    'name' => $definitions[$key]['name'] ?? $key,
                    'description' => $definitions[$key]['description'] ?? '',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'settings' => $settings,
                ];
            })
            ->all();

        if ($this->option('dry-run')) {
            foreach ($modules as $module) {
                $this->line(sprintf(
                    '%02d %s active=%s limit=%s',
                    $module['sort_order'],
                    $module['key'],
                    $module['is_active'] ? 'yes' : 'no',
                    $module['settings']['content_limit'] ?? '-'
                ));
            }

            return self::SUCCESS;
        }

        $draft = $layoutConfigService->storeDraftState(
            $modules,
            $currentState['appearance'] ?? $layoutConfigService->defaultAppearance()
        );

        $published = $layoutConfigService->publishDraft();

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        $this->info(sprintf(
            'Published full homepage layout. draft_id=%d published_id=%d modules=%d',
            $draft->id,
            $published->id,
            count($modules)
        ));

        return self::SUCCESS;
    }
}
