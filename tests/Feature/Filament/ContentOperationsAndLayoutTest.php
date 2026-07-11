<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\LayoutManager;
use App\Filament\Pages\LayoutStudio;
use App\Filament\Pages\MediaLibrary;
use App\Models\Category;
use App\Models\LayoutRevision;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\LayoutConfigService;
use App\Services\TagMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class ContentOperationsAndLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_content_management_pages_and_media_tools(): void
    {
        $admin = $this->makeAdmin();
        $this->grantContentPermissions($admin);

        Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Page::query()->create([
            'title' => ['tr' => 'Kurumsal Sayfa'],
            'slug' => 'kurumsal-sayfa',
            'content' => ['tr' => 'Icerik'],
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)->get('/admin/categories')->assertOk();
        $this->actingAs($admin)->get('/admin/categories/create')->assertOk();
        $this->actingAs($admin)->get('/admin/tags')->assertOk();
        $this->actingAs($admin)->get('/admin/tags/create')->assertOk();
        $this->actingAs($admin)->get('/admin/pages')->assertOk();
        $this->actingAs($admin)->get('/admin/pages/create')->assertOk();
        $this->actingAs($admin)->get('/admin/local-info-entries')->assertOk();
        $this->actingAs($admin)->get('/admin/local-info-entries/create')->assertOk();
        $this->actingAs($admin)->get('/admin/advertisements')->assertOk();
        $this->actingAs($admin)->get('/admin/advertisements/create')->assertOk();
        $this->actingAs($admin)->get('/admin/media-library')->assertOk();
        $this->actingAs($admin)->get('/admin/layout-studio')->assertOk();
        $this->actingAs($admin)->get('/admin/news-articles/create')->assertOk();
    }

    public function test_tag_merge_service_reassigns_articles_without_duplicate_relations(): void
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Etiket Test Haberi'],
            'slug' => 'etiket-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $target = Tag::query()->create([
            'name' => ['tr' => 'Ana Etiket'],
            'slug' => 'ana-etiket',
        ]);

        $source = Tag::query()->create([
            'name' => ['tr' => 'Birlesen Etiket'],
            'slug' => 'birlesen-etiket',
        ]);

        $article->tags()->attach([$target->id, $source->id]);

        app(TagMergeService::class)->mergeInto($target, collect([$source]));

        $article->refresh();

        $this->assertSame([$target->id], $article->tags()->pluck('tags.id')->all());
        $this->assertDatabaseMissing('tags', ['id' => $source->id]);
        $this->assertDatabaseCount('news_article_tag', 1);
    }

    public function test_layout_studio_preview_publish_and_restore_flow_updates_home_page(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        NewsArticle::query()->create([
            'title' => ['tr' => 'Layout Test Haberi'],
            'slug' => 'layout-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'editorial_score' => 80,
            'city_code' => 3,
            'city_slug' => 'adiyaman',
        ]);

        $service = app(LayoutConfigService::class);
        $baseState = $service->getDraftState();
        $firstState = $this->withAppearanceColor($baseState, '#123456');
        $service->storeDraftState($firstState['modules'], $firstState['appearance'], $admin);
        $firstPublished = $service->publishDraft($admin);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('#123456', false);

        $secondState = $this->withAppearanceColor($baseState, '#654321');
        $service->storeDraftState($secondState['modules'], $secondState['appearance'], $admin);

        $previewUrl = $service->getPreviewUrl($service->getDraftRevision(), 'tr');
        $this->get($previewUrl)
            ->assertOk()
            ->assertSee('#654321', false);

        $service->publishDraft($admin);
        $this->assertSame('#654321', data_get($service->getPublishedState(), 'appearance.primary_color'));
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('#654321', false);

        $archivedFirstRevision = LayoutRevision::query()
            ->whereKey($firstPublished->id)
            ->firstOrFail();

        $service->restoreRevisionToDraft($archivedFirstRevision, $admin);
        $restorePreviewUrl = $service->getPreviewUrl($service->getDraftRevision(), 'tr');

        $this->get($restorePreviewUrl)
            ->assertOk()
            ->assertSee('#123456', false);

        $service->publishDraft($admin);
        $this->assertSame('#123456', data_get($service->getPublishedState(), 'appearance.primary_color'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('#123456', false);
    }

    public function test_layout_draft_save_does_not_mutate_public_base_state_until_publish(): void
    {
        $admin = $this->makeAdmin();
        $service = app(LayoutConfigService::class);
        $baseState = $service->getDraftState();
        $draftState = $this->withAppearanceColor($baseState, '#abcdef');

        $service->storeDraftState($draftState['modules'], $draftState['appearance'], $admin);

        $this->assertSame('#abcdef', data_get($service->getDraftState(), 'appearance.primary_color'));
        $this->assertNotSame('#abcdef', Setting::get('appearance', 'primary_color', $service->defaultAppearance()['primary_color']));
        $this->assertNotSame('#abcdef', data_get($service->getPublishedState(), 'appearance.primary_color'));

        $service->publishDraft($admin);

        $this->assertSame('#abcdef', Setting::get('appearance', 'primary_color'));
        $this->assertSame('#abcdef', data_get($service->getPublishedState(), 'appearance.primary_color'));

        $nextDraftState = $this->withAppearanceColor($service->getDraftState(), '#fedcba');
        $service->storeDraftState($nextDraftState['modules'], $nextDraftState['appearance'], $admin);

        $this->assertSame('#fedcba', data_get($service->getDraftState(), 'appearance.primary_color'));
        $this->assertSame('#abcdef', Setting::get('appearance', 'primary_color'));
        $this->assertSame('#abcdef', data_get($service->getPublishedState(), 'appearance.primary_color'));
    }

    public function test_editor_can_save_layout_draft_but_cannot_publish_live_state(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('editor', 'web');

        $superAdmin = $this->makeAdmin();
        $superAdmin->assignRole('super_admin');

        $editor = User::factory()->create([
            'email' => 'editor@example.test',
            'is_active' => true,
        ]);
        $editor->assignRole('editor');

        $service = app(LayoutConfigService::class);
        $baseState = $service->getDraftState();
        $publishedState = $this->withAppearanceColor($baseState, '#112233');

        $service->storeDraftState($publishedState['modules'], $publishedState['appearance'], $superAdmin);
        $service->publishDraft($superAdmin);

        $this->actingAs($editor);

        Livewire::test(LayoutStudio::class)
            ->set('appearance.primary_color', '#445566')
            ->call('saveDraft')
            ->call('publishDraft');

        $this->assertSame('#445566', data_get($service->getDraftState(), 'appearance.primary_color'));
        $this->assertSame('#112233', Setting::get('appearance', 'primary_color'));
        $this->assertSame('#112233', data_get($service->getPublishedState(), 'appearance.primary_color'));
    }

    public function test_layout_publish_quality_gate_blocks_invalid_draft_state(): void
    {
        Role::findOrCreate('super_admin', 'web');

        $superAdmin = $this->makeAdmin();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        $service = app(LayoutConfigService::class);
        $baseState = $service->getDraftState();
        $publishedState = $this->withAppearanceColor($baseState, '#111111');

        $service->storeDraftState($publishedState['modules'], $publishedState['appearance'], $superAdmin);
        $service->publishDraft($superAdmin);

        $invalidState = $this->withAppearanceColor($service->getDraftState(), '#222222');
        $invalidState['modules'] = collect($invalidState['modules'])
            ->map(function (array $module): array {
                if (in_array($module['key'], ['hero', 'local_news', 'highlights', 'most_read', 'region_news', 'latest_news', 'category_shortcuts'], true)) {
                    $module['is_active'] = false;
                }

                return $module;
            })
            ->all();

        Livewire::test(LayoutStudio::class)
            ->set('modules', $invalidState['modules'])
            ->set('appearance.primary_color', '#222222')
            ->call('publishDraft');

        $this->assertSame('#111111', Setting::get('appearance', 'primary_color'));
        $this->assertSame('#111111', data_get($service->getPublishedState(), 'appearance.primary_color'));
    }

    public function test_layout_publish_quality_gate_blocks_cta_without_required_fields(): void
    {
        Role::findOrCreate('super_admin', 'web');

        $superAdmin = $this->makeAdmin();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        $service = app(LayoutConfigService::class);
        $baseState = $service->getDraftState();
        $publishedState = $this->withAppearanceColor($baseState, '#333333');

        $service->storeDraftState($publishedState['modules'], $publishedState['appearance'], $superAdmin);
        $service->publishDraft($superAdmin);

        $invalidState = $this->withAppearanceColor($service->getDraftState(), '#444444');
        $invalidState['modules'] = collect($invalidState['modules'])
            ->map(function (array $module): array {
                if ($module['key'] === 'latest_news') {
                    $module['is_active'] = true;
                    $module['settings']['cta_enabled'] = true;
                    $module['settings']['cta_label']['tr'] = '';
                    $module['settings']['cta_url'] = '';
                }

                return $module;
            })
            ->all();

        Livewire::test(LayoutStudio::class)
            ->set('modules', $invalidState['modules'])
            ->set('appearance.primary_color', '#444444')
            ->call('publishDraft');

        $this->assertSame('#333333', Setting::get('appearance', 'primary_color'));
        $this->assertSame('#333333', data_get($service->getPublishedState(), 'appearance.primary_color'));
    }

    public function test_layout_studio_renders_panel_preview_and_device_toggle(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/layout-studio')
            ->assertOk()
            ->assertSee('Canlı Önizleme ve Yayın Hazırlığı')
            ->assertSee('Yayına hazır mı?')
            ->assertSee('Mobil 390px');

        Livewire::test(LayoutStudio::class)
            ->assertSet('previewDevice', 'desktop')
            ->call('setPreviewDevice', 'mobile')
            ->assertSet('previewDevice', 'mobile')
            ->call('setPreviewDevice', 'desktop')
            ->assertSet('previewDevice', 'desktop');
    }

    public function test_layout_preview_requires_signed_url(): void
    {
        $admin = $this->makeAdmin();
        $service = app(LayoutConfigService::class);
        $draft = $service->getDraftRevision();

        $this->get(route('layout.preview.home', ['revision' => $draft->id]))
            ->assertForbidden();

        $this->get($service->getPreviewUrl($draft))
            ->assertOk()
            ->assertDontSee('adh_site_cookie_consent');
    }

    public function test_media_library_deletes_only_orphan_media(): void
    {
        $admin = $this->makeAdmin();
        $this->grantContentPermissions($admin);
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Medya Test Haberi'],
            'slug' => 'medya-test-haberi',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $attached = $this->makeMedia($article->id, 'attached.jpg');
        $orphan = $this->makeMedia(999999, 'orphan.jpg');

        $this->actingAs($admin);

        Livewire::test(MediaLibrary::class)
            ->call('deleteMedia', $attached->id);

        $this->assertDatabaseHas('media', ['id' => $attached->id]);

        Livewire::test(MediaLibrary::class)
            ->call('deleteMedia', $orphan->id);

        $this->assertDatabaseMissing('media', ['id' => $orphan->id]);
    }

    public function test_legacy_layout_manager_is_a_disabled_stub_without_mutation_methods(): void
    {
        $this->assertFalse(LayoutManager::shouldRegisterNavigation());

        foreach (['updateModuleOrder', 'toggleModule', 'saveSettings', 'saveDraft', 'publishDraft', 'restoreRevision'] as $method) {
            $this->assertFalse(method_exists(LayoutManager::class, $method), "Legacy LayoutManager must not expose {$method}().");
        }

        $this->actingAs($this->makeAdmin())
            ->get('/admin/layout-manager-legacy')
            ->assertOk()
            ->assertSee('Legacy Layout Manager devre dışı')
            ->assertDontSee('Symfony\\Component\\HttpKernel\\Exception');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin@admin.com',
            'is_active' => true,
        ]);
    }

    private function grantContentPermissions(User $admin): void
    {
        $permissions = [
            'view_any_category',
            'create_category',
            'view_any_tag',
            'create_tag',
            'view_any_page',
            'create_page',
            'view_any_local_info_entry',
            'create_local_info_entry',
            'view_any_advertisement',
            'create_advertisement',
            'view_any_news_article',
            'create_news_article',
            'page_MediaLibrary',
            'page_LayoutStudio',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $admin->givePermissionTo($permissions);
    }

    private function withAppearanceColor(array $state, string $hexColor): array
    {
        return [
            'modules' => $state['modules'],
            'appearance' => array_replace($state['appearance'], [
                'primary_color' => $hexColor,
            ]),
        ];
    }

    private function makeMedia(int $modelId, string $fileName): Media
    {
        return Media::query()->forceCreate([
            'model_type' => NewsArticle::class,
            'model_id' => $modelId,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'featured_image',
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);
    }
}
