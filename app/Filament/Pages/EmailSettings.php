<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AdminPrivileges;
use App\Support\DynamicMailConfig;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class EmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'E-posta';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $title = 'E-posta Ayarları';
    protected static ?int $navigationSort = 13;
    protected static string $view = 'filament.pages.settings';

    public array $data = [];
    public string $testEmail = '';

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public function mount(): void
    {
        $this->data = [
            'smtp_host' => Setting::get('email', 'smtp_host', ''),
            'smtp_port' => Setting::get('email', 'smtp_port', '587'),
            'smtp_username' => Setting::get('email', 'smtp_username', ''),
            'smtp_password' => '',
            'smtp_encryption' => Setting::get('email', 'smtp_encryption', 'tls'),
            'from_name' => Setting::get('email', 'from_name', ''),
            'from_email' => Setting::get('email', 'from_email', ''),
        ];
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('SMTP Ayarları')->schema([
                Placeholder::make('smtp_readiness')
                    ->label('SMTP Hazırlık Durumu')
                    ->content(fn (): string => $this->smtpReadinessStatus())
                    ->columnSpanFull(),
                TextInput::make('smtp_host')
                    ->label('SMTP Host')
                    ->helperText('SMTP bağlantısının sunucu adresidir; boşsa mevcut mail konfigürasyonu fallback olarak kalabilir.'),

                TextInput::make('smtp_port')
                    ->label('SMTP Port')
                    ->numeric()
                    ->helperText('Genellikle TLS için 587, SSL için 465 kullanılır.'),

                TextInput::make('smtp_username')
                    ->label('Kullanıcı Adı')
                    ->helperText('SMTP hesabı kullanıcı adıdır. Gizli değer değildir ancak public yüzeyde gösterilmez.'),

                TextInput::make('smtp_password')
                    ->label('Şifre')
                    ->password()
                    ->revealable()
                    ->placeholder(fn (): string => filled(Setting::get('email', 'smtp_password', '')) ? 'Kayıtlı şifre korunuyor' : 'Şifre girilmedi')
                    ->helperText('Boş bırakırsanız mevcut SMTP şifresi korunur.'),

                Select::make('smtp_encryption')
                    ->label('Şifreleme')
                    ->options([
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                        'none' => 'Yok',
                    ])
                    ->helperText('Mail sağlayıcısının önerdiği şifreleme tipiyle aynı olmalıdır.'),
            ])->columns(2),

            Section::make('Gönderici Bilgileri')->schema([
                Placeholder::make('sender_status')
                    ->label('Gönderici Durumu')
                    ->content(fn (): string => $this->senderStatus())
                    ->columnSpanFull(),
                TextInput::make('from_name')
                    ->label('Gönderici Adı')
                    ->helperText('Alıcının gelen kutusunda görünen marka adıdır.'),

                TextInput::make('from_email')
                    ->label('Gönderici E-posta')
                    ->helperText('SPF/DKIM uyumlu alan adıyla aynı olmalıdır.')
                    ->email(),
            ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($key === 'smtp_password' && blank($value)) {
                continue;
            }

            Setting::set('email', $key, $value);
        }

        Notification::make()
            ->success()
            ->title('E-posta ayarları kaydedildi')
            ->body('SMTP ve gönderici ayarları güncellendi. Boş bırakılan SMTP şifresi varsa mevcut değer korundu.')
            ->send();
    }

    public function sendTestEmail(?string $recipient = null): void
    {
        $recipient = trim((string) ($recipient ?: $this->testEmail ?: auth()->user()?->email));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->danger()
                ->title('Geçerli bir test alıcısı gerekli')
                ->body('Test e-postası göndermek için geçerli bir e-posta adresi girin veya oturum kullanıcısında geçerli e-posta olduğundan emin olun.')
                ->send();

            return;
        }

        try {
            DynamicMailConfig::apply();
            Mail::raw('Bu bir test e-postasıdır.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Test E-postası');
            });
            Notification::make()
                ->success()
                ->title('Test e-postası gönderildi')
                ->body($recipient . ' adresine test mesajı gönderildi.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Hata: ' . $e->getMessage())->send();
        }
    }

    public function settingsSummary(): string
    {
        return 'SMTP ve gönderici ayarları sistem e-postalarını etkiler. Test gönderimi yalnız açık onayla yapılır.';
    }

    /**
     * @return array<int, string>
     */
    public function settingsImpactBadges(): array
    {
        return ['Operasyonel e-posta', 'Gizli bilgi var', 'Test onayla çalışır'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test_email')
                ->label('Test E-postası Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->form([
                    TextInput::make('test_email')
                        ->label('Test Alıcısı')
                        ->email()
                        ->placeholder(fn (): string => auth()->user()?->email ?? 'ornek@domain.com')
                        ->helperText('Boş bırakılırsa oturumdaki kullanıcının e-posta adresi kullanılır. Bu değer kaydedilmez.'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Test e-postası gönder')
                ->modalDescription('Bu işlem gerçek SMTP yapılandırmasıyla tek bir test e-postası gönderir. Alıcıyı kontrol edin.')
                ->modalSubmitActionLabel('Test Gönder')
                ->action(fn (array $data): null => $this->sendTestEmail($data['test_email'] ?? null)),
        ];
    }

    private function smtpReadinessStatus(): string
    {
        $missing = [];

        foreach ([
            'smtp_host' => 'host',
            'smtp_port' => 'port',
            'smtp_username' => 'kullanıcı adı',
        ] as $field => $label) {
            if (blank($this->data[$field] ?? null)) {
                $missing[] = $label;
            }
        }

        if (! $this->smtpPasswordConfigured()) {
            $missing[] = 'şifre';
        }

        return $missing === []
            ? 'Hazır: SMTP bağlantısı için temel alanlar tanımlı.'
            : 'Eksik: ' . implode(', ', $missing) . '. Test göndermeden önce sağlayıcı ayarlarını tamamlayın.';
    }

    private function senderStatus(): string
    {
        if (filled($this->data['from_name'] ?? null) && filled($this->data['from_email'] ?? null)) {
            return 'Hazır: gönderici adı ve e-posta adresi tanımlı.';
        }

        return 'Uyarı: gönderici adı veya e-posta adresi eksik. Alıcı gelen kutusunda marka bilgisi zayıf görünebilir.';
    }

    private function smtpPasswordConfigured(): bool
    {
        return filled($this->data['smtp_password'] ?? null) || filled(Setting::get('email', 'smtp_password', ''));
    }
}
