<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialPublicationResource\Pages;
use App\Models\SocialPublication;
use App\Services\SocialPublicationService;
use App\Support\AdminOperationAuditor;
use App\Support\AdminPrivileges;
use App\Support\AdminSafeText;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->label('Görsel')
                    ->square()
                    ->size(56),

                TextColumn::make('article.title')
                    ->label('Haber')
                    ->limit(55)
                    ->searchable()
                    ->formatStateUsing(fn (SocialPublication $record): string => $record->article?->getTranslation('title', 'tr') ?? '-')
                    ->description(fn (SocialPublication $record): string => $record->article?->slug ?? 'Haber bağlantısı yok')
                    ->url(fn (SocialPublication $record): ?string => $record->article ? NewsArticleResource::getUrl('edit', ['record' => $record->article]) : null),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state)),

                TextColumn::make('creative_state')
                    ->label('Creative')
                    ->badge()
                    ->state(fn (SocialPublication $record): string => filled($record->creative_image_url) ? 'Hazır' : 'Eksik')
                    ->color(fn (SocialPublication $record): string => filled($record->creative_image_url) ? 'success' : 'warning'),

                TextColumn::make('attempts')
                    ->label('Deneme')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('media_id')
                    ->label('Media ID')
                    ->copyable()
                    ->limit(24)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('container_id')
                    ->label('Container ID')
                    ->copyable()
                    ->limit(24)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('caption')
                    ->label('Açıklama')
                    ->limit(70)
                    ->tooltip(fn (SocialPublication $record): ?string => $record->caption)
                    ->toggleable(),

                TextColumn::make('error_message')
                    ->label('Hata Özeti')
                    ->formatStateUsing(fn (?string $state): string => AdminSafeText::limit($state, 60) ?: '-')
                    ->tooltip(fn (SocialPublication $record): string => AdminSafeText::redact($record->error_message))
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Yayın Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
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

                SelectFilter::make('creative_state')
                    ->label('Creative Durumu')
                    ->options([
                        'ready' => 'Creative hazır',
                        'missing' => 'Creative eksik',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'ready' => $query->whereNotNull('creative_image_url')->where('creative_image_url', '!=', ''),
                        'missing' => $query->where(function (Builder $query): void {
                            $query->whereNull('creative_image_url')->orWhere('creative_image_url', '');
                        }),
                        default => $query,
                    }),

                Filter::make('attempts')
                    ->label('Deneme Sayısı')
                    ->form([
                        TextInput::make('min_attempts')
                            ->label('En az')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['min_attempts'] ?? null, fn (Builder $query, string|int $attempts): Builder => $query->where('attempts', '>=', (int) $attempts))),

                Filter::make('updated_at')
                    ->label('Güncelleme Tarihi')
                    ->form([
                        DatePicker::make('updated_from')->label('Başlangıç'),
                        DatePicker::make('updated_until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['updated_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('updated_at', '>=', $date))
                        ->when($data['updated_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('updated_at', '<=', $date))),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions([
                Action::make('retry')
                    ->label('Tekrar Dene')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (SocialPublication $record): bool => in_array($record->status, [
                        SocialPublication::STATUS_FAILED,
                        SocialPublication::STATUS_SKIPPED,
                        SocialPublication::STATUS_PENDING,
                    ], true))
                    ->modalHeading('Instagram paylaşımını yeniden kuyruğa al')
                    ->modalDescription('Bu işlem paylaşımı pending durumuna alır ve job kuyruğuna gönderir. Doğrudan paylaşım garantisi vermez; sonuç için durum ve hata alanlarını takip edin.')
                    ->modalSubmitActionLabel('Kuyruğa Al')
                    ->requiresConfirmation()
                    ->action(function (SocialPublication $record): void {
                        app(SocialPublicationService::class)->retry($record);

                        AdminOperationAuditor::record(
                            'instagram.publication_retry',
                            $record,
                            ['article_id' => $record->news_article_id ?? $record->article_id],
                            'simulated',
                            'Instagram paylaşımı tekrar kuyruğa alındı'
                        );

                        Notification::make()
                            ->success()
                            ->title('Instagram paylaşımı yeniden kuyruğa alındı')
                            ->body('Paylaşım pending durumuna alındı. Queue worker çalışıyorsa işlem arka planda ilerler.')
                            ->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading('Bu kapsamda Instagram paylaşımı bulunamadı')
            ->emptyStateDescription('Filtreleri genişletin veya haber yayınlama akışının Instagram otomasyonu üretip üretmediğini kontrol edin.');
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canManageOperations(auth()->user());
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

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            SocialPublication::STATUS_PENDING => 'Bekliyor',
            SocialPublication::STATUS_PROCESSING => 'İşleniyor',
            SocialPublication::STATUS_PUBLISHED => 'Yayınlandı',
            SocialPublication::STATUS_FAILED => 'Hatalı',
            SocialPublication::STATUS_SKIPPED => 'Atlandı',
            default => (string) $status,
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            SocialPublication::STATUS_PENDING => 'gray',
            SocialPublication::STATUS_PROCESSING => 'info',
            SocialPublication::STATUS_PUBLISHED => 'success',
            SocialPublication::STATUS_FAILED => 'danger',
            SocialPublication::STATUS_SKIPPED => 'warning',
            default => 'gray',
        };
    }
}
