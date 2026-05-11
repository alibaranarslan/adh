<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Schema;

class DynamicMailConfig
{
    public static function apply(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $host = Setting::get('email', 'smtp_host', config('mail.mailers.smtp.host'));
        $port = Setting::get('email', 'smtp_port', config('mail.mailers.smtp.port'));
        $username = Setting::get('email', 'smtp_username', config('mail.mailers.smtp.username'));
        $password = Setting::get('email', 'smtp_password', config('mail.mailers.smtp.password'));
        $encryption = Setting::get('email', 'smtp_encryption', config('mail.mailers.smtp.encryption'));
        $fromName = Setting::get('email', 'from_name', config('mail.from.name'));
        $fromAddress = Setting::get('email', 'from_email', config('mail.from.address'));

        if ($encryption === 'none') {
            $encryption = null;
        }

        $config = [
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ];

        if (filled($host) || filled($username) || filled($password)) {
            $config = array_merge($config, [
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => is_numeric($port) ? (int) $port : $port,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.mailers.smtp.encryption' => $encryption,
            ]);
        }

        config($config);

        try {
            app(MailManager::class)->purge();
        } catch (\Throwable) {
        }
    }
}
