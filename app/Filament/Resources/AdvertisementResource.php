<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use App\Models\Setting;
use App\Support\AdminImageUploads;
use App\Support\AdminPrivileges;
use App\Support\AdvertisementPlacement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?string $navigationLabel = 'Reklamlar';
    protected static ?string $modelLabel = 'Reklam';
    protected static ?string $pluralModelLabel = 'Reklamlar';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Reklam Adı')
                ->helperText('Operasyonel takip için reklam veren, kampanya ve slot bilgisini içeren kısa bir ad kullanın.')
                ->required()
                ->maxLength(255),

            Select::make('position')
                ->label('Pozisyon')
                ->helperText('Reklam yalnızca seçilen public slotta görünür.')
                ->options(AdvertisementPlacement::options())
                ->required()
                ->live(),

            Placeholder::make('placement_guidance')
                ->label('Slot Ölçü Rehberi')
                ->content(fn (Get $get): string => AdvertisementPlacement::guidance((string) ($get('position') ?: 'header')))
                ->columnSpanFull(),

            Select::make('type')
                ->label('Tür')
                ->helperText('Banner manuel görsel/link reklamıdır. AdSense, global Client ID ve slot ID ile çalışır.')
                ->options([
                    Advertisement::TYPE_BANNER => 'Manuel Banner',
                    Advertisement::TYPE_ADSENSE => 'Google AdSense',
                ])
                ->default(Advertisement::TYPE_BANNER)
                ->required()
                ->live(),

            Placeholder::make('adsense_client_notice')
                ->label('AdSense Client ID Durumu')
                ->content(fn (): string => filled(self::adsenseClientId())
                    ? 'Client ID hazır. Bu reklam için Google AdSense panelinden alınan sayısal Slot ID değerini girin.'
                    : 'Client ID eksik. AdSense reklamlar publicte görünmez; önce Ayarlar > Entegrasyonlar ekranına ca-pub... değerini girin.')
                ->visible(fn (Get $get) => $get('type') === Advertisement::TYPE_ADSENSE)
                ->columnSpanFull(),

            FileUpload::make('desktop_image_path')
                ->label('Desktop Banner Görseli')
                ->helperText('Manuel banner publicte yalnız desktop görsel veya eski görsel tanımlıysa görünür. Ölçü kararı için slot rehberini kullanın.')
                ->image()
                ->directory('advertisements')
                ->maxSize(AdminImageUploads::maxSizeKb())
                ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file))
                ->visible(fn (Get $get) => $get('type') === Advertisement::TYPE_BANNER),

            FileUpload::make('mobile_image_path')
                ->label('Mobil Banner Görseli')
                ->helperText('Opsiyonel. Girilirse 767px ve altı ekranlarda desktop görsel yerine kullanılır. Mobilde kırpılmayacak sade kreatif tercih edin.')
                ->image()
                ->directory('advertisements')
                ->maxSize(AdminImageUploads::maxSizeKb())
                ->acceptedFileTypes(AdminImageUploads::acceptedMimeTypes())
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => AdminImageUploads::storedFileName($file))
                ->visible(fn (Get $get) => $get('type') === Advertisement::TYPE_BANNER),

            TextInput::make('link_url')
                ->label('Tıklama Linki')
                ->helperText('Opsiyonel. Girilirse banner yeni sekmede açılır ve tıklama sayacı işler.')
                ->url()
                ->visible(fn (Get $get) => $get('type') === Advertisement::TYPE_BANNER),

            TextInput::make('adsense_slot')
                ->label('AdSense Slot ID')
                ->helperText('Google AdSense reklam birimi slot ID değeridir. Client ID, Entegrasyon Ayarları ekranından yönetilir.')
                ->nullable()
                ->required(fn (Get $get): bool => $get('type') === Advertisement::TYPE_ADSENSE)
                ->rule('regex:/^[0-9]+$/')
                ->validationMessages([
                    'regex' => 'AdSense Slot ID yalnızca rakamlardan oluşmalıdır.',
                ])
                ->visible(fn (Get $get) => $get('type') === Advertisement::TYPE_ADSENSE),

            DatePicker::make('start_date')
                ->label('Başlangıç Tarihi')
                ->helperText('Boş bırakılırsa reklam hemen yayına uygun sayılır.')
                ->native(false),

            DatePicker::make('end_date')
                ->label('Bitiş Tarihi')
                ->helperText('Boş bırakılırsa bitiş tarihi uygulanmaz.')
                ->afterOrEqual('start_date')
                ->native(false),

            Toggle::make('is_active')
                ->label('Aktif')
                ->helperText('Pasif kayıt publicte görünmez; aktif kayıt ayrıca tarih ve içerik kurallarından geçmelidir.')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->helperText('Aynı pozisyonda en küçük sıra değeri önceliklidir. Eksik kayıtlar atlanır.')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('position')
                    ->label('Pozisyon')
                    ->formatStateUsing(fn ($state): string => AdvertisementPlacement::options()[$state] ?? (string) $state)
                    ->colors(['primary']),

                BadgeColumn::make('type')
                    ->label('Tür')
                    ->colors([
                        'info' => Advertisement::TYPE_BANNER,
                        'warning' => Advertisement::TYPE_ADSENSE,
                    ])
                    ->formatStateUsing(fn ($state) => $state === Advertisement::TYPE_BANNER ? 'Manuel Banner' : 'Google AdSense'),

                TextColumn::make('render_status')
                    ->label('Yayın Durumu')
                    ->badge()
                    ->getStateUsing(fn (Advertisement $record): string => self::renderStatusLabel($record))
                    ->color(fn (Advertisement $record): string => self::renderStatusColor($record)),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('view_count')
                    ->label('Gösterim')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('click_count')
                    ->label('Tıklama')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Başlangıç')
                    ->date('d.m.Y')
                    ->toggleable(),

                TextColumn::make('end_date')
                    ->label('Bitiş')
                    ->date('d.m.Y')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->label('Pozisyon')
                    ->options(AdvertisementPlacement::options()),

                SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        Advertisement::TYPE_BANNER => 'Manuel Banner',
                        Advertisement::TYPE_ADSENSE => 'Google AdSense',
                    ]),

                SelectFilter::make('render_status')
                    ->label('Yayın Durumu')
                    ->options([
                        'ready' => 'Yayına hazır',
                        'passive' => 'Pasif',
                        'scheduled' => 'Planlı',
                        'expired' => 'Süresi doldu',
                        'missing_banner_image' => 'Eksik görsel',
                        'missing_adsense_slot' => 'Eksik Slot ID',
                        'missing_adsense_client' => 'Eksik Client ID',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyRenderStatusFilter($query, $data['value'] ?? null)),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                Filter::make('date_window')
                    ->label('Tarih Aralığı')
                    ->form([
                        DatePicker::make('from')->label('Başlangıç'),
                        DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('start_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('end_date', '<=', $date))),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->actions([
                Action::make('adsense_settings')
                    ->label('AdSense Ayarları')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url('/admin/integration-settings')
                    ->visible(fn (Advertisement $record): bool => $record->renderStatus(self::adsenseClientId()) === 'missing_adsense_client'),

                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Reklamı sil')
                    ->modalDescription(fn (Advertisement $record): string => self::deleteImpactDescription($record)),
            ])
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading('Bu kapsamda reklam bulunamadı')
            ->emptyStateDescription('Arama veya filtreleri genişletin. Yetkiniz varsa yeni reklam oluşturabilirsiniz.')
            ->emptyStateActions([
                TableCreateAction::make()
                    ->label('Yeni Reklam')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('sort_order');
    }

    public static function normalizeFormData(array $data): array
    {
        if (($data['type'] ?? Advertisement::TYPE_BANNER) === Advertisement::TYPE_ADSENSE) {
            $data['image_path'] = null;
            $data['desktop_image_path'] = null;
            $data['mobile_image_path'] = null;
            $data['link_url'] = null;
        } else {
            $data['adsense_slot'] = null;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'view_any_advertisement');
    }

    public static function canCreate(): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'create_advertisement');
    }

    public static function canEdit(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'update_advertisement');
    }

    public static function canDelete(Model $record): bool
    {
        return AdminPrivileges::hasPermission(auth()->user(), 'delete_advertisement');
    }

    private static function adsenseClientId(): ?string
    {
        return Setting::get('integration', 'adsense_client_id')
            ?: config('services.adsense.client_id');
    }

    private static function renderStatusLabel(Advertisement $record): string
    {
        return match ($record->renderStatus(self::adsenseClientId())) {
            'ready' => 'Yayına Hazır',
            'passive' => 'Pasif',
            'scheduled' => 'Planlı',
            'expired' => 'Süresi Doldu',
            'missing_banner_image' => 'Eksik Görsel',
            'missing_adsense_slot' => 'Eksik Slot ID',
            'missing_adsense_client' => 'Eksik Client ID',
            default => 'Geçersiz',
        };
    }

    private static function renderStatusColor(Advertisement $record): string
    {
        return match ($record->renderStatus(self::adsenseClientId())) {
            'ready' => 'success',
            'scheduled' => 'info',
            'passive', 'expired' => 'gray',
            default => 'danger',
        };
    }

    private static function applyRenderStatusFilter(Builder $query, ?string $status): Builder
    {
        $today = now()->toDateString();

        $currentWindow = function (Builder $query) use ($today): void {
            $query
                ->where(function (Builder $query) use ($today) {
                    $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                })
                ->where(function (Builder $query) use ($today) {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                });
        };

        return match ($status) {
            'ready' => $query
                ->where('is_active', true)
                ->where($currentWindow)
                ->where(function (Builder $query) {
                    $query
                        ->where(function (Builder $query) {
                            $query->where('type', Advertisement::TYPE_BANNER)
                                ->where(function (Builder $query) {
                                    $query->whereNotNull('desktop_image_path')->orWhereNotNull('image_path');
                                });
                        })
                        ->orWhere(function (Builder $query) {
                            $query->where('type', Advertisement::TYPE_ADSENSE)
                                ->whereNotNull('adsense_slot')
                                ->when(blank(self::adsenseClientId()), fn (Builder $query) => $query->whereRaw('1 = 0'));
                        });
                }),
            'passive' => $query->where('is_active', false),
            'scheduled' => $query->whereDate('start_date', '>', $today),
            'expired' => $query->whereDate('end_date', '<', $today),
            'missing_banner_image' => $query
                ->where('type', Advertisement::TYPE_BANNER)
                ->where('is_active', true)
                ->where($currentWindow)
                ->whereNull('desktop_image_path')
                ->whereNull('image_path'),
            'missing_adsense_slot' => $query
                ->where('type', Advertisement::TYPE_ADSENSE)
                ->where('is_active', true)
                ->where($currentWindow)
                ->where(function (Builder $query) {
                    $query->whereNull('adsense_slot')->orWhere('adsense_slot', '');
                }),
            'missing_adsense_client' => $query
                ->where('type', Advertisement::TYPE_ADSENSE)
                ->where('is_active', true)
                ->where($currentWindow)
                ->whereNotNull('adsense_slot')
                ->when(filled(self::adsenseClientId()), fn (Builder $query) => $query->whereRaw('1 = 0')),
            default => $query,
        };
    }

    private static function deleteImpactDescription(Advertisement $advertisement): string
    {
        $status = self::renderStatusLabel($advertisement);

        return "Bu reklam silindiğinde ilgili public slotta artık değerlendirilmez. Mevcut yayın durumu: {$status}.";
    }
}
