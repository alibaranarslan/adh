<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\MediaLibrary;
use App\Filament\Resources\AdvertisementResource\Pages\ListAdvertisements;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\HeaderThemeResource\Pages\ListHeaderThemes;
use App\Filament\Resources\LocalInfoEntryResource\Pages\ListLocalInfoEntries;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Filament\Resources\TagResource\Pages\ListTags;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\HeaderTheme;
use App\Models\LocalInfoEntry;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSecondaryResourcesOperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_admin_pages_show_clean_turkish_operational_labels(): void
    {
        $admin = $this->superAdmin();

        $this->category(['name' => ['tr' => 'Gündem'], 'slug' => 'gundem']);
        Tag::query()->create(['name' => ['tr' => 'Yerel'], 'slug' => 'yerel']);
        Page::query()->create(['title' => ['tr' => 'Kurumsal'], 'slug' => 'kurumsal', 'content' => ['tr' => 'İçerik'], 'is_published' => true]);
        Advertisement::query()->create(['name' => 'Yerel Reklam', 'position' => 'header', 'type' => Advertisement::TYPE_BANNER, 'is_active' => true]);
        Advertisement::query()->create(['name' => 'AdSense Reklam', 'position' => 'footer', 'type' => Advertisement::TYPE_ADSENSE, 'adsense_slot' => '1234567890', 'is_active' => true]);
        LocalInfoEntry::query()->create(['type' => 'other', 'title' => 'Yerel Bilgi', 'content' => 'Detay', 'is_active' => true]);
        HeaderTheme::query()->create([
            'name' => ['tr' => 'Milli Gün'],
            'slug' => 'test-milli-gun',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => now()->month,
            'day' => now()->day,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'is_enabled' => true,
        ]);

        foreach ([
            '/admin/users' => ['Kullanıcılar', 'Son Giriş', 'Koruma'],
            '/admin/header-themes' => ['Milli Gün Temaları', 'Zamanlama', 'Hazırlık'],
            '/admin/tags' => ['Etiketler', 'Kullanım', 'Etiketleri Birleştir'],
            '/admin/categories' => ['Kategoriler', 'İHA Eşleşmesi', 'Sitede Gör'],
            '/admin/pages' => ['Sayfalar', 'SEO', 'Korumalı'],
            '/admin/media-library' => ['Medya Kütüphanesi', 'Kullanılmayanları göster', 'Tüm koleksiyonlar'],
            '/admin/advertisements' => ['Reklamlar', 'Yayın Durumu', 'AdSense Ayarları'],
            '/admin/local-info-entries' => ['Yerel Bilgiler', 'Şu an yayında', 'Pasifleştir'],
        ] as $path => $labels) {
            $response = $this->actingAs($admin)->get($path)->assertOk();

            foreach ($labels as $label) {
                $response->assertSee($label);
            }

            $response
                ->assertDontSee('TÃ')
                ->assertDontSee('YayÄ')
                ->assertDontSee('GÃ')
                ->assertDontSee('ManÅ');
        }
    }

    public function test_user_and_content_filters_limit_records_to_operational_scope(): void
    {
        $admin = $this->superAdmin();
        $writer = User::factory()->create(['is_active' => true]);
        $writer->assignRole('writer');

        $mappedCategory = $this->category([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'iha_category_code' => 1,
        ]);
        $unmappedCategory = $this->category([
            'name' => ['tr' => 'Spor'],
            'slug' => 'spor',
            'iha_category_code' => null,
        ]);

        $publishedPage = Page::query()->create([
            'title' => ['tr' => 'Yayındaki Sayfa'],
            'slug' => 'yayindaki-sayfa',
            'content' => ['tr' => 'İçerik'],
            'meta_title' => ['tr' => 'Meta'],
            'meta_description' => ['tr' => 'Açıklama'],
            'is_published' => true,
        ]);
        $draftPage = Page::query()->create([
            'title' => ['tr' => 'Taslak Sayfa'],
            'slug' => 'taslak-sayfa',
            'content' => ['tr' => 'İçerik'],
            'is_published' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('role', Role::findByName('writer')->id)
            ->assertCanSeeTableRecords([$writer])
            ->assertCanNotSeeTableRecords([$admin]);

        Livewire::test(ListCategories::class)
            ->filterTable('iha_mapping', 'mapped')
            ->assertCanSeeTableRecords([$mappedCategory])
            ->assertCanNotSeeTableRecords([$unmappedCategory])
            ->assertTableActionVisible('view_site', $mappedCategory);

        Livewire::test(ListPages::class)
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$publishedPage])
            ->assertCanNotSeeTableRecords([$draftPage])
            ->assertTableActionVisible('view_site', $publishedPage)
            ->assertTableActionHidden('view_site', $draftPage);
    }

    public function test_tag_merge_requires_target_outside_selected_sources(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();
        $target = Tag::query()->create(['name' => ['tr' => 'Hedef'], 'slug' => 'hedef']);
        $source = Tag::query()->create(['name' => ['tr' => 'Kaynak'], 'slug' => 'kaynak']);
        $article = $this->article($category);

        $article->tags()->attach([$source->id]);

        $this->actingAs($admin);

        Livewire::test(ListTags::class)
            ->callTableBulkAction('merge', [$target, $source], ['target_tag_id' => $target->id]);

        $this->assertDatabaseHas('tags', ['id' => $target->id]);
        $this->assertDatabaseHas('tags', ['id' => $source->id]);

        Livewire::test(ListTags::class)
            ->callTableBulkAction('merge', [$source], ['target_tag_id' => $target->id]);

        $this->assertDatabaseHas('tags', ['id' => $target->id]);
        $this->assertDatabaseMissing('tags', ['id' => $source->id]);
        $this->assertTrue($article->refresh()->tags()->whereKey($target->id)->exists());
    }

    public function test_theme_advertisement_and_local_info_status_filters_are_operational(): void
    {
        $admin = $this->superAdmin();

        $activeTheme = HeaderTheme::query()->create([
            'name' => ['tr' => 'Bugün Aktif'],
            'slug' => 'bugun-aktif',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => now()->month,
            'day' => now()->day,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'is_enabled' => true,
        ]);
        $futureTheme = HeaderTheme::query()->create([
            'name' => ['tr' => 'Gelecek Aralık'],
            'slug' => 'gelecek-aralik',
            'theme_type' => HeaderTheme::TYPE_RANGE,
            'starts_at' => now()->addDays(5)->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'is_enabled' => true,
        ]);
        $missingBanner = Advertisement::query()->create([
            'name' => 'Eksik Banner',
            'position' => 'header',
            'type' => Advertisement::TYPE_BANNER,
            'is_active' => true,
        ]);
        $passiveAd = Advertisement::query()->create([
            'name' => 'Pasif Reklam',
            'position' => 'footer',
            'type' => Advertisement::TYPE_BANNER,
            'is_active' => false,
        ]);
        $currentInfo = LocalInfoEntry::query()->create([
            'type' => 'road_status',
            'title' => 'Açık Yol',
            'content' => 'Detay',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
        $scheduledInfo = LocalInfoEntry::query()->create([
            'type' => 'water_outage',
            'title' => 'Planlı Kesinti',
            'content' => 'Detay',
            'starts_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListHeaderThemes::class)
            ->filterTable('schedule_scope', 'active_today')
            ->assertCanSeeTableRecords([$activeTheme])
            ->assertCanNotSeeTableRecords([$futureTheme])
            ->assertTableActionVisible('preview', $activeTheme);

        Livewire::test(ListAdvertisements::class)
            ->filterTable('render_status', 'missing_banner_image')
            ->assertCanSeeTableRecords([$missingBanner])
            ->assertCanNotSeeTableRecords([$passiveAd]);

        Livewire::test(ListLocalInfoEntries::class)
            ->filterTable('publication_status', 'current')
            ->assertCanSeeTableRecords([$currentInfo])
            ->assertCanNotSeeTableRecords([$scheduledInfo])
            ->assertTableActionVisible('deactivate', $currentInfo);
    }

    public function test_header_theme_preview_action_redirects_to_signed_public_preview(): void
    {
        $admin = $this->superAdmin();
        $theme = HeaderTheme::query()->create([
            'name' => ['tr' => '29 Ekim'],
            'slug' => '29-ekim',
            'theme_type' => HeaderTheme::TYPE_FIXED,
            'month' => 10,
            'day' => 29,
            'mode' => HeaderTheme::MODE_AUTOMATIC,
            'is_enabled' => true,
            'banner_message' => ['tr' => '29 Ekim Cumhuriyet Bayramı kutlu olsun.'],
            'show_flag' => true,
            'show_ataturk' => true,
            'decor_intensity' => 'strong',
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ListHeaderThemes::class)
            ->callTableAction('preview', $theme, data: [
                'locale' => 'tr',
                'preview_date' => '2026-10-29',
            ])
            ->assertRedirect();

        $redirectUrl = $component->effects['redirect'] ?? '';

        $this->assertNotSame('', $redirectUrl);
        $this->assertTrue(URL::hasValidSignature(request()->create($redirectUrl)));

        $this->get($redirectUrl)
            ->assertOk()
            ->assertSee('adh-theme-29-ekim', false)
            ->assertSee('adh-event-seal--republic', false)
            ->assertSee('29 Ekim Cumhuriyet Bayramı kutlu olsun.');
    }

    public function test_media_library_filters_by_collection_and_preserves_orphan_delete_guard(): void
    {
        $admin = $this->superAdmin();
        $article = $this->article($this->category());
        $attached = $this->media($article->id, 'attached.jpg', 'featured_image');
        $orphan = $this->media(999999, 'orphan-gallery.jpg', 'gallery');

        $this->actingAs($admin);

        $component = Livewire::test(MediaLibrary::class)
            ->set('collectionFilter', 'gallery');

        $this->assertSame(['orphan-gallery.jpg'], $component->instance()->getMediaItems()->pluck('file_name')->all());

        $component->call('deleteMedia', $attached->id);
        $this->assertDatabaseHas('media', ['id' => $attached->id]);

        $component->call('deleteMedia', $orphan->id);
        $this->assertDatabaseMissing('media', ['id' => $orphan->id]);
    }

    private function superAdmin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function category(array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem-' . Str::random(6),
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function article(Category $category): NewsArticle
    {
        return NewsArticle::query()->create([
            'title' => ['tr' => 'Operasyon Test Haberi'],
            'slug' => 'operasyon-test-haberi-' . Str::random(6),
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'İçerik'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function media(int $modelId, string $fileName, string $collection): Media
    {
        return Media::query()->forceCreate([
            'model_type' => NewsArticle::class,
            'model_id' => $modelId,
            'uuid' => (string) Str::uuid(),
            'collection_name' => $collection,
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
