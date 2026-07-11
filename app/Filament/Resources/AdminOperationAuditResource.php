<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminOperationAuditResource\Pages;
use App\Models\AdminOperationAudit;
use App\Support\AdminPrivileges;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdminOperationAuditResource extends Resource
{
    protected static ?string $model = AdminOperationAudit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Operasyon';

    protected static ?string $navigationLabel = 'Operasyon Kayıtları';

    protected static ?string $modelLabel = 'Operasyon Kaydı';

    protected static ?string $pluralModelLabel = 'Operasyon Kayıtları';

    protected static ?int $navigationSort = 95;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Zaman')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Aksiyon')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'blocked' => 'warning',
                        'simulated' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('user.email')
                    ->label('Kullanıcı')
                    ->placeholder('Sistem')
                    ->searchable(),

                TextColumn::make('summary')
                    ->label('Özet')
                    ->limit(80)
                    ->placeholder('-')
                    ->tooltip(fn (AdminOperationAudit $record): ?string => $record->summary),

                TextColumn::make('auditable_type')
                    ->label('Hedef')
                    ->formatStateUsing(fn (?string $state, AdminOperationAudit $record): string => $state
                        ? class_basename($state).' #'.$record->auditable_id
                        : '-')
                    ->toggleable(),

                TextColumn::make('path')
                    ->label('Sayfa')
                    ->limit(45)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'success' => 'Başarılı',
                        'failed' => 'Hatalı',
                        'blocked' => 'Engellendi',
                        'simulated' => 'Simülasyon',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading('Henüz operasyon kaydı yok')
            ->emptyStateDescription('Riskli admin aksiyonları çalıştıkça burada kim, ne zaman, hangi sonucu aldı bilgisi görünür.');
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
            'index' => Pages\ListAdminOperationAudits::route('/'),
        ];
    }
}
