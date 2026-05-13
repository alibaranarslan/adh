<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsArticleResource\Pages;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Tag;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
use Filament\Resources\Concerns\Translatable;
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

                    DateTimePicker::make('published_at')
                        ->label('Yayın Tarihi')
                        ->disabled(fn ($record): bool => $isIhaReadonly($record) || $cannotPublish())
                        ->native(false),

                    Select::make('city_code')
                        ->label('Şehir')
                        ->options([
                            2 => 'Adıyaman',
                            34 => 'İstanbul',
                            6 => 'Ankara',
                            35 => 'İzmir',
                        ])
                        ->searchable()
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
                    ->width(40)
                    ->height(40)
                    ->defaultImageUrl(asset('images/news/placeholder-news.jpg')),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->getTranslation('title', 'tr')),

                BadgeColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->category?->getTranslation('name', 'tr') ?? '-')
                    ->colors(['primary']),

                BadgeColumn::make('source')
                    ->label('Kaynak')
                    ->sortable()
                    ->colors([
                        'info' => 'iha',
                        'success' => 'manuel',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'iha' ? 'IHA' : 'Manuel'),

                BadgeColumn::make('status')
                    ->label('Durum')
                    ->sortable()
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'published' => 'Yayında',
                        'draft' => 'Taslak',
                        'archived' => 'Arşiv',
                        default => $state,
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
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'pending' => 'Bekliyor',
                        'processing' => 'İşleniyor',
                        'published' => 'Yayınlandı',
                        'failed' => 'Hatalı',
                        'skipped' => 'Atlandı',
                        default => $state ?: '-',
                    }),

                TextColumn::make('editorial_score')
                    ->label('Editoryal Puan')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 70 => 'success',
                        $state >= 40 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . '/100'),

                TextColumn::make('view_count')
                    ->label('Görüntülenme')
                    ->sortable()
                    ->numeric(),

                TextColumn::make('published_at')
                    ->label('Yayın Tarihi')
                    ->sortable()
                    ->dateTime('d.m.Y H:i')
                    ->since(),

                IconColumn::make('is_breaking')
                    ->label('Son Dakika')
                    ->sortable()
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Manşet')
                    ->sortable()
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'tr')),

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
                        'iha' => 'IHA',
                        'manuel' => 'Manuel',
                    ]),

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
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Başlangıç'),
                        Forms\Components\DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('published_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('published_at', '<=', $data['until']))),

                TrashedFilter::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Yayınla')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'published'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('archive')
                        ->label('Arşivle')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'archived'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('draft')
                        ->label('Taslağa Al')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['status' => 'draft'], 'publish_news_article'))
                        ->requiresConfirmation(),

                    BulkAction::make('set_breaking')
                        ->label('Son Dakika Yap')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_breaking' => true], 'publish_news_article')),

                    BulkAction::make('unset_breaking')
                        ->label('Son Dakikayı Kaldır')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_breaking' => false], 'publish_news_article')),

                    BulkAction::make('set_featured')
                        ->label('Manşete Ekle')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_featured' => true], 'publish_news_article')),

                    BulkAction::make('unset_featured')
                        ->label('Manşetten Kaldır')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->action(fn (Collection $records) => self::updateManualRecords($records, ['is_featured' => false], 'publish_news_article')),

                    BulkAction::make('change_category')
                        ->visible(fn (): bool => self::canPublishNews())
                        ->label('Kategori Değiştir')
                        ->form([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->options(Category::all()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('name', 'tr')]))
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::updateManualRecords($records, ['category_id' => $data['category_id']], 'publish_news_article')),

                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->before(fn (DeleteBulkAction $action, Collection $records) => self::cancelIhaBulkMutation($action, $records)),
                    RestoreBulkAction::make()->label('Geri Al'),
                    ForceDeleteBulkAction::make()->label('Kalıcı Sil'),
                ]),
            ])
            ->actions([
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('published_at', 'desc')
            ->poll('60s');
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
            ->title('IHA haberleri toplu islemle degistirilemez')
            ->body('Secimde IHA kaydi bulundugu icin islem iptal edildi. IHA haber katalog butunlugu yalnizca senkron akisi ile korunur.')
            ->send();
    }

    private static function notifyUnauthorizedBulkMutation(): void
    {
        Notification::make()
            ->danger()
            ->title('Bu toplu islem icin yetki gerekli')
            ->body('Yayin, vitrin ve kategori etkili toplu islemler yalnizca yayin yetkisi olan kullanicilar tarafindan yapilabilir.')
            ->send();
    }

    private static function canPublishNews(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'publish_news_article');
    }
}
