<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsArticleResource\Pages;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Tag;
use App\Services\IhaCategoryMapper;
use App\Support\AdminImageUploads;
use App\Support\AdminPrivileges;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class NewsArticleResource extends Resource
{
    use Translatable;

    protected static ?string $model = NewsArticle::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Haberler';
    protected static ?string $navigationLabel = 'Tüm Haberler';
    protected static ?string $modelLabel = 'Haber';
    protected static ?string $pluralModelLabel = 'Haberler';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Tüm Haberler';
    }

    public static function form(Form $form): Form
    {
        $isIhaReadonly = fn ($record): bool => (bool) $record?->isFromIha();
        $cannotPublish = fn (): bool => ! self::canPublishNews();

        return $form->schema([
            Tabs::make('Haber Formu')->tabs([

                Tabs\Tab::make('Temel Bilgiler')->schema([
                    TextInput::make('title')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(500)
                        ->live(onBlur: true)
                        ->disabled($isIhaReadonly)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(500)
                        ->unique(NewsArticle::class, 'slug', ignoreRecord: true)
                        ->disabled($isIhaReadonly),

                    Textarea::make('summary')
                        ->label('Özet')
                        ->maxLength(300)
                        ->rows(3)
                        ->disabled($isIhaReadonly),

                    RichEditor::make('content')
                        ->label('İçerik')
                        ->required()
                        ->disableToolbarButtons(['attachFiles'])
                        ->disabled($isIhaReadonly)
                        ->columnSpanFull(),

                    Select::make('source')
                        ->label('Kaynak')
                        ->options([
                            'manuel' => 'Manuel',
                            'iha' => 'IHA',
                        ])
                        ->default('manuel')
                        ->required()
                        ->disabled($isIhaReadonly),

                    TextInput::make('source_url')
                        ->label('Kaynak URL')
                        ->url()
                        ->maxLength(500)
                        ->disabled($isIhaReadonly)
                        ->visible(fn (Get $get) => $get('source') === 'iha'),
                ])->columns(2),

                Tabs\Tab::make('Görseller')->schema([
                    FileUpload::make('featured_image')
                        ->label('Öne Çıkan Görsel')
                        ->image()
                        ->maxSize(AdminImageUploads::maxSizeKb())
                        ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                        ->disabled($isIhaReadonly)
                        ->directory('news/featured')
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file))
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->columnSpanFull(),

                    Repeater::make('images_data')
                        ->label('Galeri')
                        ->relationship('images')
                        ->disabled($isIhaReadonly)
                        ->schema([
                            FileUpload::make('image_path')
                                ->label('Görsel')
                                ->image()
                                ->required()
                                ->disabled($isIhaReadonly)
                                ->directory('news/gallery')
                                ->maxSize(AdminImageUploads::maxSizeKb())
                                ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file)),
                            TextInput::make('caption')
                                ->label('Açıklama')
                                ->disabled($isIhaReadonly)
                                ->maxLength(500),
                        ])
                        ->reorderable('sort_order')
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

                Tabs\Tab::make('Kategoriler & Etiketler')->schema([
                    Select::make('category_id')
                        ->label('Ana Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled($isIhaReadonly)
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),

                    Select::make('categories')
                        ->label('Ek Kategoriler')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->disabled($isIhaReadonly)
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),

                    Select::make('tags')
                        ->label('Etiketler')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (Tag $record): string => $record->getTranslation('name', app()->getLocale()))
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Etiket adi')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(Tag::class, 'slug'),
                        ])
                        ->createOptionUsing(fn (array $data): int => Tag::query()->create([
                            'name' => ['tr' => $data['name']],
                            'slug' => $data['slug'],
                        ])->getKey())
                        ->disabled($isIhaReadonly)
                        ->placeholder('Etiket sec veya olustur...'),
                ]),

                Tabs\Tab::make('Yayın Ayarları')->schema([
                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            'draft' => 'Taslak',
                            'published' => 'Yayında',
                            'archived' => 'Arşiv',
                        ])
                        ->default(fn (): string => self::canPublishNews() ? 'published' : 'draft')
                        ->required()
                        ->disabled(fn ($record): bool => $isIhaReadonly($record) || $cannotPublish()),

                    Toggle::make('is_breaking')
                        ->label('Son Dakika')
                        ->disabled(fn ($record): bool => $isIhaReadonly($record) || $cannotPublish()),

                    Toggle::make('is_featured')
                        ->disabled(fn ($record): bool => $isIhaReadonly($record) || $cannotPublish())
                        ->label('Manşet'),

                    Select::make('homepage_pin_area')
                        ->label('Anasayfa sabitleme alani')
                        ->options(self::homepagePinAreaOptions())
                        ->helperText('Suresi dolana kadar ilgili blokta oncelikli gosterilir. Hero ve yan manset icin gercek gorsel gerekir.')
                        ->nullable()
                        ->searchable()
                        ->disabled(fn (): bool => $cannotPublish()),

                    DateTimePicker::make('homepage_pin_until')
                        ->label('Sabitleme bitis zamani')
                        ->helperText('Bos kalirsa sabitleme manuel kaldirilana kadar surer.')
                        ->native(false)
                        ->disabled(fn (): bool => $cannotPublish()),

                    DateTimePicker::make('homepage_exclude_until')
                        ->label('Anasayfadan dislama bitis zamani')
                        ->helperText('Bu tarihe kadar haber anasayfa bloklarinda secilmez; haber detayi yayinda kalabilir.')
                        ->native(false)
                        ->disabled(fn (): bool => $cannotPublish()),

                    DateTimePicker::make('published_at')
                        ->label('Yayın Tarihi')
                        ->disabled(fn ($record): bool => $isIhaReadonly($record) || $cannotPublish())
                        ->native(false),

                    Select::make('city_slug')
                        ->label('Public şehir sayfası')
                        ->options(fn (): array => IhaCategoryMapper::getActiveCities())
                        ->helperText('Seçilirse haber ilgili /il/{şehir} sayfasında ve Adıyaman seçimi için yerel haber bloklarında görünür.')
                        ->searchable()
                        ->nullable()
                        ->disabled($isIhaReadonly),

                    Select::make('city_code')
                        ->label('Yerellik skoru')
                        ->options([
                            IhaCategoryMapper::LOCALITY_LOCAL => 'Adıyaman yerel',
                            IhaCategoryMapper::LOCALITY_REGION => 'Bölge',
                            IhaCategoryMapper::LOCALITY_NATIONAL => 'Ulusal / diğer',
                        ])
                        ->helperText('İHA senkronunun iç öncelik skorudur; public şehir sayfasını city_slug belirler.')
                        ->disabled($isIhaReadonly)
                        ->hidden(fn (Get $get) => $get('source') === 'manuel'),

                    Placeholder::make('editorial_score_summary')
                        ->label('Editoryal Puan')
                        ->content(function (?NewsArticle $record): string {
                            if (! $record) {
                                return 'Kayıt oluşturulduktan sonra editoryal puan otomatik hesaplanır.';
                            }

                            return sprintf(
                                'Puan: %s/100. Bu skor manşet seçimi ve anasayfa editoryal sıralaması için kullanılır.',
                                $record->editorial_score ?? 0
                            );
                        })
                        ->columnSpanFull(),

                    Placeholder::make('editorial_score_breakdown_view')
                        ->label('Puan gerekcesi')
                        ->content(fn (?NewsArticle $record): string => $record
                            ? self::editorialScoreBreakdownText($record)
                            : 'Kayit olusturulduktan sonra puan gerekcesi gorunur.')
                        ->columnSpanFull(),
                ])->columns(2),

                Tabs\Tab::make('SEO')->schema([
                    TextInput::make('meta_title')->disabled($isIhaReadonly)
                        ->label('Meta Başlık')
                        ->maxLength(255)
                        ->placeholder('Otomatik: haber başlığından'),

                    Textarea::make('meta_description')->disabled($isIhaReadonly)
                        ->label('Meta Açıklama')
                        ->maxLength(160)
                        ->placeholder('Otomatik: özetten'),
                ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Görsel')
                    ->width(48)
                    ->height(48)
                    ->square()
                    ->defaultImageUrl(asset('images/news/placeholder-news.jpg')),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->limit(72)
                    ->wrap()
                    ->formatStateUsing(fn (NewsArticle $record): string => self::tableTitle($record))
                    ->description(fn (NewsArticle $record): string => self::tableSourceReference($record))
                    ->tooltip(fn (NewsArticle $record): string => self::tableTitle($record)),

                BadgeColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->formatStateUsing(fn (NewsArticle $record): string => $record->category?->getTranslation('name', 'tr') ?? '-')
                    ->colors(['primary']),

                BadgeColumn::make('city_slug')
                    ->label('Şehir')
                    ->formatStateUsing(fn (?string $state): string => $state ? (IhaCategoryMapper::getActiveCities()[$state] ?? $state) : '-')
                    ->colors(['gray'])
                    ->toggleable(),

                BadgeColumn::make('source')
                    ->label('Kaynak')
                    ->sortable()
                    ->colors([
                        'info' => 'iha',
                        'success' => 'manuel',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'iha' => 'İHA',
                        'manuel' => 'Manuel',
                        default => $state ?: '-',
                    }),

                BadgeColumn::make('status')
                    ->label('Durum')
                    ->sortable()
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'published' => 'Yayında',
                        'draft' => 'Taslak',
                        'archived' => 'Arşiv',
                        default => $state ?: '-',
                    }),

                BadgeColumn::make('instagramPublication.status')
                    ->label('Instagram')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'processing',
                        'success' => 'published',
                        'danger' => 'failed',
                        'warning' => 'skipped',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Bekliyor',
                        'processing' => 'İşleniyor',
                        'published' => 'Yayınlandı',
                        'failed' => 'Hatalı',
                        'skipped' => 'Atlandı',
                        default => $state ?: '-',
                    })
                    ->toggleable(),

                TextColumn::make('editorial_score')
                    ->label('Editoryal Puan')
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        (int) $state >= 70 => 'success',
                        (int) $state >= 40 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ((int) ($state ?? 0)) . '/100'),

                BadgeColumn::make('homepage_pin_area')
                    ->label('Anasayfa')
                    ->formatStateUsing(fn (?string $state): string => self::homepagePinAreaOptions()[$state] ?? '-')
                    ->colors(['info'])
                    ->toggleable(),

                TextColumn::make('view_count')
                    ->label('Görüntülenme')
                    ->sortable()
                    ->numeric()
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Yayın Tarihi')
                    ->sortable()
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (NewsArticle $record): ?string => $record->published_at?->diffForHumans())
                    ->toggleable(),

                IconColumn::make('is_breaking')
                    ->label('Son Dakika')
                    ->sortable()
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('Manşet')
                    ->sortable()
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', 'tr')),

                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'draft' => 'Taslak',
                        'published' => 'Yayında',
                        'archived' => 'Arşiv',
                    ]),

                SelectFilter::make('source')
                    ->label('Kaynak')
                    ->options([
                        'iha' => 'İHA',
                        'manuel' => 'Manuel',
                    ]),

                SelectFilter::make('city_slug')
                    ->label('Public Şehir')
                    ->options(fn (): array => IhaCategoryMapper::getActiveCities()),

                SelectFilter::make('homepage_pin_area')
                    ->label('Anasayfa alani')
                    ->options(self::homepagePinAreaOptions()),

                TernaryFilter::make('is_breaking')
                    ->label('Son Dakika'),

                TernaryFilter::make('is_featured')
                    ->label('Manşet'),

                SelectFilter::make('editorial_band')
                    ->label('Editoryal Puan')
                    ->options([
                        'high' => '70+ Yüksek',
                        'medium' => '40-69 Orta',
                        'low' => '0-39 Düşük',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'high' => $query->where('editorial_score', '>=', 70),
                            'medium' => $query->whereBetween('editorial_score', [40, 69]),
                            'low' => $query->where('editorial_score', '<', 40),
                            default => $query,
                        };
                    }),

                Filter::make('published_at')
                    ->label('Yayın Tarihi')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Başlangıç'),
                        Forms\Components\DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('published_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('published_at', '<=', $data['until']))),

                TrashedFilter::make()
                    ->label('Silinmiş Kayıtlar')
                    ->placeholder('Silinmişleri gösterme')
                    ->trueLabel('Silinmişlerle birlikte')
                    ->falseLabel('Yalnız silinmişler'),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Yayınla')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->modalHeading('Seçili haberleri yayınla')
                        ->modalDescription('Seçimde İHA haberi varsa işlem iptal edilir; İHA katalog bütünlüğü yalnızca senkron akışıyla korunur.')
                        ->modalSubmitActionLabel('Yayınla')
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'published'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('archive')
                        ->label('Arşivle')
                        ->icon('heroicon-o-archive-box')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->modalHeading('Seçili haberleri arşivle')
                        ->modalDescription('Arşivlenen haberler public arşiv erişimi kapsamında kalabilir; İHA kaydı içeren seçimler iptal edilir.')
                        ->modalSubmitActionLabel('Arşivle')
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'archived'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('draft')
                        ->label('Taslağa Al')
                        ->icon('heroicon-o-document')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->modalHeading('Seçili haberleri taslağa al')
                        ->modalDescription('Taslağa alınan haberler sitede görünmez. İHA kaydı içeren seçimler iptal edilir.')
                        ->modalSubmitActionLabel('Taslağa Al')
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'draft'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('set_breaking')
                        ->label('Son Dakika Yap')
                        ->icon('heroicon-o-bolt')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_breaking' => true], 'publish_news_article')),

                    BulkAction::make('unset_breaking')
                        ->label('Son Dakikayı Kaldır')
                        ->icon('heroicon-o-bolt-slash')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_breaking' => false], 'publish_news_article')),

                    BulkAction::make('set_featured')
                        ->label('Manşete Ekle')
                        ->icon('heroicon-o-star')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_featured' => true], 'publish_news_article')),

                    BulkAction::make('unset_featured')
                        ->label('Manşetten Kaldır')
                        ->icon('heroicon-o-x-mark')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_featured' => false], 'publish_news_article')),

                    BulkAction::make('change_category')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->label('Kategori Değiştir')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->options(Category::query()->get()->mapWithKeys(fn (Category $category) => [$category->id => $category->getTranslation('name', 'tr')]))
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::updateManualRecords($records, ['category_id' => $data['category_id']], 'publish_news_article')),

                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->modalHeading('Seçili haberleri sil')
                        ->modalDescription('İHA kaydı içeren seçimler iptal edilir; manuel haberler silinebilir.')
                        ->before(fn (DeleteBulkAction $action, Collection $records) => self::cancelIhaBulkMutation($action, $records)),
                    RestoreBulkAction::make()
                        ->label('Geri Al')
                        ->modalHeading('Seçili haberleri geri al'),
                    ForceDeleteBulkAction::make()
                        ->label('Kalıcı Sil')
                        ->modalHeading('Seçili haberleri kalıcı olarak sil'),
                ])->label('Toplu İşlemler'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),
                TableAction::make('view_site')
                    ->label('Sitede Gör')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (NewsArticle $record): string => route('news.show', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (NewsArticle $record): bool => self::isPubliclyVisibleOnSite($record)),
                RestoreAction::make()->label('Geri Al'),
                ForceDeleteAction::make()->label('Kalıcı Sil'),
            ])
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateHeading('Bu kapsamda haber bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Haber oluşturma yetkiniz varsa yeni haber ekleyebilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Haber')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('published_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->poll('60s');
    }

    private static function tableTitle(NewsArticle $record): string
    {
        return trim((string) $record->getTranslation('title', 'tr', false)) ?: (string) $record->slug;
    }

    private static function tableSourceReference(NewsArticle $record): string
    {
        if ($record->isFromIha()) {
            return $record->iha_id ? 'İHA ID: ' . $record->iha_id : 'İHA senkron kaydı';
        }

        return 'Manuel kayıt';
    }

    private static function isPubliclyVisibleOnSite(NewsArticle $record): bool
    {
        if ($record->trashed()) {
            return false;
        }

        if (! in_array($record->status, ['published', 'archived'], true)) {
            return false;
        }

        return $record->published_at === null || $record->published_at->lessThanOrEqualTo(now());
    }

    private static function homepagePinAreaOptions(): array
    {
        return [
            'hero' => 'Ana manset',
            'hero_side' => 'Yan manset',
            'local_news' => 'Adiyaman gundemi',
            'asayis_news' => 'Asayis',
            'region_news' => 'Bolge',
            'politics_economy' => 'Siyaset ve ekonomi',
            'life_digest' => 'Yasam',
            'latest_news' => 'Son haberler',
        ];
    }

    private static function editorialScoreBreakdownText(NewsArticle $record): string
    {
        $factors = collect($record->editorial_score_breakdown['factors'] ?? [])
            ->filter(fn ($factor): bool => is_array($factor) && isset($factor['label'], $factor['points']))
            ->sortByDesc(fn ($factor): int => (int) ($factor['points'] ?? 0))
            ->take(5)
            ->map(fn ($factor): string => sprintf('%s %+d', $factor['label'], (int) $factor['points']))
            ->values();

        if ($factors->isEmpty()) {
            return 'Puan gerekcesi henuz olusmadi; bir sonraki IHA sync veya editorial:recalculate sonrasinda guncellenir.';
        }

        return 'One cikan faktorler: '.$factors->implode(', ');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsArticles::route('/'),
            'create' => Pages\CreateNewsArticle::route('/create'),
            'edit' => Pages\EditNewsArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['category', 'author', 'instagramPublication']);
    }

    public static function getTranslatableLocales(): array
    {
        return ['tr', 'en', 'ku'];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_news_article');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_news_article');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_news_article');
    }

    public static function canDelete(Model $record): bool
    {
        if ($record instanceof NewsArticle && $record->isFromIha()) {
            return false;
        }

        return AdminPrivileges::hasPermission(auth()->user(), 'delete_news_article');
    }

    public static function canDeleteAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_any_news_article');
    }

    public static function canForceDelete(Model $record): bool
    {
        if ($record instanceof NewsArticle && $record->isFromIha()) {
            return false;
        }

        return AdminPrivileges::hasPermission(auth()->user(), 'force_delete_news_article');
    }

    public static function canRestore(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'restore_news_article');
    }

    public static function canRestoreAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'restore_any_news_article');
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    private static function updateManualRecords(Collection $records, array $attributes, string $permission = 'update_news_article'): void
    {
        if (! AdminPrivileges::hasPermission(auth()->user(), $permission)) {
            self::notifyUnauthorizedBulkMutation();

            return;
        }

        if (self::containsIhaRecord($records)) {
            self::notifyIhaMutationBlocked();

            return;
        }

        $records->each->update($attributes);

        Notification::make()
            ->success()
            ->title('Toplu işlem tamamlandı')
            ->body($records->count() . ' haber güncellendi.')
            ->send();
    }

    private static function cancelIhaBulkMutation(BulkAction $action, Collection $records): void
    {
        if (! self::containsIhaRecord($records)) {
            return;
        }

        self::notifyIhaMutationBlocked();
        $action->cancel();
    }

    private static function containsIhaRecord(Collection $records): bool
    {
        return $records->contains(fn (NewsArticle $record): bool => $record->isFromIha());
    }

    private static function notifyIhaMutationBlocked(): void
    {
        Notification::make()
            ->danger()
            ->title('İHA haberleri toplu işlemle değiştirilemez')
            ->body('Seçimde İHA kaydı bulunduğu için işlem iptal edildi. İHA haber katalog bütünlüğü yalnızca senkron akışıyla korunur.')
            ->send();
    }

    private static function notifyUnauthorizedBulkMutation(): void
    {
        Notification::make()
            ->danger()
            ->title('Bu toplu işlem için yetki gerekli')
            ->body('Yayın, vitrin ve kategori etkili toplu işlemler yalnızca yayın yetkisi olan kullanıcılar tarafından yapılabilir.')
            ->send();
    }

    private static function canPublishNews(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'publish_news_article');
    }
}
