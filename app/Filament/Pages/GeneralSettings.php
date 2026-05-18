<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AdminImageUploads;
use App\Support\AdminPrivileges;
use App\Support\LocalizedSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Genel';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $title = 'Genel Ayarlar';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.settings';

    public array $data = [];

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public function mount(): void
    {
        $this->data = [
            'site_name' => LocalizedSettings::decodeText(Setting::get('general', 'site_name', '')),
            'site_tagline' => LocalizedSettings::decodeText(Setting::get('general', 'site_tagline', '')),
            'logo_path' => Setting::get('general', 'logo_path', ''),
            'dark_logo_path' => Setting::get('general', 'dark_logo_path', ''),
            'favicon_path' => Setting::get('general', 'favicon_path', ''),
            'contact_email' => Setting::get('general', 'contact_email', ''),
            'contact_recipient_email' => Setting::get('general', 'contact_recipient_email', ''),
            'contact_phone' => Setting::get('general', 'contact_phone', ''),
            'address' => LocalizedSettings::decodeText(Setting::get('general', 'address', '')),
            'social_links' => json_decode(Setting::get('social', 'links', '[]'), true) ?? [],
            'archive_active_days' => (int) Setting::get('general', 'archive_active_days', 90),
            'house_ads_enabled' => filter_var(Setting::get('advertising', 'house_ads_enabled', '1'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Site Bilgileri')
                ->description('Bu alanlar public header, footer ve meta katmanında görünür. Türkçe alan zorunludur; diğer diller boş bırakılırsa kontrollü fallback kullanılır.')
                ->schema([
                    $this->localizedTextTabs(
                        'site_name',
                        'Site Adı',
                        'Header masthead, footer ve meta/site adı alanında kullanılır.',
                        required: true,
                    ),
                    $this->localizedTextTabs(
                        'site_tagline',
                        'Slogan',
                        'Header alt metni ve footer açıklamasında görünür.',
                    ),
                    FileUpload::make('logo_path')
                        ->label('Logo (Açık Tema)')
                        ->helperText('Açık zeminli alanlarda ve footer fallback görselinde kullanılır.')
                        ->image()
                        ->directory('settings')
                        ->maxSize(AdminImageUploads::maxSizeKb())
                        ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file)),
                    FileUpload::make('dark_logo_path')
                        ->label('Logo (Koyu Tema)')
                        ->helperText('Koyu zeminlerde kullanılacak marka görselidir.')
                        ->image()
                        ->directory('settings')
                        ->maxSize(AdminImageUploads::maxSizeKb())
                        ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file)),
                    FileUpload::make('favicon_path')
                        ->label('Favicon')
                        ->helperText('Tarayıcı sekmesi ve yer imlerinde görünür.')
                        ->image()
                        ->directory('settings')
                        ->maxSize(AdminImageUploads::maxSizeKb())
                        ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file)),
                ])->columns(2),

            Section::make('İletişim Bilgileri')
                ->description('Telefon ve e-posta footer iletişim alanında görünür. Adres alanı çok dillidir ve public tarafta locale seçimine göre çözülür.')
                ->schema([
                    TextInput::make('contact_email')
                        ->label('E-posta')
                        ->helperText('Footer iletişim alanında görünür.')
                        ->email(),
                    TextInput::make('contact_recipient_email')
                        ->label('Form Alici E-posta')
                        ->helperText('Iletisim formu mesajlari bu adrese gonderilir. Bos birakilirsa footer e-posta adresi kullanilir.')
                        ->email(),
                    TextInput::make('contact_phone')
                        ->label('Telefon')
                        ->helperText('Footer iletişim alanında görünür.'),
                    $this->localizedTextTabs(
                        'address',
                        'Adres',
                        'Footer ve iletişim sayfasında görünür.',
                        isTextarea: true,
                        rows: 3,
                    ),
                ])->columns(2),

            Section::make('Arşiv Politikası')
                ->description('Operasyonel bir ayardır; public sitede doğrudan görünmez.')
                ->schema([
                    TextInput::make('archive_active_days')
                        ->label('Aktif Haber Süresi (gün)')
                        ->numeric()
                        ->minValue(1)
                        ->default(90)
                        ->helperText('Bu süreden eski yayındaki haberler arşive taşınır. Arşiv içerikleri süresiz saklanır.'),
                    Placeholder::make('archive_retention_note')
                        ->label('Saklama Politikası')
                        ->content('K24: Haberler süresiz saklanır. Sistem yalnızca aktif süre eşiğini aşan içerikleri arşive taşır; kalıcı silme yapmaz.'),
                ])->columns(2),

            Section::make('Reklam Dolgu Alanları')
                ->description('Gerçek reklam yoksa public yüzeyde profesyonel "Buraya reklam verebilirsiniz" alanları gösterilir.')
                ->schema([
                    Toggle::make('house_ads_enabled')
                        ->label('Boş reklam alanlarını satış çağrısıyla doldur')
                        ->helperText('Header, ana sayfa sponsorlu alanları ve footer için geçerlidir. Haber detayının üst reklam alanı okuma deneyimini bozmasın diye dolgu göstermez.')
                        ->default(true),
                    Placeholder::make('house_ads_contact_note')
                        ->label('İletişim kaynağı')
                        ->content('Dolgu alanlarında Genel Ayarlar > Telefon değeri kullanılır. Telefon yoksa e-posta, o da yoksa iletişim sayfası bağlantısı gösterilir.'),
                ])->columns(2),

            Section::make('Sosyal Medya')
                ->description('Kaydedilen bağlantılar header ve footer sosyal ikonlarını besler.')
                ->schema([
                    Repeater::make('social_links')
                        ->label('Sosyal Medya Linkleri')
                        ->schema([
                            Select::make('platform')
                                ->label('Platform')
                                ->options([
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'twitter' => 'Twitter/X',
                                    'youtube' => 'YouTube',
                                    'linkedin' => 'LinkedIn',
                                ]),
                            TextInput::make('url')
                                ->label('URL')
                                ->url(),
                        ])
                        ->columns(2),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('general', 'site_name', LocalizedSettings::encodeText($data['site_name'] ?? []));
        Setting::set('general', 'site_tagline', LocalizedSettings::encodeText($data['site_tagline'] ?? []));
        Setting::set('general', 'logo_path', $data['logo_path'] ?? '');
        Setting::set('general', 'dark_logo_path', $data['dark_logo_path'] ?? '');
        Setting::set('general', 'favicon_path', $data['favicon_path'] ?? '');
        Setting::set('general', 'contact_email', $data['contact_email'] ?? '');
        Setting::set('general', 'contact_recipient_email', $data['contact_recipient_email'] ?? '');
        Setting::set('general', 'contact_phone', $data['contact_phone'] ?? '');
        Setting::set('general', 'address', LocalizedSettings::encodeText($data['address'] ?? []));
        Setting::set('social', 'links', json_encode($data['social_links'] ?? [], JSON_UNESCAPED_UNICODE));
        Setting::set('general', 'archive_active_days', $data['archive_active_days'] ?? 90);
        Setting::set('advertising', 'house_ads_enabled', ! empty($data['house_ads_enabled']) ? '1' : '0');

        Notification::make()->success()->title('Ayarlar kaydedildi')->send();
    }

    private function localizedTextTabs(
        string $field,
        string $label,
        string $helperText,
        bool $required = false,
        bool $isTextarea = false,
        int $rows = 2,
    ): Tabs {
        return Tabs::make($label)
            ->columnSpanFull()
            ->tabs(
                collect(LocalizedSettings::localeLabels())
                    ->map(
                        fn (string $localeLabel, string $locale): Tab => Tab::make($localeLabel)
                            ->schema([
                                $isTextarea
                                    ? Textarea::make("{$field}.{$locale}")
                                        ->label($label)
                                        ->rows($rows)
                                        ->helperText($helperText)
                                        ->required($required && $locale === 'tr')
                                    : TextInput::make("{$field}.{$locale}")
                                        ->label($label)
                                        ->helperText($helperText)
                                        ->required($required && $locale === 'tr'),
                            ])
                    )
                    ->values()
                    ->all()
            );
    }
}
