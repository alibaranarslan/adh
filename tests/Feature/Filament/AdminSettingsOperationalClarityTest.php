<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\EmailSettings;
use App\Filament\Pages\LayoutStudio;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSettingsOperationalClarityTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_pages_render_operational_clarity_without_mojibake(): void
    {
        $admin = $this->userWithRole('super_admin');

        foreach ([
            '/admin/layout-studio' => ['Yerleşim Stüdyosu', 'Yayın Yetkisi', 'Kalite kapısı'],
            '/admin/general-settings' => ['Genel Ayarlar', 'Public header/footer', 'Form Alıcı Durumu'],
            '/admin/seo-settings' => ['SEO Ayarları', 'AI Crawl Özeti', 'Sitemap Durumu'],
            '/admin/integration-settings' => ['Entegrasyon Ayarları', 'Dış test çağrısı yok', 'İHA Hazırlık Durumu'],
            '/admin/email-settings' => ['E-posta Ayarları', 'Test onayla çalışır', 'SMTP Hazırlık Durumu'],
        ] as $path => $labels) {
            $response = $this->actingAs($admin)->get($path)->assertOk();

            foreach ($labels as $label) {
                $response->assertSee($label);
            }

            $this->assertDoesNotMatchRegularExpression('/(?:Ãƒ|Ã„|Ã…|Ã¢â‚¬|Ã‚)/u', $response->getContent() ?: '', "{$path} renders mojibake.");
        }
    }

    public function test_layout_studio_keeps_editor_draft_and_super_admin_publish_signals_separate(): void
    {
        $editor = $this->userWithRole('editor');
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($editor)
            ->get('/admin/layout-studio')
            ->assertOk()
            ->assertSee('Yalnız taslak düzenleme')
            ->assertSee('Önizleme güncel taslakla çalışır', false);

        $this->actingAs($superAdmin)
            ->get('/admin/layout-studio')
            ->assertOk()
            ->assertSee('Canlıya alma yetkisi var')
            ->assertSee('Kalite kapısı hazır');

        $this->actingAs($editor);

        Livewire::test(LayoutStudio::class)
            ->set('appearance.primary_color', '#445566')
            ->call('saveDraft')
            ->call('publishDraft');

        $this->assertSame('#445566', data_get(app(\App\Services\LayoutConfigService::class)->getDraftState(), 'appearance.primary_color'));
        $this->assertNotSame('#445566', Setting::get('appearance', 'primary_color'));
    }

    public function test_email_test_action_uses_explicit_recipient_without_persisting_it(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin);

        $capturedRecipient = null;
        Mail::shouldReceive('raw')
            ->once()
            ->with(
                'Bu bir test e-postasıdır.',
                \Mockery::on(function (callable $callback) use (&$capturedRecipient): bool {
                    $message = new class {
                        public ?string $recipient = null;
                        public ?string $subject = null;

                        public function to(string $recipient): self
                        {
                            $this->recipient = $recipient;

                            return $this;
                        }

                        public function subject(string $subject): self
                        {
                            $this->subject = $subject;

                            return $this;
                        }
                    };

                    $callback($message);
                    $capturedRecipient = $message->recipient;

                    return $message->subject === 'Test E-postası';
                })
            );

        Livewire::test(EmailSettings::class)
            ->callAction('send_test_email', data: [
                'test_email' => 'ops@adh.test',
            ]);

        $this->assertSame('ops@adh.test', $capturedRecipient);
        $this->assertNull(Setting::get('email', 'test_email'));
    }

    public function test_secret_settings_remain_write_only_on_operational_pages(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin);

        Setting::set('email', 'smtp_password', 'existing-smtp-pass');

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
            ->assertHasNoFormErrors()
            ->assertSee('Kaydetmeden önce public etki taşıyan alanları ve gizli bilgi notlarını kontrol edin.');

        $this->assertSame('existing-smtp-pass', Setting::get('email', 'smtp_password'));
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
