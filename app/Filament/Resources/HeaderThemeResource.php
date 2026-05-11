<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeaderThemeResource\Pages;
use App\Models\HeaderTheme;
use App\Support\AdminPrivileges;
use App\Support\HeaderThemeResolver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HeaderThemeResource extends Resource
{
    use Translatable;

    protected static ?string $model = HeaderTheme::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Görünüm';
    protected static ?string $navigationLabel = 'Milli Gün Temaları';
    protected static ?string $modelLabel = 'Milli gün teması';
    protected static ?string $pluralModelLabel = 'Milli gün temaları';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Temel bilgiler')
                ->description('Bu kayıtlar header ve masthead üzerinde kontrollü milli gün/bayram görünümünü yönetir.')
                ->schema([
                    TextInput::make('name')
                        ->label('Tema adı')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('slug')
                        ->label('Sistem anahtarı')
                        ->required()
                        ->maxLength(120)
                        ->unique(HeaderTheme::class, 'slug', ignoreRecord: true)
                        ->helperText('Atatürk öğesi yalnız belirli anahtarlarda açılabilir.'),
                    Select::make('mode')
                        ->label('Çalışma modu')
                        ->options(HeaderTheme::modeOptions())
                        ->required()
                        ->default(HeaderTheme::MODE_AUTOMATIC),
                    Toggle::make('is_enabled')
                        ->label('Tema kullanılabilir')
                        ->default(true),
                    TextInput::make('priority')
                        ->label('Öncelik')
                        ->numeric()
                        ->default(100),
                    Textarea::make('banner_message')
                        ->label('Üst duyuru mesajı')
                        ->rows(3)
                        ->maxLength(320),
                ])->columns(2),

            Section::make('Takvim kuralı')
                ->schema([
                    Select::make('theme_type')
                        ->label('Tarih kuralı')
                        ->options(HeaderTheme::typeOptions())
                        ->required()
                        ->live(),
                    Select::make('month')
                        ->label('Ay')
                        ->options([
                            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
                            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
                        ])
                        ->required(fn (Get $get): bool => in_array($get('theme_type'), [HeaderTheme::TYPE_FIXED, HeaderTheme::TYPE_NTH_WEEKDAY], true))
                        ->visible(fn (Get $get) => in_array($get('theme_type'), [HeaderTheme::TYPE_FIXED, HeaderTheme::TYPE_NTH_WEEKDAY], true)),
                    TextInput::make('day')
                        ->label('Gün')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(31)
                        ->required(fn (Get $get): bool => $get('theme_type') === HeaderTheme::TYPE_FIXED)
                        ->visible(fn (Get $get) => $get('theme_type') === HeaderTheme::TYPE_FIXED),
                    Select::make('weekday')
                        ->label('Haftanın günü')
                        ->options([0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi'])
                        ->required(fn (Get $get): bool => $get('theme_type') === HeaderTheme::TYPE_NTH_WEEKDAY)
                        ->visible(fn (Get $get) => $get('theme_type') === HeaderTheme::TYPE_NTH_WEEKDAY),
                    Select::make('nth_week')
                        ->label('Hafta sırası')
                        ->options([1 => 'İlk', 2 => 'İkinci', 3 => 'Üçüncü', 4 => 'Dördüncü', -1 => 'Son'])
                        ->required(fn (Get $get): bool => $get('theme_type') === HeaderTheme::TYPE_NTH_WEEKDAY)
                        ->visible(fn (Get $get) => $get('theme_type') === HeaderTheme::TYPE_NTH_WEEKDAY),
                    DatePicker::make('starts_at')
                        ->label('Başlangıç tarihi')
                        ->required(fn (Get $get): bool => $get('theme_type') === HeaderTheme::TYPE_RANGE)
                        ->visible(fn (Get $get) => $get('theme_type') === HeaderTheme::TYPE_RANGE),
                    DatePicker::make('ends_at')
                        ->label('Bitiş tarihi')
                        ->required(fn (Get $get): bool => $get('theme_type') === HeaderTheme::TYPE_RANGE)
                        ->afterOrEqual('starts_at')
                        ->visible(fn (Get $get) => $get('theme_type') === HeaderTheme::TYPE_RANGE),
                ])->columns(3),

            Section::make('Görsel tavır')
                ->schema([
                    Select::make('style_variant')
                        ->label('Stil')
                        ->options(HeaderTheme::styleVariantOptions())
                        ->default('national'),
                    Select::make('illustration_mode')
                        ->label('İllüstrasyon tipi')
                        ->options(HeaderTheme::illustrationModeOptions())
                        ->default('inline_svg')
                        ->live(),
                    TextInput::make('illustration_asset')
                        ->label('Görsel anahtarı / yolu')
                        ->visible(fn (Get $get) => $get('illustration_mode') !== 'none'),
                    Select::make('decor_intensity')
                        ->label('Yoğunluk')
                        ->options(HeaderTheme::decorIntensityOptions())
                        ->default('medium'),
                    Toggle::make('show_flag')
                        ->label('Türk bayrağı aksanı')
                        ->default(true),
                    Toggle::make('show_ataturk')
                        ->label('Atatürk öğesi')
                        ->helperText('Yalnız 19 Mayıs, 30 Ağustos ve 10 Kasım temalarında açılabilir.')
                        ->disabled(fn (Get $get) => ! HeaderTheme::allowsAtaturkForSlug($get('slug')))
                        ->dehydrateStateUsing(fn ($state, Get $get) => HeaderTheme::allowsAtaturkForSlug($get('slug')) ? (bool) $state : false),
                    Textarea::make('notes')
                        ->label('Notlar')
                        ->rows(2)
                        ->columnSpanFull(),
                    Placeholder::make('preview_hint')
                        ->label('Önizleme')
                        ->content('Kayıt listesindeki “Önizle” aksiyonu locale ve tarih seçerek signed preview açar.')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(HeaderTheme::query()->forSite()->orderByDesc('priority'))
            ->columns([
                TextColumn::make('name')
                    ->label('Tema')
                    ->formatStateUsing(fn (HeaderTheme $record) => $record->translatedName('tr'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Anahtar')
                    ->badge(),
                TextColumn::make('schedule')
                    ->label('Takvim')
                    ->state(fn (HeaderTheme $record) => $record->scheduleLabel())
                    ->wrap(),
                TextColumn::make('mode')
                    ->label('Mod')
                    ->formatStateUsing(fn (string $state) => HeaderTheme::modeOptions()[$state] ?? $state),
                IconColumn::make('show_flag')
                    ->label('Bayrak')
                    ->boolean(),
                IconColumn::make('show_ataturk')
                    ->label('Atatürk')
                    ->boolean(),
                IconColumn::make('is_enabled')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-eye')
                    ->form([
                        Select::make('locale')
                            ->label('Dil')
                            ->options(['tr' => 'Türkçe', 'en' => 'English', 'ku' => 'Kurdî'])
                            ->default('tr')
                            ->required(),
                        DatePicker::make('preview_date')
                            ->label('Simüle edilecek tarih')
                            ->default(fn (HeaderTheme $record) => $record->previewDate()->toDateString())
                            ->required(),
                    ])
                    ->action(function (HeaderTheme $record, array $data) {
                        return redirect(app(HeaderThemeResolver::class)->getPreviewUrl(
                            $record,
                            $data['locale'] ?? 'tr',
                            $data['preview_date'] ?? null,
                        ));
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeaderThemes::route('/'),
            'create' => Pages\CreateHeaderTheme::route('/create'),
            'edit' => Pages\EditHeaderTheme::route('/{record}/edit'),
        ];
    }

    public static function getTranslatableLocales(): array
    {
        return ['tr', 'en', 'ku'];
    }

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
        return AdminPrivileges::canManageSystemSettings(auth()->user());
    }
}
