<?php

namespace Tests\Feature\Filament;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_instagram_configuration_status_when_credentials_are_missing(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/integration-settings')
            ->assertOk()
            ->assertSee('Google Translation Durumu')
            ->assertSee('Eksik: Çeviri işleri kuyrukta bekler')
            ->assertSee('Instagram Durumu')
            ->assertSee('Eksik yapılandırma')
            ->assertSee('Business account ID');
    }

    public function test_admin_sees_instagram_ready_state_when_credentials_are_complete(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        Setting::set('integration', 'instagram_access_token', 'token-123');
        Setting::set('integration', 'instagram_business_account_id', 'acct-456');
        Setting::set('integration', 'instagram_enabled', true);
        Setting::set('integration', 'google_translate_api_key', 'google-key-123');

        $this->actingAs($admin)
            ->get('/admin/integration-settings')
            ->assertOk()
            ->assertSee('Hazır: Çeviri kuyruğu gerçek API ile işlenebilir.')
            ->assertSee('Instagram Durumu')
            ->assertSee('Hazır: Yeni yayınlanan haberler Instagram kuyruğuna alınabilir.');
    }
}
