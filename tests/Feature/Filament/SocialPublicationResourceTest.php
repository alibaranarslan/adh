<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SocialPublicationResource;
use App\Filament\Resources\SocialPublicationResource\Pages\ListSocialPublications;
use App\Jobs\PublishToInstagramJob;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\SocialPublication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class SocialPublicationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_instagram_publication_list(): void
    {
        $this->actingAs($this->admin());
        $publication = $this->publication(SocialPublication::STATUS_FAILED);

        $this->get(SocialPublicationResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Instagram Paylaşımları')
            ->assertSee($publication->article->getTranslation('title', 'tr'));
    }

    public function test_admin_can_retry_failed_publication(): void
    {
        Queue::fake();
        $this->actingAs($this->admin());
        $publication = $this->publication(SocialPublication::STATUS_FAILED);

        Livewire::test(ListSocialPublications::class)
            ->callTableAction('retry', $publication);

        $publication->refresh();
        $this->assertSame(SocialPublication::STATUS_PENDING, $publication->status);
        $this->assertNull($publication->error_message);
        Queue::assertPushed(PublishToInstagramJob::class);
    }

    private function publication(string $status): SocialPublication
    {
        $category = Category::query()->create([
            'name' => ['tr' => 'Gundem'],
            'slug' => 'gundem',
            'is_active' => true,
        ]);

        $article = NewsArticle::query()->create([
            'title' => ['tr' => 'Instagram Admin Test'],
            'slug' => 'instagram-admin-test',
            'summary' => ['tr' => 'Ozet'],
            'content' => ['tr' => 'Icerik'],
            'source' => 'iha',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return SocialPublication::query()->updateOrCreate(
            [
                'news_article_id' => $article->id,
                'platform' => SocialPublication::PLATFORM_INSTAGRAM,
            ],
            [
                'status' => $status,
                'error_message' => $status === SocialPublication::STATUS_FAILED ? 'Previous error' : null,
            ]
        )->load('article');
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
}
