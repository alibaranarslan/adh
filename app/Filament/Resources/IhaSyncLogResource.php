<?php

namespace App\Filament\Resources;

use App\Filament\Pages\IhaHealth;
use App\Filament\Resources\IhaSyncLogResource\Pages;
use App\Models\IhaSyncLog;
use App\Services\IhaSyncTriggerService;
use App\Support\AdminPrivileges;
use App\Support\AdminSafeText;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IhaSyncLogResource extends Resource
{
    private const STALE_RUNNING_MINUTES = 120;

    protected static ?string $model = IhaSyncLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Operasyon';
    protected static ?string $navigationLabel = 'İHA Senkron Kayıtları';
    protected static ?string $modelLabel = 'İHA Senkron Kaydı';
    protected static ?string $pluralModelLabel = 'İHA Senkron Kayıtları';
    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state)),

                TextColumn::make('started_at')
                    ->label('Başlangıç')
                    ->dateTime('d.m.Y H:i:s')
                    ->description(fn (IhaSyncLog $record): string => $record->started_at?->diffForHumans() ?? 'Başlangıç yok')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('duration')
                    ->label('Süre')
                    ->state(fn (IhaSyncLog $record): string => self::durationLabel($record)),

                TextColumn::make('runtime_risk')
                    ->label('Operasyon Riski')
                    ->badge()
                    ->state(fn (IhaSyncLog $record): string => self::riskLabel($record))
                    ->color(fn (IhaSyncLog $record): string => self::riskColor($record)),

                TextColumn::make('articles_fetched')
                    ->label('Çekilen')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('article_changes')
                    ->label('Yeni / Güncel')
                    ->state(fn (IhaSyncLog $record): string => number_format((int) $record->articles_created) . ' / ' . number_format((int) $record->articles_updated)),

                TextColumn::make('articles_skipped')
                    ->label('Atlanan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('images_downloaded')
                    ->label('Görseller')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('error_message')
                    ->label('Hata Özeti')
                    ->formatStateUsing(fn (?string $state): string => AdminSafeText::limit($state, 70) ?: '-')
                    ->tooltip(fn (IhaSyncLog $record): string => AdminSafeText::redact($record->error_message))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'success' => 'Başarılı',
                        'failed' => 'Hatalı',
                        'partial' => 'Kısmi',
                        'running' => 'Çalışıyor',
                    ]),

                SelectFilter::make('risk_scope')
                    ->label('Operasyon Riski')
                    ->options([
                        'failed_partial' => 'Hatalı veya kısmi',
                        'running' => 'Çalışan kayıtlar',
                        'stale_running' => 'Bayat çalışan kayıtlar',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'failed_partial' => $query->whereIn('status', ['failed', 'partial']),
                        'running' => $query->where('status', 'running'),
                        'stale_running' => $query->where('status', 'running')->where('started_at', '<=', now()->subMinutes(self::STALE_RUNNING_MINUTES)),
                        default => $query,
                    }),

                Filter::make('started_at')
                    ->label('Başlangıç Tarihi')
                    ->form([
                        DatePicker::make('started_from')->label('Başlangıç'),
                        DatePicker::make('started_until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['started_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('started_at', '>=', $date))
                        ->when($data['started_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('started_at', '<=', $date))),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->headerActions([
                Action::make('health')
                    ->label('İHA Sağlığı')
                    ->icon('heroicon-o-signal')
                    ->color('gray')
                    ->url(IhaHealth::getUrl(panel: 'admin')),

                Action::make('manual_sync')
                    ->label('Manuel Senkronizasyon Başlat')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->modalHeading('İHA manuel senkronizasyonu başlat')
                    ->modalDescription('Bu aksiyon mevcut iha:sync akışını kuyruğa alır. Kayıt hemen running kalabilir; sonuç için bu tabloyu ve İHA Sağlığı ekranını kontrol edin.')
                    ->modalSubmitActionLabel('Senkronu Başlat')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $result = app(IhaSyncTriggerService::class)->triggerQueued();

                        $notification = Notification::make()
                            ->title($result['title'])
                            ->body($result['body']);

                        match ($result['status']) {
                            'success' => $notification->success(),
                            'partial', 'skipped' => $notification->warning(),
                            'failed' => $notification->danger(),
                            default => $notification->info(),
                        };

                        $notification->send();
                    }),
            ])
            ->defaultSort('started_at', 'desc')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Bu kapsamda İHA senkron kaydı bulunamadı')
            ->emptyStateDescription('Filtreleri genişletin veya İHA Sağlığı ekranından son çalışma durumunu kontrol edin.');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canManageOperations(auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIhaSyncLogs::route('/'),
        ];
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            'success' => 'Başarılı',
            'failed' => 'Hatalı',
            'partial' => 'Kısmi',
            'running' => 'Çalışıyor',
            default => (string) $status,
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'failed' => 'danger',
            'partial' => 'warning',
            'running' => 'info',
            default => 'gray',
        };
    }

    private static function durationLabel(IhaSyncLog $record): string
    {
        if (! $record->started_at) {
            return 'Bilinmiyor';
        }

        $end = $record->completed_at ?? now();
        $seconds = max(0, $record->started_at->diffInSeconds($end));

        if ($seconds < 60) {
            return $seconds . ' sn';
        }

        return (int) floor($seconds / 60) . ' dk ' . ($seconds % 60) . ' sn';
    }

    private static function riskLabel(IhaSyncLog $record): string
    {
        if ($record->status === 'failed') {
            return 'Riskli';
        }

        if ($record->status === 'partial') {
            return 'Kontrol gerekli';
        }

        if ($record->status === 'running' && $record->started_at?->lte(now()->subMinutes(self::STALE_RUNNING_MINUTES))) {
            return 'Bayat running';
        }

        if ($record->status === 'running') {
            return 'İşlem bekliyor';
        }

        return 'Hazır';
    }

    private static function riskColor(IhaSyncLog $record): string
    {
        return match (self::riskLabel($record)) {
            'Riskli', 'Bayat running' => 'danger',
            'Kontrol gerekli', 'İşlem bekliyor' => 'warning',
            default => 'success',
        };
    }
}
