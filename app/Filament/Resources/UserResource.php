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
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
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
                    DeleteBulkAction::make()
                        ->label('Sil')
                        ->action(function (Collection $records): void {
                            if ($records->contains(fn (User $user): bool => self::isProtectedAccountMutation($user))) {
                                Notification::make()
                                    ->danger()
                                    ->title('Kendi hesabınız veya son aktif super_admin silinemez')
                                    ->send();

                                return;
                            }

                            $records->each(fn (Model $record): ?bool => $record->delete());
                        }),
                ]),
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
}
