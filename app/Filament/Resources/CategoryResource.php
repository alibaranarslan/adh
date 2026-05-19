<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    use Translatable;

    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Kategoriler';
    protected static ?string $modelLabel = 'Kategori';
    protected static ?string $pluralModelLabel = 'Kategoriler';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Ad')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, \Filament\Forms\Set $set) =>
                    $operation === 'create' ? $set('slug', Str::slug($state)) : null),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(Category::class, 'slug', ignoreRecord: true),

            Textarea::make('description')
                ->label('Açıklama')
                ->rows(3)
                ->columnSpanFull(),

            ColorPicker::make('color')
                ->label('Renk'),

            TextInput::make('icon')
                ->label('İkon')
                ->placeholder('heroicon-o-newspaper'),

            Select::make('parent_id')
                ->label('Üst Kategori')
                ->relationship('parent', 'name')
                ->nullable()
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),

            TextInput::make('iha_category_code')
                ->label('IHA Kategori Kodu')
                ->numeric()
                ->helperText('IHA kategori eşleştirme kodu'),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Renk'),

                TextColumn::make('name')
                    ->label('Ad')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'tr'))
                    ->description(fn (Category $record): string => $record->slug)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('iha_category_code')
                    ->label('İHA Eşleşmesi')
                    ->sortable()
                    ->badge()
                    ->placeholder('Eşleşme yok')
                    ->formatStateUsing(fn (?int $state): string => $state ? 'İHA #' . $state : 'Eşleşme yok')
                    ->color(fn (?int $state): string => $state ? 'info' : 'gray'),

                TextColumn::make('parent.name')
                    ->label('Üst Kategori')
                    ->formatStateUsing(fn ($record) => $record->parent?->getTranslation('name', 'tr') ?? '-'),

                TextColumn::make('public_articles_count')
                    ->label('Sitede Görünen Haber')
                    ->state(fn (Category $record): int => $record->publicArticlesCount())
                    ->badge()
                    ->color(fn (?int $state): string => ((int) $state) > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => ((int) $state) . ' haber'),

                TextColumn::make('articles_count')
                    ->label('Toplam Ana Haber')
                    ->counts('articles')
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => ((int) $state) > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => ((int) $state) . ' haber')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                SelectFilter::make('parent_scope')
                    ->label('Hiyerarşi')
                    ->options([
                        'root' => 'Üst kategori',
                        'child' => 'Alt kategori',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'root' => $query->whereNull('parent_id'),
                            'child' => $query->whereNotNull('parent_id'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('iha_mapping')
                    ->label('İHA Eşleşmesi')
                    ->options([
                        'mapped' => 'Eşleşmiş',
                        'unmapped' => 'Eşleşmemiş',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'mapped' => $query->whereNotNull('iha_category_code'),
                            'unmapped' => $query->whereNull('iha_category_code'),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->reorderable('sort_order', fn (): bool => self::canReorder())
            ->defaultSort('sort_order')
            ->actions([
                Action::make('view_site')
                    ->label('Sitede Gör')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Category $record): string => route('news.category', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (Category $record): bool => (bool) $record->is_active),

                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Kategoriyi sil')
                    ->modalDescription(fn (Category $record): string => self::deleteImpactDescription($record)),
            ])
            ->emptyStateIcon('heroicon-o-folder')
            ->emptyStateHeading('Bu kapsamda kategori bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni kategori oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Kategori')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_category');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_category');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_category');
    }

    public static function canDelete(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_category');
    }

    public static function canReorder(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_category');
    }

    public static function getTranslatableLocales(): array
    {
        return ['tr', 'en', 'ku'];
    }

    private static function deleteImpactDescription(Category $category): string
    {
        $primaryCount = $category->articles()->count();
        $additionalCount = $category->additionalArticles()->count();
        $childCount = $category->children()->count();
        $publicCount = $category->publicArticlesCount();

        if (($primaryCount + $additionalCount + $childCount) === 0) {
            return 'Bu kategori haberlerde veya alt kategorilerde kullanılmıyor; silme işlemi yalnız kategori kaydını kaldırır.';
        }

        return sprintf(
            'Bu kategori %d public haber, %d ana haber, %d ek haber ilişkisi ve %d alt kategoriyle bağlantılı. Silmeden önce haberleri taşıyın veya hiyerarşiyi temizleyin.',
            $publicCount,
            $primaryCount,
            $additionalCount,
            $childCount,
        );
    }
}
