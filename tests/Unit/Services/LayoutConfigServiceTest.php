<?php

namespace Tests\Unit\Services;

use App\Models\LayoutModule;
use App\Models\LayoutRevision;
use App\Services\LayoutConfigService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_news_legacy_limit_is_upgraded_to_editorial_default(): void
    {
        $service = app(LayoutConfigService::class);
        $service->getDraftState();

        LayoutModule::query()
            ->where('key', 'latest_news')
            ->update([
                'settings' => [
                    'variant' => 'lead-with-list',
                    'content_limit' => 8,
                ],
            ]);

        $state = $service->getDraftState();
        $latestNews = collect($state['modules'])->firstWhere('key', 'latest_news');

        $this->assertSame(12, data_get($latestNews, 'settings.content_limit'));
    }

    public function test_publish_and_restore_revision_work_with_home_layout_state(): void
    {
        $service = app(LayoutConfigService::class);
        $user = User::factory()->create(['is_active' => true]);

        $draftState = $service->getDraftState();
        $modules = $draftState['modules'];
        $appearance = $draftState['appearance'];

        $heroIndex = collect($modules)->search(fn (array $module) => $module['key'] === 'hero');
        $latestIndex = collect($modules)->search(fn (array $module) => $module['key'] === 'latest_news');

        $heroModule = $modules[$heroIndex];
        $latestModule = $modules[$latestIndex];
        $modules[$heroIndex] = $latestModule;
        $modules[$latestIndex] = $heroModule;
        $appearance['primary_color'] = '#123456';

        $service->storeDraftState($modules, $appearance, $user);
        $published = $service->publishDraft($user);
        $expectedOrder = collect($modules)->pluck('key')->values()->all();

        $this->assertSame(LayoutRevision::STATUS_PUBLISHED, $published->status);
        $this->assertSame('#123456', data_get($published->payload, 'appearance.primary_color'));
        $this->assertSame($expectedOrder, collect($published->payload['modules'] ?? [])->pluck('key')->values()->all());

        $freshDraft = $service->getDraftState();
        $freshModules = $freshDraft['modules'];
        $freshAppearance = $freshDraft['appearance'];
        $freshAppearance['primary_color'] = '#654321';

        $service->storeDraftState($freshModules, $freshAppearance, $user);
        $restoredDraft = $service->restoreRevisionToDraft($published, $user);

        $this->assertSame(LayoutRevision::STATUS_DRAFT, $restoredDraft->status);
        $this->assertSame('#123456', data_get($restoredDraft->payload, 'appearance.primary_color'));
        $this->assertSame($expectedOrder, collect($restoredDraft->payload['modules'] ?? [])->pluck('key')->values()->all());
    }
}
