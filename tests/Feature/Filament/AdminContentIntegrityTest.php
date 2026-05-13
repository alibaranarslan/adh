<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\HeaderThemeResource\Pages\CreateHeaderTheme;
use App\Filament\Resources\NewsletterSubscriptionResource;
use App\Filament\Resources\NewsArticleResource;
use App\Filament\Resources\NewsArticleResource\Pages\CreateNewsArticle;
use App\Filament\Resources\NewsArticleResource\Pages\EditNewsArticle;
use App\Filament\Resources\NewsArticleResource\Pages\ListNewsArticles;
use App\Models\Category;
use App\Models\HeaderTheme;
use App\Models\NewsArticle;
use App\Models\Tag;
use App\Models\User;
use App\Support\AdminImageUploads;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_iha_articles_are_not_changed_by_news_bulk_actions(): void
    {
        $admin = $this->admin();
        $category = $this->category('gundem');
        $newCategory = $this->category('asayis');
        $ihaArticle = $this->article($category, [
            'slug' => 'iha-bulk-korumasi',
            'source' => 'iha',
            'iha_id' => 'IHA-BULK-GUARD',
            'status' => 'draft',
            'is_featured' => false,
        ]);
        $manualArticle = $this->article($category, [
            'slug' => 'manuel-bulk-korumasi',
            'source' => 'manuel',
            'status' => 'draft',
            'is_featured' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListNewsArticles::class)
            ->callTableBulkAction('publish', [$ihaArticle, $manualArticle]);

        $this->assertSame('draft', $ihaArticle->refresh()->status);
        $this->assertSame('draft', $manualArticle->refresh()->status);

        Livewire::test(ListNewsArticles::class)
            ->callTableBulkAction('set_featured', [$ihaArticle]);

        $this->assertFalse($ihaArticle->refresh()->is_featured);

        Livewire::test(ListNewsArticles::class)
            ->callTableBulkAction('change_category', [$ihaArticle], ['category_id' => $newCategory->id]);

        $this->assertSame($category->id, $ihaArticle->refresh()->category_id);

        Livewire::test(ListNewsArticles::class)
            ->callTableBulkAction('delete', [$ihaArticle]);

        $this->assertNotSoftDeleted($ihaArticle);
    }

    public function test_iha_edit_page_hides_delete_action(): void
    {
        $admin = $this->admin();
        $category = $this->category('gundem');
        $ihaArticle = $this->article($category, [
            'slug' => 'iha-delete-korumasi',
            'source' => 'iha',
            'iha_id' => 'IHA-DELETE-GUARD',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditNewsArticle::class, ['record' => $ihaArticle->getKey()])
            ->assertActionHidden('delete');
    }

    public function test_iha_articles_cannot_be_deleted_or_force_deleted_by_resource_policy(): void
    {
        $admin = $this->admin();
        $category = $this->category('gundem');
        $ihaArticle = $this->article($category, [
            'slug' => 'iha-resource-delete-korumasi',
            'source' => 'iha',
            'iha_id' => 'IHA-RESOURCE-DELETE-GUARD',
        ]);
        $manualArticle = $this->article($category, [
            'slug' => 'manual-resource-delete-korumasi',
            'source' => 'manuel',
        ]);

        $this->actingAs($admin);

        $this->assertFalse(NewsArticleResource::canDelete($ihaArticle));
        $this->assertFalse(NewsArticleResource::canForceDelete($ihaArticle));
        $this->assertTrue(NewsArticleResource::canDelete($manualArticle));
        $this->assertTrue(NewsArticleResource::canForceDelete($manualArticle));
        $this->assertFalse(NewsArticleResource::canForceDeleteAny());
    }

    public function test_news_article_form_syncs_tags_relationship(): void
    {
        $admin = $this->admin();
        $category = $this->category('gundem');
        $tag = Tag::query()->create([
            'name' => ['tr' => 'Yerel Gündem'],
            'slug' => 'yerel-gundem',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateNewsArticle::class)
            ->fillForm([
                'title' => 'Tag Pivot Haberi',
                'slug' => 'tag-pivot-haberi',
                'summary' => 'Kısa özet',
                'content' => 'Haber içerik metni.',
                'source' => 'manuel',
                'category_id' => $category->id,
                'status' => 'published',
                'tags' => [$tag->id],
                'images_data' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = NewsArticle::query()->where('slug', 'tag-pivot-haberi')->firstOrFail();

        $this->assertDatabaseHas('news_article_tag', [
            'news_article_id' => $article->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_writer_cannot_publish_or_feature_news_through_bulk_actions(): void
    {
        $this->seed(RoleSeeder::class);
        $writer = User::factory()->create(['is_active' => true]);
        $writer->assignRole('writer');
        $category = $this->category('gundem');
        $article = $this->article($category, [
            'slug' => 'writer-bulk-yetki',
            'source' => 'manuel',
            'status' => 'draft',
            'is_breaking' => false,
            'is_featured' => false,
        ]);

        $this->actingAs($writer);

        Livewire::test(ListNewsArticles::class)
            ->assertTableBulkActionHidden('publish')
            ->assertTableBulkActionHidden('archive')
            ->assertTableBulkActionHidden('draft')
            ->assertTableBulkActionHidden('set_breaking')
            ->assertTableBulkActionHidden('unset_breaking')
            ->assertTableBulkActionHidden('set_featured')
            ->assertTableBulkActionHidden('unset_featured')
            ->assertTableBulkActionHidden('change_category');

        $this->assertSame('draft', $article->refresh()->status);
        $this->assertFalse($article->is_breaking);
        $this->assertFalse($article->is_featured);
    }

    public function test_writer_created_news_is_forced_to_draft_without_vitrine_flags(): void
    {
        $this->seed(RoleSeeder::class);
        $writer = User::factory()->create(['is_active' => true]);
        $writer->assignRole('writer');
        $category = $this->category('gundem');

        $this->actingAs($writer);

        Livewire::test(CreateNewsArticle::class)
            ->fillForm([
                'title' => 'Writer Yayina Zorlayamaz',
                'slug' => 'writer-yayina-zorlayamaz',
                'summary' => 'Writer status published gonderse de taslak kalmali.',
                'content' => 'Writer haber icerigi.',
                'source' => 'manuel',
                'category_id' => $category->id,
                'status' => 'published',
                'is_breaking' => true,
                'is_featured' => true,
                'images_data' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = NewsArticle::query()->where('slug', 'writer-yayina-zorlayamaz')->firstOrFail();

        $this->assertSame('draft', $article->status);
        $this->assertFalse($article->is_breaking);
        $this->assertFalse($article->is_featured);
    }

    public function test_header_theme_fixed_date_requires_month_and_day(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateHeaderTheme::class)
            ->fillForm([
                'name' => 'Eksik Sabit Tarih',
                'slug' => 'eksik-sabit-tarih',
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'theme_type' => HeaderTheme::TYPE_FIXED,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'month' => 'required',
                'day' => 'required',
            ]);
    }

    public function test_header_theme_nth_weekday_requires_schedule_parts(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateHeaderTheme::class)
            ->fillForm([
                'name' => 'Eksik Hafta Kuralı',
                'slug' => 'eksik-hafta-kurali',
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'theme_type' => HeaderTheme::TYPE_NTH_WEEKDAY,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'month' => 'required',
                'weekday' => 'required',
                'nth_week' => 'required',
            ]);
    }

    public function test_header_theme_range_requires_valid_date_order(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateHeaderTheme::class)
            ->fillForm([
                'name' => 'Ters Tarih Aralığı',
                'slug' => 'ters-tarih-araligi',
                'mode' => HeaderTheme::MODE_AUTOMATIC,
                'theme_type' => HeaderTheme::TYPE_RANGE,
                'starts_at' => '2026-05-10',
                'ends_at' => '2026-05-01',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'ends_at' => 'after_or_equal',
            ]);
    }

    public function test_public_image_upload_fields_do_not_allow_pdf_mime_type(): void
    {
        $paths = [
            'app/Filament/Resources/NewsArticleResource.php',
            'app/Filament/Resources/AdvertisementResource.php',
            'app/Filament/Pages/GeneralSettings.php',
            'app/Filament/Pages/SeoSettings.php',
        ];

        foreach ($paths as $path) {
            $this->assertStringNotContainsString(
                'application/pdf',
                file_get_contents(base_path($path)),
                "{$path} must not allow PDFs in image upload fields.",
            );
        }
    }

    public function test_news_table_uses_existing_local_placeholder_image(): void
    {
        $resource = file_get_contents(app_path('Filament/Resources/NewsArticleResource.php'));

        $this->assertStringContainsString("asset('images/news/placeholder-news.jpg')", $resource);
        $this->assertFileExists(public_path('images/news/placeholder-news.jpg'));
        $this->assertStringNotContainsString("asset('images/placeholder.png')", $resource);
    }

    public function test_admin_image_upload_file_names_use_verified_mime_extension_map(): void
    {
        $this->assertSame('jpg', AdminImageUploads::extensionForMimeType('image/jpeg'));
        $this->assertSame('png', AdminImageUploads::extensionForMimeType('image/png'));
        $this->assertSame('webp', AdminImageUploads::extensionForMimeType('image/webp'));
        $this->assertSame('gif', AdminImageUploads::extensionForMimeType('image/gif'));
        $this->assertStringNotContainsString(
            'getClientOriginalExtension',
            file_get_contents(app_path('Support/AdminImageUploads.php')),
        );

        $this->expectException(\InvalidArgumentException::class);
        AdminImageUploads::extensionForMimeType('application/x-php');
    }

    public function test_newsletter_csv_cells_are_escaped_against_formula_injection(): void
    {
        $this->assertSame("'=cmd|calc", NewsletterSubscriptionResource::escapeCsvCell('=cmd|calc'));
        $this->assertSame("'+441234", NewsletterSubscriptionResource::escapeCsvCell('+441234'));
        $this->assertSame("'-10", NewsletterSubscriptionResource::escapeCsvCell('-10'));
        $this->assertSame("'@SUM(A1:A2)", NewsletterSubscriptionResource::escapeCsvCell('@SUM(A1:A2)'));
        $this->assertSame('okur@example.test', NewsletterSubscriptionResource::escapeCsvCell('okur@example.test'));
    }

    private function admin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
    }

    private function category(string $slug): Category
    {
        return Category::query()->create([
            'name' => ['tr' => ucfirst($slug)],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function article(Category $category, array $overrides = []): NewsArticle
    {
        $slug = $overrides['slug'] ?? 'haber-'.str()->random(8);

        return NewsArticle::query()->create(array_merge([
            'title' => ['tr' => 'İçerik Bütünlüğü Test Haberi'],
            'slug' => $slug,
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'Haber içerik metni.'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'is_breaking' => false,
            'is_featured' => false,
            'published_at' => now(),
        ], $overrides));
    }
}
