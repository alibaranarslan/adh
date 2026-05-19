<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\AdminPrivileges;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Kullanıcılar';
    protected static ?string $modelLabel = 'Kullanıcı';
    protected static ?string $pluralModelLabel = 'Kullanıcılar';
    protected static ?string $navigationGroup = 'Yönetim';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canEdit($record): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canDelete($record): bool
    {
        if ($record instanceof User && self::isProtectedAccountMutation($record)) {
            return false;
        }

        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function canDeleteAny(): bool
    {
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Kullanıcı Bilgileri')->schema([
                TextInput::make('name')
                    ->label('Ad Soyad')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-posta')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),

                TextInput::make('password')
                    ->label('Şifre')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText('Değiştirmek istemiyorsanız boş bırakın'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record instanceof User && self::isProtectedAccountMutation($record))
                    ->dehydrated(fn (?User $record): bool => ! ($record instanceof User && self::isProtectedAccountMutation($record))),
            ])->columns(2),

            Section::make('Roller & Yetkiler')->schema([
                Select::make('roles')
                    ->label('Roller')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->disabled(fn (?User $record): bool => $record instanceof User && self::isProtectedAccountMutation($record))
                    ->dehydrated(fn (?User $record): bool => ! ($record instanceof User && self::isProtectedAccountMutation($record))),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Roller')
                    ->badge()
                    ->separator(','),

                TextColumn::make('account_guard')
                    ->label('Koruma')
                    ->badge()
                    ->state(fn (User $record): string => self::accountGuardLabel($record))
                    ->color(fn (User $record): string => self::accountGuardColor($record)),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Son Giriş')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Hiç giriş yapılmadı'),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                SelectFilter::make('last_login_state')
                    ->label('Son Giriş')
                    ->options([
                        'never' => 'Hiç giriş yapmadı',
                        'recent' => 'Son 30 gün',
                        'stale' => '30 günden eski',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'never' => $query->whereNull('last_login_at'),
                            'recent' => $query->where('last_login_at', '>=', now()->subDays(30)),
                            'stale' => $query->where('last_login_at', '<', now()->subDays(30)),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Kullanıcıyı sil')
                    ->modalDescription(fn (User $record): string => self::deleteImpactDescription($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->modalHeading('Seçili kullanıcıları sil')
                        ->modalDescription('Kendi hesabınız veya son aktif süper admin seçime dahilse işlem iptal edilir. Bu koruma yönetim erişiminin kilitlenmesini önler.')
                        ->action(function (Collection $records): void {
                            if ($records->contains(fn (User $user): bool => self::isProtectedAccountMutation($user))) {
                                Notification::make()
                                    ->danger()
                                    ->title('Korumalı kullanıcı seçildi')
                                    ->body('Kendi hesabınız veya sistemdeki son aktif süper admin silinemez. Seçimi daraltıp tekrar deneyin.')
                                    ->send();

                                return;
                            }

                            $records->each(fn (Model $record): ?bool => $record->delete());

                            Notification::make()
                                ->success()
                                ->title('Kullanıcılar silindi')
                                ->body($records->count() . ' kullanıcı kaydı silindi.')
                                ->send();
                        }),
                ])->label('Toplu İşlemler'),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Bu kapsamda kullanıcı bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni kullanıcı oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Kullanıcı')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function isProtectedAccountMutation(User $user): bool
    {
        return self::isSelf($user) || self::isLastActiveSuperAdmin($user);
    }

    private static function isSelf(User $user): bool
    {
        return auth()->id() !== null && (int) auth()->id() === (int) $user->getKey();
    }

    private static function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->exists || ! $user->is_active || ! $user->hasRole('super_admin')) {
            return false;
        }

        return ! User::role('super_admin')
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    private static function accountGuardLabel(User $user): string
    {
        if (self::isSelf($user)) {
            return 'Kendi hesabınız';
        }

        if (self::isLastActiveSuperAdmin($user)) {
            return 'Son süper admin';
        }

        return 'Standart';
    }

    private static function accountGuardColor(User $user): string
    {
        return self::isProtectedAccountMutation($user) ? 'warning' : 'gray';
    }

    private static function deleteImpactDescription(User $user): string
    {
        if (self::isSelf($user)) {
            return 'Kendi oturumunuzun bağlı olduğu kullanıcı silinemez. Bu koruma panel erişiminizin kapanmasını önler.';
        }

        if (self::isLastActiveSuperAdmin($user)) {
            return 'Bu kullanıcı sistemdeki son aktif süper admin olduğu için silinemez. Önce başka bir aktif süper admin oluşturun.';
        }

        return 'Kullanıcı silindiğinde panel erişimi kaldırılır; içerik geçmişi kayıt sahibini referans göstermeye devam edebilir.';
    }
}
