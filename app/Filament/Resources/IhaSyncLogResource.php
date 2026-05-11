<?php

namespace App\Filament\Resources;

use App\Filament\Pages\IhaHealth;
use App\Filament\Resources\IhaSyncLogResource\Pages;
use App\Models\IhaSyncLog;
use App\Services\IhaSyncTriggerService;
use App\Support\AdminPrivileges;
use App\Support\AdminSafeText;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class IhaSyncLogResource extends Resource
{
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
                TextColumn::make('started_at')
                    ->label('Baslangic')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Bitis')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'warning' => 'partial',
                        'info' => 'running',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'success' => 'Basarili',
                        'failed' => 'Hatali',
                        'partial' => 'Kismi',
                        'running' => 'Calisiyor',
                        default => $state,
                    }),
                TextColumn::make('articles_fetched')
                    ->label('Cekilen')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('articles_created')
                    ->label('Yeni')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('articles_updated')
                    ->label('Guncellenen')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('articles_skipped')
                    ->label('Atlanan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('images_downloaded')
                    ->label('Gorseller')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Hata')
                    ->formatStateUsing(fn ($state): string => AdminSafeText::redact($state))
                    ->limit(70)
                    ->tooltip(fn ($record): string => AdminSafeText::redact($record->error_message)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'success' => 'Basarili',
                        'failed' => 'Hatali',
                        'partial' => 'Kismi',
                        'running' => 'Calisiyor',
                    ]),
            ])
            ->headerActions([
                Action::make('health')
                    ->label('IHA Sagligi')
                    ->icon('heroicon-o-signal')
                    ->color('gray')
                    ->url(IhaHealth::getUrl(panel: 'admin')),
                Action::make('manual_sync')
                    ->label('Manuel Senkronizasyon Baslat')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
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
            ->defaultSort('started_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIhaSyncLogs::route('/'),
        ];
    }
}
