<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocalInfoEntryResource\Pages;
use App\Models\LocalInfoEntry;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LocalInfoEntryResource extends Resource
{
    protected static ?string $model = LocalInfoEntry::class;
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Yerel Bilgiler';
    protected static ?string $modelLabel = 'Yerel Bilgi';
    protected static ?string $pluralModelLabel = 'Yerel Bilgiler';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('type')
                ->label('Tür')
                ->options(self::typeOptions())
                ->required(),

            TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->maxLength(255),

            Textarea::make('content')
                ->label('Detay')
                ->rows(4)
                ->columnSpanFull(),

            DateTimePicker::make('starts_at')
                ->label('Başlangıç Zamanı')
                ->native(false),

            DateTimePicker::make('ends_at')
                ->label('Bitiş Zamanı')
                ->native(false),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('type')
                    ->label('Tür')
                    ->colors([
                        'warning' => 'road_status',
                        'danger' => 'power_outage',
                        'info' => 'water_outage',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn ($state) => self::typeOptions()[$state] ?? 'Diğer'),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('publication_status')
                    ->label('Yayın Durumu')
                    ->badge()
                    ->state(fn (LocalInfoEntry $record): string => self::statusLabel($record))
                    ->color(fn (LocalInfoEntry $record): string => self::statusColor($record)),

                TextColumn::make('starts_at')
                    ->label('Başlangıç')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(self::typeOptions()),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                SelectFilter::make('publication_status')
                    ->label('Yayın Durumu')
                    ->options([
                        'current' => 'Şu an yayında',
                        'scheduled' => 'Planlandı',
                        'expired' => 'Süresi doldu',
                        'passive' => 'Pasif',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyStatusFilter($query, $data['value'] ?? null)),

                Filter::make('starts_at')
                    ->label('Başlangıç Zamanı')
                    ->form([
                        DateTimePicker::make('from')->label('Başlangıç'),
                        DateTimePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->where('starts_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->where('starts_at', '<=', $date))),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->actions([
                Action::make('deactivate')
                    ->label('Pasifleştir')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Yerel bilgiyi pasifleştir')
                    ->modalDescription('Pasifleştirilen kayıt public yerel bilgi alanlarında görünmez.')
                    ->visible(fn (LocalInfoEntry $record): bool => (bool) $record->is_active && self::canEdit($record))
                    ->action(function (LocalInfoEntry $record): void {
                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->success()
                            ->title('Yerel bilgi pasifleştirildi')
                            ->send();
                    }),

                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Yerel bilgiyi sil')
                    ->modalDescription(fn (LocalInfoEntry $record): string => 'Bu kayıt silindiğinde public yerel bilgi alanlarından kaldırılır. Mevcut durum: ' . self::statusLabel($record) . '.'),
            ])
            ->emptyStateIcon('heroicon-o-information-circle')
            ->emptyStateHeading('Bu kapsamda yerel bilgi bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni yerel bilgi oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Bilgi')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalInfoEntries::route('/'),
            'create' => Pages\CreateLocalInfoEntry::route('/create'),
            'edit' => Pages\EditLocalInfoEntry::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_local_info_entry');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_local_info_entry');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_local_info_entry');
    }

    public static function canDelete(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_local_info_entry');
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            'road_status' => 'Yol Durumu',
            'power_outage' => 'Elektrik Kesintisi',
            'water_outage' => 'Su Kesintisi',
            'other' => 'Diğer',
        ];
    }

    private static function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'current' => $query
                ->where('is_active', true)
                ->where(function (Builder $query) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function (Builder $query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                }),
            'scheduled' => $query->where('is_active', true)->where('starts_at', '>', now()),
            'expired' => $query->where('is_active', true)->whereNotNull('ends_at')->where('ends_at', '<', now()),
            'passive' => $query->where('is_active', false),
            default => $query,
        };
    }

    private static function statusLabel(LocalInfoEntry $entry): string
    {
        if (! $entry->is_active) {
            return 'Pasif';
        }

        if ($entry->starts_at && $entry->starts_at->isFuture()) {
            return 'Planlandı';
        }

        if ($entry->ends_at && $entry->ends_at->isPast()) {
            return 'Süresi doldu';
        }

        return 'Şu an yayında';
    }

    private static function statusColor(LocalInfoEntry $entry): string
    {
        return match (self::statusLabel($entry)) {
            'Şu an yayında' => 'success',
            'Planlandı' => 'info',
            'Süresi doldu', 'Pasif' => 'gray',
            default => 'warning',
        };
    }
}
