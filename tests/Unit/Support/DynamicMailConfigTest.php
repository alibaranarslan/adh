<?php

namespace Tests\Unit\Support;

use App\Models\Setting;
use App\Support\DynamicMailConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicMailConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_database_backed_mail_configuration(): void
    {
        config([
            'mail.default' => 'log',
            'mail.mailers.smtp.host' => 'env-host',
            'mail.mailers.smtp.port' => 2525,
            'mail.from.address' => 'env@example.com',
            'mail.from.name' => 'Env Sender',
        ]);

        Setting::set('email', 'smtp_host', 'smtp.example.com');
        Setting::set('email', 'smtp_port', '587');
        Setting::set('email', 'smtp_username', 'mailer-user');
        Setting::set('email', 'smtp_password', 'mailer-pass');
        Setting::set('email', 'smtp_encryption', 'tls');
        Setting::set('email', 'from_name', 'Panel Sender');
        Setting::set('email', 'from_email', 'panel@example.com');

        DynamicMailConfig::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('mailer-user', config('mail.mailers.smtp.username'));
        $this->assertSame('panel@example.com', config('mail.from.address'));
        $this->assertSame('Panel Sender', config('mail.from.name'));
    }
}
