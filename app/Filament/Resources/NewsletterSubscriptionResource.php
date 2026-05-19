<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriptionResource\Pages;
use App\Models\NewsletterSubscription;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->description(fn (NewsletterSubscription $record): string => $record->name ?: 'Ad bilgisi yok')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('subscription_status')
                    ->label('Aktiflik')
                    ->badge()
                    ->state(fn (NewsletterSubscription $record): string => $record->is_active ? 'Aktif' : 'Pasif')
                    ->color(fn (NewsletterSubscription $record): string => $record->is_active ? 'success' : 'gray')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('is_active', $direction)),

                TextColumn::make('confirmation_status')
                    ->label('Onay')
                    ->badge()
                    ->state(fn (NewsletterSubscription $record): string => $record->confirmed_at ? 'Onaylı' : 'Onay bekliyor')
                    ->color(fn (NewsletterSubscription $record): string => $record->confirmed_at ? 'success' : 'warning'),

                TextColumn::make('confirmed_at')
                    ->label('Onay Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Abonelik Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                SelectFilter::make('confirmation_state')
                    ->label('Onay Durumu')
                    ->options([
                        'confirmed' => 'Onaylı',
                        'unconfirmed' => 'Onay bekliyor',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'confirmed' => $query->whereNotNull('confirmed_at'),
                        'unconfirmed' => $query->whereNull('confirmed_at'),
                        default => $query,
                    }),

                Filter::make('created_at')
                    ->label('Abonelik Tarihi')
                    ->form([
                        DatePicker::make('created_from')->label('Başlangıç'),
                        DatePicker::make('created_until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktif Et')
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading('Seçili aboneleri aktif et')
                        ->modalDescription('Aktif edilen aboneler bülten gönderim kapsamına yeniden dahil edilir.')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->success()
                                ->title('Seçili aboneler aktif edildi')
                                ->body($records->count() . ' abone bülten kapsamına alındı.')
                                ->send();
                        }),

                    BulkAction::make('deactivate')
                        ->label('Pasif Et')
                        ->icon('heroicon-o-x-circle')
                        ->modalHeading('Seçili aboneleri pasifleştir')
                        ->modalDescription('Pasifleştirilen aboneler bülten gönderim kapsamından çıkarılır; kayıtlar silinmez.')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->warning()
                                ->title('Seçili aboneler pasifleştirildi')
                                ->body($records->count() . ' abone bülten kapsamından çıkarıldı.')
                                ->send();
                        }),

                    BulkAction::make('export')
                        ->label('CSV Olarak İndir')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->modalHeading('Seçili aboneleri CSV olarak indir')
                        ->modalDescription('Yalnız seçili kayıtlar indirilir. CSV hücreleri formül enjeksiyonuna karşı güvenli hale getirilir; unsubscribe token dışa aktarılmaz.')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $stream = fopen('php://temp', 'r+');
                            fputcsv($stream, ['E-posta', 'Ad Soyad', 'Aktif', 'Onay Tarihi', 'Abonelik Tarihi']);

                            foreach ($records as $record) {
                                fputcsv($stream, [
                                    self::escapeCsvCell($record->email),
                                    self::escapeCsvCell($record->name),
                                    $record->is_active ? 'Evet' : 'Hayır',
                                    $record->confirmed_at?->format('d.m.Y H:i') ?? '',
                                    $record->created_at?->format('d.m.Y H:i') ?? '',
                                ]);
                            }

                            rewind($stream);
                            $csv = stream_get_contents($stream);
                            fclose($stream);

                            return response()->streamDownload(
                                fn () => print($csv),
                                'newsletter-aboneleri-' . now()->format('YmdHis') . '.csv',
                                ['Content-Type' => 'text/csv; charset=UTF-8']
                            );
                        }),

                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->modalHeading('Seçili aboneleri sil')
                        ->modalDescription('Silinen aboneler geri alınamaz. Gönderim dışı bırakmak için silmek yerine pasifleştirme aksiyonunu kullanın.'),
                ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Bülten abonesini sil')
                    ->modalDescription('Bu işlem abonelik kaydını kalıcı olarak kaldırır. Gönderim dışı bırakmak için pasifleştirme daha güvenli seçenektir.'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateHeading('Bu kapsamda bülten abonesi bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni abone kaydı oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Abone')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscriptions::route('/'),
            'create' => Pages\CreateNewsletterSubscription::route('/create'),
            'edit' => Pages\EditNewsletterSubscription::route('/{record}/edit'),
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
