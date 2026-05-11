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
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                ->options([
                    'road_status' => 'Yol Durumu',
                    'power_outage' => 'Elektrik Kesintisi',
                    'water_outage' => 'Su Kesintisi',
                    'other' => 'Diğer',
                ])
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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'road_status' => 'Yol Durumu',
                        'power_outage' => 'Elektrik Kesintisi',
                        'water_outage' => 'Su Kesintisi',
                        default => 'Diğer',
                    }),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable(),

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
                    ->options([
                        'road_status' => 'Yol Durumu',
                        'power_outage' => 'Elektrik Kesintisi',
                        'water_outage' => 'Su Kesintisi',
                        'other' => 'Diğer',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
}
