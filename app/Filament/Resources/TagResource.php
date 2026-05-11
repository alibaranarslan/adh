<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use App\Services\TagMergeService;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    use Translatable;

    protected static ?string $model = Tag::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Etiketler';
    protected static ?string $modelLabel = 'Etiket';
    protected static ?string $pluralModelLabel = 'Etiketler';
    protected static ?int $navigationSort = 3;

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
                ->unique(Tag::class, 'slug', ignoreRecord: true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'tr'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('articles_count')
                    ->label('Kullanım Sayısı')
                    ->counts('articles')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('merge')
                        ->label('Etiketleri Birleştir')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->visible(fn (): bool => self::canMerge())
                        ->form([
                            Select::make('target_tag_id')
                                ->label('Hedef Etiket (tüm etiketler bu etikete birleştirilir)')
                                ->options(fn () => Tag::all()->mapWithKeys(fn ($tag) => [
                                    $tag->id => $tag->getTranslation('name', 'tr'),
                                ]))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            if (! self::canMerge()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Bu işlem için yetkiniz yok')
                                    ->send();

                                return;
                            }

                            $targetId  = (int) $data['target_tag_id'];
                            $targetTag = Tag::query()->find($targetId);

                            if (! $targetTag) {
                                return;
                            }

                            app(TagMergeService::class)->mergeInto(
                                $targetTag,
                                $records->filter(fn (Tag $tag) => $tag->id !== $targetId)->values()
                            );
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Etiketleri Birleştir')
                        ->modalDescription('Seçilen etiketler hedef etikete birleştirilecek ve kaynak etiketler silinecek.'),

                    DeleteBulkAction::make()->label('Sil'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_tag');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_tag');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_tag');
    }

    public static function canDelete(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_tag');
    }

    public static function canDeleteAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_tag');
    }

    public static function canMerge(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_tag')
            && AdminPrivileges::hasPermission(auth()->user(), 'delete_tag');
    }

    public static function getTranslatableLocales(): array
    {
        return ['tr', 'en', 'ku'];
    }
}
