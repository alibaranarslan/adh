<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AdvertisementResource\Pages\CreateAdvertisement;
use App\Filament\Resources\AdvertisementResource\Pages\EditAdvertisement;
use App\Models\Advertisement;
use App\Models\Setting;
use App\Models\User;
use App\Support\AdvertisementPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertisementResourceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_banner_advertisement(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateAdvertisement::class)
            ->fillForm([
                'name' => 'CRUD Test Reklamı',
                'position' => 'header',
                'type' => Advertisement::TYPE_BANNER,
                'link_url' => 'https://example.com/reklam',
                'is_active' => true,
                'sort_order' => 25,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $advertisement = Advertisement::query()
            ->where('name', 'CRUD Test Reklamı')
            ->firstOrFail();

        $this->assertSame('header', $advertisement->position);
        $this->assertSame(Advertisement::TYPE_BANNER, $advertisement->type);
        $this->assertNull($advertisement->desktop_image_path);
        $this->assertNull($advertisement->mobile_image_path);
        $this->assertSame('missing_banner_image', $advertisement->renderStatus());

        Livewire::test(EditAdvertisement::class, ['record' => $advertisement->getKey()])
            ->fillForm([
                'name' => 'CRUD Test Reklamı Güncellendi',
                'position' => 'article-top',
                'type' => Advertisement::TYPE_BANNER,
                'link_url' => 'https://example.com/reklam-guncel',
                'is_active' => true,
                'sort_order' => 30,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $advertisement->refresh();

        $this->assertSame('CRUD Test Reklamı Güncellendi', $advertisement->name);
        $this->assertSame('article-top', $advertisement->position);
        $this->assertSame('https://example.com/reklam-guncel', $advertisement->link_url);
        $this->assertSame(30, $advertisement->sort_order);

        Livewire::test(EditAdvertisement::class, ['record' => $advertisement->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('advertisements', [
            'id' => $advertisement->id,
        ]);
    }

    public function test_admin_adsense_ad_requires_slot_and_normalizes_incompatible_fields(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateAdvertisement::class)
            ->fillForm([
                'name' => 'Eksik Slot Adsense',
                'position' => 'header',
                'type' => Advertisement::TYPE_ADSENSE,
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->assertSee('Client ID eksik')
            ->call('create')
            ->assertHasFormErrors([
                'adsense_slot' => 'required',
            ]);

        Livewire::test(CreateAdvertisement::class)
            ->fillForm([
                'name' => 'Geçerli Adsense',
                'position' => 'header',
                'type' => Advertisement::TYPE_ADSENSE,
                'adsense_slot' => '1234567890',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $advertisement = Advertisement::query()
            ->where('name', 'Geçerli Adsense')
            ->firstOrFail();

        $this->assertSame(Advertisement::TYPE_ADSENSE, $advertisement->type);
        $this->assertSame('1234567890', $advertisement->adsense_slot);
        $this->assertNull($advertisement->image_path);
        $this->assertNull($advertisement->desktop_image_path);
        $this->assertNull($advertisement->mobile_image_path);
        $this->assertNull($advertisement->link_url);
        $this->assertSame('missing_adsense_client', $advertisement->renderStatus());

        Setting::query()->create([
            'group' => 'integration',
            'key' => 'adsense_client_id',
            'value' => 'ca-pub-test-client',
        ]);

        $this->assertSame('ready', $advertisement->refresh()->renderStatus('ca-pub-test-client'));

        Livewire::test(CreateAdvertisement::class)
            ->fillForm([
                'name' => 'Hazir Adsense',
                'position' => 'footer',
                'type' => Advertisement::TYPE_ADSENSE,
                'adsense_slot' => '1234567890',
                'is_active' => true,
                'sort_order' => 900,
            ])
            ->assertSee('Client ID hazır');
    }

    public function test_advertisement_slots_expose_operational_size_guidance(): void
    {
        $header = AdvertisementPlacement::placementMeta('header');
        $sidebar = AdvertisementPlacement::placementMeta('sidebar-top');

        $this->assertSame('180px', $header['max_height']);
        $this->assertSame('150px', $header['mobile_max_height']);
        $this->assertSame('8 / 1', $header['aspect_ratio']);
        $this->assertSame('360px', $sidebar['max_height']);
        $this->assertSame('1.25 / 1', $sidebar['aspect_ratio']);
        $this->assertStringContainsString('Desktop önerisi', AdvertisementPlacement::guidance('article-top'));
        $this->assertStringContainsString('Public sınır', AdvertisementPlacement::guidance('article-top'));
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
