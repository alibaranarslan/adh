<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\EmailSettings;
use App\Filament\Pages\GeneralSettings;
use App\Filament\Pages\IhaHealth;
use App\Filament\Pages\IntegrationSettings;
use App\Filament\Pages\SeoSettings;
use App\Filament\Widgets\SystemAlertsWidget;
use App\Mail\ContactFormSubmitted;
use App\Models\IhaSyncLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOperationsReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_iha_health_uses_config_credentials_when_settings_are_empty(): void
    {
        config([
            'services.iha.user_code' => 'ENV-CODE',
            'services.iha.username' => 'ENV-USER',
            'services.iha.password' => 'ENV-PASS',
        ]);

        $stats = app(IhaHealth::class)->getViewData()['stats'];

        $this->assertTrue($stats['iha_credentials_ready']);
    }

    public function test_contact_form_sends_to_configured_recipient_email(): void
    {
        Mail::fake();

        Setting::set('general', 'contact_recipient_email', 'forms@adh.test');

        $payload = [
            'name' => 'Okur Test',
            'email' => 'okur@example.com',
            'subject' => 'Haber Ihbari',
            'message' => 'Bu bir test mesajidir.',
        ];

        $this->post(route('contact.submit'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(ContactFormSubmitted::class, function (ContactFormSubmitted $mail) use ($payload): bool {
            return $mail->hasTo('forms@adh.test')
                && $mail->submission['email'] === $payload['email']
                && $mail->submission['message'] === $payload['message'];
        });
    }

    public function test_system_alerts_surface_missing_and_stale_iha_evidence(): void
    {
        $alerts = app(SystemAlertsWidget::class)->getViewData()['alerts'];

        $this->assertSame('no_log', $alerts[0]['state']);

        IhaSyncLog::query()->create([
            'status' => 'running',
            'started_at' => now()->subHours(3),
        ]);

        $alerts = app(SystemAlertsWidget::class)->getViewData()['alerts'];

        $this->assertContains('running_stale', array_column($alerts, 'state'));
        $this->assertContains('no_success', array_column($alerts, 'state'));
    }

    public function test_system_alerts_surface_failed_and_success_lag_states(): void
    {
        IhaSyncLog::query()->create([
            'status' => 'success',
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
        ]);

        IhaSyncLog::query()->create([
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
            'error_message' => 'Feed error',
        ]);

        $alerts = app(SystemAlertsWidget::class)->getViewData()['alerts'];

        $this->assertContains('last_failed', array_column($alerts, 'state'));
        $this->assertContains('last_success_lag', array_column($alerts, 'state'));
    }

    public function test_general_settings_save_persists_and_remounts_contact_recipient(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'site_name' => ['tr' => 'ADH', 'en' => 'ADH', 'ku' => 'ADH'],
                'site_tagline' => ['tr' => 'Yerel haber', 'en' => '', 'ku' => ''],
                'logo_path' => null,
                'dark_logo_path' => null,
                'favicon_path' => null,
                'contact_email' => 'footer@adh.test',
                'contact_recipient_email' => 'forms@adh.test',
                'contact_phone' => '+90 416 000 00 00',
                'address' => ['tr' => 'Adiyaman', 'en' => '', 'ku' => ''],
                'social_links' => [],
                'archive_active_days' => 45,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('forms@adh.test', Setting::get('general', 'contact_recipient_email'));
        $this->assertSame('45', Setting::get('general', 'archive_active_days'));

        Livewire::test(GeneralSettings::class)
            ->assertFormSet([
                'contact_recipient_email' => 'forms@adh.test',
                'archive_active_days' => 45,
            ]);
    }

    public function test_seo_integration_and_email_settings_persist_and_remount(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(SeoSettings::class)
            ->fillForm([
                'default_meta_title' => '{title} - ADH',
                'default_meta_description' => 'Adiyaman haberleri',
                'og_image' => null,
                'robots_txt' => "User-agent: *\nAllow: /",
                'google_search_console_code' => 'gsc-code',
                'google_analytics_id' => 'G-TEST123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(IntegrationSettings::class)
            ->fillForm([
                'iha_user_code' => 'IHA-CODE',
                'iha_username' => 'IHA-USER',
                'iha_password' => 'IHA-PASS',
                'instagram_access_token' => 'IG-TOKEN',
                'instagram_business_account_id' => 'IG-BUSINESS',
                'adsense_client_id' => 'ca-pub-test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EmailSettings::class)
            ->fillForm([
                'smtp_host' => 'smtp.adh.test',
                'smtp_port' => '587',
                'smtp_username' => 'smtp-user',
                'smtp_password' => 'smtp-pass',
                'smtp_encryption' => 'tls',
                'from_name' => 'ADH Haber',
                'from_email' => 'noreply@adh.test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('{title} - ADH', Setting::get('seo', 'default_meta_title'));
        $this->assertSame('IHA-CODE', Setting::get('integration', 'iha_user_code'));
        $this->assertSame('IHA-PASS', Setting::get('integration', 'iha_password'));
        $this->assertSame('IG-TOKEN', Setting::get('integration', 'instagram_access_token'));
        $this->assertSame('smtp-pass', Setting::get('email', 'smtp_password'));
        $this->assertSame('noreply@adh.test', Setting::get('email', 'from_email'));

        Livewire::test(SeoSettings::class)
            ->assertFormSet(['default_meta_title' => '{title} - ADH']);
        Livewire::test(IntegrationSettings::class)
            ->assertFormSet([
                'iha_user_code' => 'IHA-CODE',
                'iha_password' => '',
                'instagram_access_token' => '',
            ])
            ->assertSee('Google AdSense scriptinde public istemci kimliği olarak kullanılır');
        Livewire::test(EmailSettings::class)
            ->assertFormSet([
                'from_email' => 'noreply@adh.test',
                'smtp_password' => '',
            ]);
    }

    public function test_write_only_secret_settings_are_preserved_when_blank_on_save(): void
    {
        $this->actingAs($this->admin());

        Setting::set('integration', 'iha_password', 'existing-iha-pass');
        Setting::set('integration', 'instagram_access_token', 'existing-ig-token');
        Setting::set('email', 'smtp_password', 'existing-smtp-pass');

        Livewire::test(IntegrationSettings::class)
            ->fillForm([
                'iha_user_code' => 'IHA-CODE',
                'iha_username' => 'IHA-USER',
                'iha_password' => '',
                'instagram_access_token' => '',
                'instagram_business_account_id' => 'IG-BUSINESS',
                'adsense_client_id' => 'ca-pub-test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EmailSettings::class)
            ->fillForm([
                'smtp_host' => 'smtp.adh.test',
                'smtp_port' => '587',
                'smtp_username' => 'smtp-user',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'from_name' => 'ADH Haber',
                'from_email' => 'noreply@adh.test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('existing-iha-pass', Setting::get('integration', 'iha_password'));
        $this->assertSame('existing-ig-token', Setting::get('integration', 'instagram_access_token'));
        $this->assertSame('existing-smtp-pass', Setting::get('email', 'smtp_password'));
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
