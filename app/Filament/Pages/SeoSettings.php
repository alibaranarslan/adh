<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AdminImageUploads;
use App\Support\AdminPrivileges;
use App\Support\SeoHealth;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'SEO';
    protected static ?string $navigationGroup = 'Ayarlar';
    protected static ?string $title = 'SEO Ayarları';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.pages.settings';

    public array $data = [];

    public static function canAccess(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public function mount(): void
    {
        $this->data = [
            'default_meta_title' => Setting::get('seo', 'default_meta_title', '{title} - {site_name}'),
            'default_meta_description' => Setting::get('seo', 'default_meta_description', ''),
            'og_image' => Setting::get('seo', 'og_image', ''),
            'robots_txt' => Setting::get('seo', 'robots_txt', "User-agent: *\nAllow: /"),
            'google_search_console_code' => Setting::get('seo', 'google_search_console_code', ''),
            'google_analytics_id' => Setting::get('integration', 'google_analytics_id', ''),
        ];
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('SEO Sağlığı')->schema([
                Placeholder::make('canonical_status')
                    ->label('Canonical / HTTPS')
                    ->content(fn (): string => $this->seoCardSummary('Canonical HTTPS')),
                Placeholder::make('sitemap_status')
                    ->label('Sitemap Durumu')
                    ->content(fn (): string => $this->seoCardSummary('Sitemap')),
                Placeholder::make('news_sitemap_status')
                    ->label('Google News Sitemap')
                    ->content(fn (): string => $this->seoCardSummary('News sitemap')),
                Placeholder::make('recent_article_status')
                    ->label('Son 20 Haber')
                    ->content(fn (): string => $this->seoCardSummary('Son 20 haber')),
            ])->columns(2),

            Section::make('AI Görünürlüğü')->schema([
                Placeholder::make('ai_crawl_summary')
                    ->label('AI Crawl Özeti')
                    ->content(fn (): string => $this->seoCardSummary('AI crawl')),
                Placeholder::make('feed_summary')
                    ->label('Feed / llms.txt Özeti')
                    ->content(fn (): string => $this->seoCardSummary('Feed / llms.txt')),
                Placeholder::make('llms_txt_status')
                    ->label('/llms.txt')
                    ->content(fn (): string => $this->seoSnapshot()['llms_txt_available']
                        ? 'Hazır: makine-okunur kaynak haritası yayında.'
                        : 'Risk: /llms.txt route kaydi bulunamadi.'),
                Placeholder::make('rss_status')
                    ->label('/rss.xml')
                    ->content(fn (): string => $this->seoSnapshot()['rss_available']
                        ? 'Hazır: public RSS feed route kaydı yayında.'
                        : 'Risk: RSS feed route kaydı bulunamadı.'),
                Placeholder::make('oai_searchbot_status')
                    ->label('OAI-SearchBot')
                    ->content(fn (): string => $this->seoSnapshot()['oai_searchbot_allowed']
                        ? 'Allow: ChatGPT Search crawler robots tarafında açık.'
                        : 'Risk: OAI-SearchBot için Allow sinyali yok.'),
                Placeholder::make('chatgpt_user_status')
                    ->label('ChatGPT-User')
                    ->content(fn (): string => $this->seoSnapshot()['chatgpt_user_allowed']
                        ? 'Allow: ChatGPT user-triggered browse robots tarafında açık.'
                        : 'Risk: ChatGPT-User için Allow sinyali yok.'),
                Placeholder::make('llms_recent_status')
                    ->label('Son Haberler')
                    ->content(fn (): string => $this->seoSnapshot()['llms_recent_articles_available']
                        ? 'Hazır: llms.txt son yayınlanmış haberleri listeleyebilir.'
                        : 'Uyarı: henüz listelenecek yayınlanmış haber yok.'),
            ])->columns(2),

            Section::make('Meta Ayarları')->schema([
                TextInput::make('default_meta_title')
                    ->label('Varsayılan Meta Başlık Formatı')
                    ->placeholder('{title} - {site_name}')
                    ->helperText('Public sayfalarda özel meta başlık yoksa kullanılır. Kullanılabilir: {title}, {site_name}, {category}.'),

                Textarea::make('default_meta_description')
                    ->label('Varsayılan Meta Açıklama')
                    ->helperText('Haber veya sayfa özel açıklaması yoksa arama sonuçlarında fallback olarak kullanılır.')
                    ->rows(3),

                FileUpload::make('og_image')
                    ->label('Varsayılan OG Görseli')
                    ->helperText('Sosyal paylaşım görseli olmayan sayfalarda kullanılır.')
                    ->image()
                    ->directory('seo')
                    ->maxSize(AdminImageUploads::maxSizeKb())
                    ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file)),
            ]),

            Section::make('Arama Motorları')->schema([
                Textarea::make('robots_txt')
                    ->label('Robots.txt')
                    ->helperText('Crawler erişimi ve sitemap sinyali burada yönetilir. Sitemap üretimi bu ekranda otomatik çalıştırılmaz.')
                    ->rows(6)
                    ->columnSpanFull(),

                TextInput::make('google_search_console_code')
                    ->label('Google Search Console Doğrulama Kodu')
                    ->helperText('Yalnız doğrulama meta kodu veya token değeri girilmelidir. Tam HTML etiketi gerekiyorsa public çıktı kontrol edilmelidir.'),

                TextInput::make('google_analytics_id')
                    ->label('Google Analytics ID')
                    ->placeholder('G-XXXXXXXXXX')
                    ->helperText('GA4 ölçüm kimliği public analytics scriptinde kullanılır.'),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (['default_meta_title', 'default_meta_description', 'og_image', 'robots_txt', 'google_search_console_code'] as $key) {
            Setting::set('seo', $key, $data[$key]);
        }
        Setting::set('integration', 'google_analytics_id', $data['google_analytics_id']);

        Notification::make()
            ->success()
            ->title('SEO ayarları kaydedildi')
            ->body('Meta, robots.txt, Search Console ve Analytics ayarları güncellendi. Sitemap üretimi otomatik çalıştırılmadı.')
            ->send();
    }

    public function settingsSummary(): string
    {
        return 'Canonical, sitemap, robots.txt, AI crawl ve varsayılan meta ayarları public arama görünürlüğünü etkiler.';
    }

    /**
     * @return array<int, string>
     */
    public function settingsImpactBadges(): array
    {
        return ['Public SEO', 'Crawler erişimi', 'Gizli bilgi yok'];
    }

    private function seoSnapshot(): array
    {
        return app(SeoHealth::class)->snapshot();
    }

    private function sitemapSummary(): string
    {
        $snapshot = $this->seoSnapshot();

        if (! $snapshot['sitemap_exists']) {
            return 'Eksik: sitemap:generate çalıştırılmalı.';
        }

        return 'Hazır: ' . $snapshot['expected_sitemap'] . ' / Son üretim: ' . ($snapshot['sitemap_updated_at'] ?? 'bilinmiyor');
    }

    private function recentArticleSummary(): string
    {
        $snapshot = $this->seoSnapshot();

        return $snapshot['recent_articles_checked']
            . ' haber kontrol edildi; '
            . $snapshot['recent_missing_meta'] . ' meta eksiği, '
            . $snapshot['recent_missing_image'] . ' görsel eksiği.';
    }

    private function seoCardSummary(string $label): string
    {
        $card = collect(app(SeoHealth::class)->cards())->firstWhere('label', $label);

        if (! $card) {
            return 'UNVERIFIED: Bu SEO kontrolü için veri üretilemedi.';
        }

        $tone = match ($card['tone'] ?? null) {
            'success' => 'Hazır',
            'warning' => 'Uyarı',
            'danger' => 'Risk',
            default => 'Bilgi',
        };

        return sprintf('%s: %s. %s', $tone, $card['value'] ?? '-', $card['meta'] ?? '');
    }
}
