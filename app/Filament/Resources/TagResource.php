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
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->description(fn (Tag $record): string => $record->slug)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('articles_count')
                    ->label('Kullanım')
                    ->counts('articles')
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => ((int) $state) > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => ((int) $state) . ' haber'),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('usage_band')
                    ->label('Kullanım')
                    ->options([
                        'used' => 'Kullanımda',
                        'unused' => 'Kullanılmıyor',
                        'high' => '5+ haber',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'used' => $query->has('articles'),
                            'unused' => $query->doesntHave('articles'),
                            'high' => $query->has('articles', '>=', 5),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Etiketi sil')
                    ->modalDescription(fn (Tag $record): string => self::deleteImpactDescription($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('merge')
                        ->label('Etiketleri Birleştir')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->visible(fn (): bool => self::canMerge())
                        ->form([
                            Select::make('target_tag_id')
                                ->label('Hedef Etiket')
                                ->helperText('Hedef etiket seçili kaynak etiketlerin dışında olmalıdır; haber ilişkileri hedef etikete taşınır.')
                                ->options(fn () => Tag::query()->get()->mapWithKeys(fn ($tag) => [
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
                                Notification::make()
                                    ->danger()
                                    ->title('Hedef etiket bulunamadı')
                                    ->body('Etiket listesi güncellenmiş olabilir. Sayfayı yenileyip tekrar deneyin.')
                                    ->send();

                                return;
                            }

                            if ($records->pluck('id')->contains($targetId)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Hedef etiket seçimin dışında olmalı')
                                    ->body('Birleştirme hedefi kaynak etiketlerden biri olamaz. Hedef etiketi seçimden çıkarıp tekrar deneyin.')
                                    ->send();

                                return;
                            }

                            $sourceCount = $records->count();

                            app(TagMergeService::class)->mergeInto(
                                $targetTag,
                                $records->values()
                            );

                            Notification::make()
                                ->success()
                                ->title('Etiketler birleştirildi')
                                ->body($sourceCount . ' etiketin haber ilişkileri "' . $targetTag->getTranslation('name', 'tr') . '" etiketine taşındı.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Etiketleri Birleştir')
                        ->modalDescription('Seçilen kaynak etiketlerin haber ilişkileri hedef etikete taşınacak ve kaynak etiketler silinecek.')
                        ->modalSubmitActionLabel('Birleştir'),

                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->modalHeading('Seçili etiketleri sil')
                        ->modalDescription('Etiketleri silmeden önce haber ilişkilerini kontrol edin. Etiket birleştirme, kullanılan etiketler için daha güvenli yoldur.'),
                ])->label('Toplu İşlemler'),
            ])
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('Bu kapsamda etiket bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni etiket oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Etiket')
                    ->icon('heroicon-o-plus'),
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

    private static function deleteImpactDescription(Tag $tag): string
    {
        $count = $tag->articles()->count();

        if ($count === 0) {
            return 'Bu etiket herhangi bir haberde kullanılmıyor; silme işlemi yalnız etiket kaydını kaldırır.';
        }

        return $count . ' haber bu etiketi kullanıyor. Silme yerine birleştirme aksiyonunu kullanmak haber ilişkilerini korur.';
    }
}
