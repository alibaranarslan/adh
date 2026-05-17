<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\IhaApiService;
use App\Services\IhaTranslationRequeueService;
use App\Services\InstagramService;
use App\Support\AdminPrivileges;
use App\Support\TranslationSettings;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class IntegrationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Entegrasyonlar';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $title = 'Entegrasyon Ayarları';
    protected static ?int $navigationSort = 12;
    protected static string $view = 'filament.pages.settings';

    public array $data = [];

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public function mount(): void
    {
        $this->data = [
            'iha_user_code' => Setting::get('integration', 'iha_user_code', ''),
            'iha_username' => Setting::get('integration', 'iha_username', ''),
            'iha_password' => '',
            'google_translate_api_key' => '',
            'instagram_enabled' => filter_var(
                Setting::get('integration', 'instagram_enabled', config('services.instagram.enabled', false)),
                FILTER_VALIDATE_BOOL
            ),
            'instagram_access_token' => '',
            'instagram_business_account_id' => Setting::get('integration', 'instagram_business_account_id', ''),
            'adsense_client_id' => Setting::get('integration', 'adsense_client_id', ''),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('İHA API')
                ->description('Bu alanlar public sitede görünmez; yalnız senkron ve içerik operasyonlarını besler.')
                ->schema([
                    TextInput::make('iha_user_code')
                        ->label('Kullanıcı Kodu')
                        ->helperText('Sadece entegrasyon kimliği için kullanılır.'),
                    TextInput::make('iha_username')
                        ->label('Kullanıcı Adı')
                        ->helperText('İHA XML akışına erişim için kullanılır.'),
                    TextInput::make('iha_password')
                        ->label('Şifre')
                        ->password()
                        ->revealable()
                        ->placeholder(fn (): string => $this->secretConfigured('integration', 'iha_password', config('services.iha.password')) ? 'Kayıtlı şifre korunuyor' : 'Şifre girilmedi')
                        ->helperText('Boş bırakırsanız mevcut şifre korunur; yeni değer yalnız kaydetme sırasında yazılır.'),
                    Placeholder::make('iha_sync_interval_notice')
                        ->label('Efektif Senkron Aralığı')
                        ->content('İHA senkronu operasyonel olarak her 15 dakikada bir cron ile çalışır. Güncel durum için "İHA Sağlığı" ekranını kullanın.'),
                ])->columns(2),

            Section::make('Google Çeviri')
                ->description('İngilizce ve Kürtçe haber çevirileri için Google Cloud Translation API anahtarı burada yönetilir.')
                ->schema([
                    Placeholder::make('google_translate_status')
                        ->label('Google Translation Durumu')
                        ->content(fn (): string => TranslationSettings::ready()
                            ? 'Hazır: Çeviri kuyruğu gerçek API ile işlenebilir.'
                            : 'Eksik: Çeviri işleri kuyrukta bekler, haberler Türkçe fallback ile görüntülenir.'),
                    TextInput::make('google_translate_api_key')
                        ->label('Google Translation API Key')
                        ->password()
                        ->revealable()
                        ->placeholder(fn (): string => $this->secretConfigured('integration', 'google_translate_api_key', config('services.google_translate.api_key')) ? 'Kayıtlı API key korunuyor' : 'API key girilmedi')
                        ->helperText('Boş bırakırsanız mevcut API key korunur. Key Google Cloud Console içinde yalnız Cloud Translation API ile sınırlandırılmalıdır.'),
                    Placeholder::make('google_translate_flow')
                        ->label('Çeviri Akışı')
                        ->content('API key kaydedildiğinde eksik haber çevirileri otomatik olarak kuyruğa alınır. Ayrıca "İHA Sağlığı" ekranındaki "Çeviri Sürecini Başlat" aksiyonu aynı süreci panelden yeniden tetikler. Sunucuda çalışan queue worker işleri arka planda tamamlar.')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Diğer Entegrasyonlar')
                ->description('Bu ayarlar işlem akışlarını etkiler; public header veya footerda doğrudan görünmez.')
                ->schema([
                    Placeholder::make('instagram_status')
                        ->label('Instagram Durumu')
                        ->content(fn (): string => $this->instagramStatus()['configured']
                            ? 'Hazır: Yeni yayınlanan haberler Instagram kuyruğuna alınabilir.'
                            : 'Eksik yapılandırma: Otomatik Instagram paylaşımı şu anda devreye giremez.'),
                    Placeholder::make('instagram_missing')
                        ->label('Eksik Alanlar')
                        ->content(fn (): string => empty($this->instagramStatus()['missing'])
                            ? 'Eksik alan yok.'
                            : implode(', ', $this->instagramStatus()['missing']))
                        ->columnSpan(1),
                    Placeholder::make('instagram_flow')
                        ->label('Paylaşım Akışı')
                        ->content('Yayınlanan yeni haberler 2 dakika gecikmeyle Instagram kuyruğuna alınır. Haber görseli varsa başlık, özet, link ve hashtag formatında caption üretilir.')
                        ->columnSpanFull(),
                    Toggle::make('instagram_enabled')
                        ->label('Instagram otomatik paylaşım aktif')
                        ->helperText('Aktif olduğunda yayınlanan İHA ve manuel haberler Instagram kuyruğuna alınır.'),
                    TextInput::make('instagram_access_token')
                        ->label('Instagram Access Token')
                        ->password()
                        ->revealable()
                        ->placeholder(fn (): string => $this->secretConfigured('integration', 'instagram_access_token') ? 'Kayıtlı token korunuyor' : 'Token girilmedi')
                        ->helperText('Otomatik paylaşım akışının çalışması için gereklidir.'),
                    TextInput::make('instagram_business_account_id')
                        ->label('Instagram Business Account ID')
                        ->helperText('Instagram Graph API paylaşımlarının çalışması için zorunludur.'),
                    TextInput::make('adsense_client_id')
                        ->label('AdSense Client ID')
                        ->placeholder('ca-pub-XXXXXXXXXXXXXXXX')
                        ->helperText('Google AdSense scriptinde public istemci kimliği olarak kullanılır. Yalnız müşterinin AdSense hesabındaki ca-pub... değeri girilmelidir.'),
                ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $writeOnlySecrets = ['iha_password', 'google_translate_api_key', 'instagram_access_token'];

        foreach ($data as $key => $value) {
            if (in_array($key, $writeOnlySecrets, true) && blank($value)) {
                continue;
            }

            Setting::set('integration', $key, $value);
        }

        $translationResult = null;
        if (TranslationSettings::ready()) {
            $translationResult = app(IhaTranslationRequeueService::class)->requeueMissingTranslations();
        }

        IhaApiService::bumpFeedCacheVersion();
        Cache::forget('site_settings');
        Cache::forget('iha.category.local');
        Cache::forget('iha.category.region');
        Cache::forget('iha.category.default');
        Cache::forget('iha.category_map');
        Cache::forget('iha.health.translation_backlog');

        Artisan::call('config:clear');

        $notification = Notification::make()
            ->success()
            ->title('Entegrasyon ayarları kaydedildi');

        if ($translationResult !== null) {
            $notification->body(
                $translationResult['queued'] > 0
                    ? "{$translationResult['queued']} eksik haber çevirisi kuyruğa alındı; {$translationResult['skipped_duplicates']} tekrar kayıt atlandı."
                    : 'Google çeviri hazır. Eksik çeviri kuyruğu zaten güncel görünüyor.'
            );
        }

        $notification->send();
    }

    private function instagramStatus(): array
    {
        return app(InstagramService::class)->configurationStatus();
    }

    private function secretConfigured(string $group, string $key, ?string $fallback = null): bool
    {
        return filled(Setting::get($group, $key, $fallback ?? ''));
    }
}
