<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    use Translatable;

    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Sayfalar';
    protected static ?string $modelLabel = 'Sayfa';
    protected static ?string $pluralModelLabel = 'Sayfalar';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, \Filament\Forms\Set $set) =>
                    $operation === 'create' ? $set('slug', Str::slug($state)) : null),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(Page::class, 'slug', ignoreRecord: true)
                ->disabled(fn (?Page $record): bool => (bool) $record?->isProtectedStaticPage())
                ->dehydrated(),

            RichEditor::make('content')
                ->label('İçerik')
                ->columnSpanFull(),

            TextInput::make('meta_title')
                ->label('Meta Başlık'),

            Textarea::make('meta_description')
                ->label('Meta Açıklama')
                ->maxLength(160),

            Toggle::make('is_published')
                ->label('Yayında')
                ->default(true)
                ->disabled(fn (?Page $record): bool => (bool) $record?->isProtectedStaticPage())
                ->dehydrated(),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('title', 'tr'))
                    ->description(fn (Page $record): string => $record->slug)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('page_kind')
                    ->label('Tür')
                    ->badge()
                    ->state(fn (Page $record): string => $record->isProtectedStaticPage() ? 'Korumalı' : 'Özel')
                    ->color(fn (Page $record): string => $record->isProtectedStaticPage() ? 'warning' : 'gray'),

                IconColumn::make('is_published')
                    ->label('Yayında')
                    ->boolean(),

                TextColumn::make('seo_status')
                    ->label('SEO')
                    ->badge()
                    ->state(fn (Page $record): string => self::seoStatusLabel($record))
                    ->color(fn (Page $record): string => self::seoStatusColor($record)),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Yayın Durumu')
                    ->trueLabel('Yayında')
                    ->falseLabel('Taslak'),

                SelectFilter::make('page_kind')
                    ->label('Sayfa Türü')
                    ->options([
                        'protected' => 'Korumalı statik sayfa',
                        'custom' => 'Özel sayfa',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'protected' => $query->whereIn('slug', Page::protectedStaticSlugs()),
                            'custom' => $query->whereNotIn('slug', Page::protectedStaticSlugs()),
                            default => $query,
                        };
                    }),

                SelectFilter::make('seo_status')
                    ->label('SEO Durumu')
                    ->options([
                        'complete' => 'Tam',
                        'missing' => 'Eksik',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'complete' => $query->whereNotNull('meta_title')->whereNotNull('meta_description'),
                            'missing' => $query->where(function (Builder $query) {
                                $query->whereNull('meta_title')->orWhereNull('meta_description');
                            }),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->actions([
                Action::make('view_site')
                    ->label('Sitede Gör')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $record): string => route('page.show', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record): bool => (bool) $record->is_published),

                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Sayfayı sil')
                    ->modalDescription(fn (Page $record): string => $record->isProtectedStaticPage()
                        ? 'Bu sayfa sabit public route tarafından kullanıldığı için silinemez.'
                        : 'Sayfa silindiğinde public erişimi kapanır. Yayındaki linkleri ve menü bağlantılarını kontrol edin.')
                    ->hidden(fn (Page $record): bool => $record->isProtectedStaticPage()),
            ])
            ->reorderable('sort_order', fn (): bool => self::canReorder())
            ->defaultSort('sort_order')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('Bu kapsamda sayfa bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni sayfa oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Sayfa')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_page');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_page');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_page');
    }

    public static function canDelete(Model $record): bool
    {
        if ($record instanceof Page && $record->isProtectedStaticPage()) {
            return false;
        }

        return AdminPrivileges::hasPermission(auth()->user(), 'delete_page');
    }

    public static function canReorder(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_page');
    }

    public static function getTranslatableLocales(): array
    {
        return ['tr', 'en', 'ku'];
    }

    private static function seoStatusLabel(Page $page): string
    {
        return self::translationValue($page, 'meta_title') !== ''
            && self::translationValue($page, 'meta_description') !== ''
                ? 'Tam'
                : 'Eksik';
    }

    private static function seoStatusColor(Page $page): string
    {
        return self::seoStatusLabel($page) === 'Tam' ? 'success' : 'warning';
    }

    private static function translationValue(Page $page, string $field): string
    {
        foreach (['tr', app()->getLocale(), 'en', 'ku'] as $locale) {
            $value = trim((string) $page->getTranslation($field, $locale, false));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
