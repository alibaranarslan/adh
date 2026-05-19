<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PageResource;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use App\Support\PagePublicUrl;
use Database\Seeders\CustomerContentSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminStaticPageReflectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_static_pages_are_publicly_reachable_from_fixed_and_show_routes(): void
    {
        $this->seed(CustomerContentSeeder::class);

        foreach ([
            '/hakkimizda',
            '/iletisim',
            '/gizlilik-politikasi',
            '/kvkk',
            '/cerez-politikasi',
            '/sayfa/hakkimizda',
            '/sayfa/iletisim',
            '/sayfa/gizlilik-politikasi',
            '/sayfa/kvkk-aydinlatma',
            '/sayfa/cerez-politikasi',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_admin_static_page_edit_reflects_publicly_and_keeps_route_invariants(): void
    {
        $this->seed(CustomerContentSeeder::class);
        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();
        $content = '<p>Static page reflection proof content.</p>';

        $this->get('/hakkimizda')
            ->assertOk()
            ->assertDontSee('Static page reflection proof content', false);

        $this->actingAs($this->superAdmin());

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'title' => 'About Static Page',
                'slug' => 'broken-about-slug',
                'content' => $content,
                'is_published' => false,
                'sort_order' => 1,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertSame('hakkimizda', $page->slug);
        $this->assertTrue($page->is_published);
        $this->assertStringContainsString('Static page reflection proof content', $page->content);

        auth()->logout();

        $this->get('/hakkimizda')
            ->assertOk()
            ->assertSee('Static page reflection proof content', false);

        $this->get('/sayfa/hakkimizda')
            ->assertOk()
            ->assertSee('Static page reflection proof content', false);
    }

    public function test_admin_contact_page_edit_reflects_canonical_contact_route(): void
    {
        $this->seed(CustomerContentSeeder::class);
        $page = Page::query()->where('slug', 'iletisim')->firstOrFail();
        $content = '<p>Canonical contact page reflection proof.</p>';

        $this->actingAs($this->superAdmin());

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm([
                'title' => 'Canonical Contact',
                'slug' => 'broken-contact-slug',
                'content' => $content,
                'is_published' => false,
                'sort_order' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertSame('iletisim', $page->slug);
        $this->assertTrue($page->is_published);
        $this->assertSame('/iletisim', PagePublicUrl::path($page));
        $this->assertSame('/kvkk', PagePublicUrl::pathForSlug('kvkk-aydinlatma'));
        $this->assertSame('/sayfa/custom-page', PagePublicUrl::pathForSlug('custom-page'));

        auth()->logout();

        $this->get('/iletisim')
            ->assertOk()
            ->assertSee('Canonical contact page reflection proof', false);
    }

    public function test_protected_static_pages_cannot_be_deleted_from_admin(): void
    {
        $this->seed(CustomerContentSeeder::class);
        $page = Page::query()->where('slug', 'gizlilik-politikasi')->firstOrFail();

        $this->actingAs($this->superAdmin());

        $this->assertFalse(PageResource::canDelete($page));

        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->assertActionHidden('delete');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'slug' => 'gizlilik-politikasi',
        ]);
    }

    public function test_created_admin_page_is_reflected_on_public_show_route(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Public Reflection Page',
                'slug' => 'public-reflection-page',
                'content' => '<p>New admin page public body.</p>',
                'is_published' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get('/sayfa/public-reflection-page')
            ->assertOk()
            ->assertSee('New admin page public body', false);
    }

    private function superAdmin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');

        return $user;
    }
}
