<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriptionResource\Pages;
use App\Models\NewsletterSubscription;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriptionResource extends Resource
{
    protected static ?string $model = NewsletterSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Bülten Aboneleri';
    protected static ?string $modelLabel = 'Abone';
    protected static ?string $pluralModelLabel = 'Bülten Aboneleri';
    protected static ?string $navigationGroup = 'İletişim';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('email')
                ->label('E-posta')
                ->email()
                ->required()
                ->unique(NewsletterSubscription::class, 'email', ignoreRecord: true),

            TextInput::make('name')
                ->label('Ad Soyad'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            DateTimePicker::make('confirmed_at')
                ->label('Onay Tarihi')
                ->native(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->default('—'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('confirmed_at')
                    ->label('Onay Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Abone Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktif Et')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),

                    BulkAction::make('deactivate')
                        ->label('Pasif Et')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),

                    BulkAction::make('export')
                        ->label('CSV Olarak İndir')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $stream = fopen('php://temp', 'r+');
                            fputcsv($stream, ['Email', 'Ad Soyad', 'Aktif', 'Abone Tarihi']);

                            foreach ($records as $r) {
                                fputcsv($stream, [
                                    self::escapeCsvCell($r->email),
                                    self::escapeCsvCell($r->name),
                                    $r->is_active ? 'Evet' : 'Hayır',
                                    $r->created_at->format('d.m.Y'),
                                ]);
                            }

                            rewind($stream);
                            $csv = stream_get_contents($stream);
                            fclose($stream);

                            return response()->streamDownload(
                                fn () => print($csv),
                                'newsletter-aboneleri-' . now()->format('YmdHis') . '.csv',
                                ['Content-Type' => 'text/csv']
                            );
                        }),

                    DeleteBulkAction::make()->label('Sil'),
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
            'index'  => Pages\ListNewsletterSubscriptions::route('/'),
            'create' => Pages\CreateNewsletterSubscription::route('/create'),
            'edit'   => Pages\EditNewsletterSubscription::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public static function canDelete(Model $record): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public static function canDeleteAny(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public static function escapeCsvCell(mixed $value): string
    {
        $cell = (string) $value;

        if ($cell !== '' && preg_match('/^[=+\-@\t\r]/', $cell) === 1) {
            return "'" . $cell;
        }

        return $cell;
    }
}
