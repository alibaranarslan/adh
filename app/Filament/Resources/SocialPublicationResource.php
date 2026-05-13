<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialPublicationResource\Pages;
use App\Models\SocialPublication;
use App\Services\SocialPublicationService;
use App\Support\AdminPrivileges;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SocialPublicationResource extends Resource
{
    protected static ?string $model = SocialPublication::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Operasyon';
    protected static ?string $navigationLabel = 'Instagram Paylaşımları';
    protected static ?string $modelLabel = 'Instagram Paylaşımı';
    protected static ?string $pluralModelLabel = 'Instagram Paylaşımları';
    protected static ?int $navigationSort = 34;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('creative_image_url')
                    ->label('Creative')
                    ->square()
                    ->size(56),
                TextColumn::make('article.title')
                    ->label('Haber')
                    ->limit(55)
                    ->searchable()
                    ->formatStateUsing(fn ($record): string => $record->article?->getTranslation('title', 'tr') ?? '-')
                    ->url(fn ($record): ?string => $record->article ? NewsArticleResource::getUrl('edit', ['record' => $record->article]) : null),
                BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'gray' => SocialPublication::STATUS_PENDING,
                        'info' => SocialPublication::STATUS_PROCESSING,
                        'success' => SocialPublication::STATUS_PUBLISHED,
                        'danger' => SocialPublication::STATUS_FAILED,
                        'warning' => SocialPublication::STATUS_SKIPPED,
                    ])
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        SocialPublication::STATUS_PENDING => 'Bekliyor',
                        SocialPublication::STATUS_PROCESSING => 'İşleniyor',
                        SocialPublication::STATUS_PUBLISHED => 'Yayınlandı',
                        SocialPublication::STATUS_FAILED => 'Hatalı',
                        SocialPublication::STATUS_SKIPPED => 'Atlandı',
                        default => (string) $state,
                    }),
                TextColumn::make('attempts')
                    ->label('Deneme')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('media_id')
                    ->label('Media ID')
                    ->copyable()
                    ->limit(24),
                TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(70)
                    ->tooltip(fn ($record): ?string => $record->caption),
                TextColumn::make('error_message')
                    ->label('Hata')
                    ->limit(60)
                    ->tooltip(fn ($record): ?string => $record->error_message),
                TextColumn::make('published_at')
                    ->label('Yayın')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Güncelleme')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        SocialPublication::STATUS_PENDING => 'Bekliyor',
                        SocialPublication::STATUS_PROCESSING => 'İşleniyor',
                        SocialPublication::STATUS_PUBLISHED => 'Yayınlandı',
                        SocialPublication::STATUS_FAILED => 'Hatalı',
                        SocialPublication::STATUS_SKIPPED => 'Atlandı',
                    ]),
            ])
            ->actions([
                Action::make('retry')
                    ->label('Tekrar Dene')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (SocialPublication $record): bool => in_array($record->status, [
                        SocialPublication::STATUS_FAILED,
                        SocialPublication::STATUS_SKIPPED,
                        SocialPublication::STATUS_PENDING,
                    ], true))
                    ->requiresConfirmation()
                    ->action(function (SocialPublication $record): void {
                        app(SocialPublicationService::class)->retry($record);

                        Notification::make()
                            ->success()
                            ->title('Instagram paylaşımı yeniden kuyruğa alındı')
                            ->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('60s');
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialPublications::route('/'),
        ];
    }
}
