<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\MediaLibrary;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class AdminMediaUploadHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_library_surfaces_usage_status_and_delete_state(): void
    {
        $admin = $this->admin();
        $article = $this->article();
        $attached = $this->makeMedia($article->id, 'attached.jpg');
        $orphan = $this->makeMedia(999999, 'orphan.jpg');

        $this->actingAs($admin)
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee($attached->file_name)
            ->assertSee($orphan->file_name)
            ->assertSee('Kullanımda')
            ->assertSee('Kullanılmıyor')
            ->assertSee('Silme kapalı')
            ->assertSee('Sil');
    }

    public function test_media_library_delete_method_requires_orphaned_media(): void
    {
        $admin = $this->admin();
        $article = $this->article();
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

    public function test_media_library_requires_configuration_access(): void
    {
        $writer = User::query()->create([
            'name' => 'Writer',
            'email' => 'writer@example.test',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
        $orphan = $this->makeMedia(999999, 'orphan.jpg');

        $this->actingAs($writer);

        $this->get('/admin/media-library')
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $orphan->id]);
    }

    public function test_admin_image_upload_fields_use_shared_safe_constraints(): void
    {
        foreach ([
            'app/Filament/Resources/NewsArticleResource.php',
            'app/Filament/Resources/AdvertisementResource.php',
            'app/Filament/Pages/GeneralSettings.php',
            'app/Filament/Pages/SeoSettings.php',
        ] as $path) {
            $content = file_get_contents(base_path($path));

            $this->assertStringContainsString('AdminImageUploads::acceptedMimeTypes()', $content, "{$path} must use shared MIME constraints.");
            $this->assertStringContainsString('AdminImageUploads::maxSizeKb()', $content, "{$path} must use shared size constraints.");
            $this->assertStringNotContainsString('application/pdf', $content, "{$path} must not allow PDF in public image upload fields.");
        }
    }

    public function test_admin_image_upload_mime_allowlist_stays_image_only(): void
    {
        $this->assertSame([
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ], \App\Support\AdminImageUploads::acceptedMimeTypes());

        $blockedMimeTypes = [
            'application/pdf',
            'image/svg+xml',
            'text/html',
            'application/x-php',
        ];

        foreach ($blockedMimeTypes as $mimeType) {
            $this->assertNotContains($mimeType, \App\Support\AdminImageUploads::acceptedMimeTypes());
        }
    }

    public function test_media_library_loads_media_in_batches(): void
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 30; $i++) {
            $this->makeMedia(999999 + $i, sprintf('batch-%02d.jpg', $i));
        }

        $this->actingAs($admin);

        $component = Livewire::test(MediaLibrary::class);

        $this->assertCount(24, $component->instance()->getMediaItems());
        $this->assertTrue($component->instance()->hasMoreMedia());

        $component->call('loadMoreMedia');

        $this->assertCount(30, $component->instance()->getMediaItems());
        $this->assertFalse($component->instance()->hasMoreMedia());
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

    private function article(): NewsArticle
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gündem'],
            'slug' => 'gundem',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return NewsArticle::query()->create([
            'title' => ['tr' => 'Medya Kullanım Test Haberi'],
            'slug' => 'medya-kullanim-test-haberi',
            'summary' => ['tr' => 'Özet'],
            'content' => ['tr' => 'Haber gövdesi'],
            'source' => 'manuel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
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
