<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AdminPrivileges;
use App\Support\DynamicMailConfig;
use Filament\Actions\Action;
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
                TextInput::make('smtp_host')
                    ->label('SMTP Host'),

                TextInput::make('smtp_port')
                    ->label('SMTP Port')
                    ->numeric(),

                TextInput::make('smtp_username')
                    ->label('Kullanıcı Adı'),

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
                    ]),
            ])->columns(2),

            Section::make('Gönderici Bilgileri')->schema([
                TextInput::make('from_name')
                    ->label('Gönderici Adı'),

                TextInput::make('from_email')
                    ->label('Gönderici E-posta')
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

        Notification::make()->success()->title('E-posta ayarları kaydedildi')->send();
    }

    public function sendTestEmail(): void
    {
        try {
            DynamicMailConfig::apply();
            Mail::raw('Bu bir test e-postasıdır.', function ($message) {
                $message->to(auth()->user()->email)
                    ->subject('Test E-postası');
            });
            Notification::make()->success()->title('Test e-postası gönderildi')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Hata: ' . $e->getMessage())->send();
        }
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test_email')
                ->label('Test E-postası Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->requiresConfirmation()
                ->action('sendTestEmail'),
        ];
    }
}
