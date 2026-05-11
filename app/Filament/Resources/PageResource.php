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
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug'),

                IconColumn::make('is_published')
                    ->label('Yayında')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Page $record): bool => $record->isProtectedStaticPage()),
            ])
            ->reorderable('sort_order', fn (): bool => self::canReorder())
            ->defaultSort('sort_order');
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
}
