<?php

namespace App\Filament\Pages;

use App\Support\AdminPrivileges;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Medya Kütüphanesi';
    protected static ?string $title = 'Medya Kütüphanesi';
    protected static ?string $navigationGroup = 'İçerik';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.media-library';

    public string $viewMode = 'grid';
    public string $search = '';
    public bool $showOrphaned = false;
    public int $visibleLimit = 24;
    private const MEDIA_BATCH_SIZE = 24;

    public static function canAccess(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }

    public function getMediaItems(): \Illuminate\Support\Collection
    {
        $canDeleteMedia = $this->canDeleteMedia();

        return $this->mediaQuery()
            ->limit($this->visibleLimit)
            ->get()
            ->map(function (Media $media) use ($canDeleteMedia) {
                $isOrphaned = $this->isOrphaned($media);

                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'size' => $this->humanFileSize($media->size),
                    'collection' => $media->collection_name,
                    'model_type' => $media->model_type ? class_basename($media->model_type) : '—',
                    'model_id' => $media->model_id,
                    'is_orphaned' => $isOrphaned,
                    'can_delete' => $canDeleteMedia && $isOrphaned,
                    'usage_status' => $isOrphaned ? 'Kullanılmıyor' : 'Kullanımda',
                    'usage_label' => $this->usageLabel($media, $isOrphaned),
                    'thumb_url' => $media->hasGeneratedConversion('thumb')
                        ? $media->getUrl('thumb')
                        : ($media->type === 'image' ? $media->getUrl() : null),
                    'created_at' => $media->created_at?->format('d.m.Y H:i'),
                    'mime_type' => $media->mime_type,
                ];
            });
    }

    public function getMediaTotalCount(): int
    {
        return $this->mediaQuery()->count();
    }

    public function hasMoreMedia(): bool
    {
        return $this->getMediaTotalCount() > $this->visibleLimit;
    }

    public function loadMoreMedia(): void
    {
        $this->visibleLimit += self::MEDIA_BATCH_SIZE;
    }

    public function updatedSearch(): void
    {
        $this->resetVisibleLimit();
    }

    public function deleteMedia(int $id): void
    {
        if (! $this->canDeleteMedia()) {
            Notification::make()
                ->danger()
                ->title('Yetkisiz işlem')
                ->body('Medya silme işlemi için yönetim yetkisi gerekiyor.')
                ->send();

            return;
        }

        $media = Media::find($id);

        if (! $media) {
            Notification::make()->danger()->title('Medya bulunamadı')->send();

            return;
        }

        if (! $this->isOrphaned($media)) {
            Notification::make()
                ->warning()
                ->title('Medya kullaniliyor')
                ->body('Bu dosya bir icerige bagli oldugu icin medya kutuphanesinden silinmedi.')
                ->send();

            return;
        }

        $media->delete();
        Notification::make()->success()->title('Medya silindi')->send();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function toggleOrphaned(): void
    {
        $this->showOrphaned = ! $this->showOrphaned;
        $this->resetVisibleLimit();
    }

    private function mediaQuery()
    {
        $query = Media::query()
            ->when($this->search, fn ($q) => $q->where('file_name', 'like', '%' . $this->search . '%'))
            ->orderByDesc('created_at');

        if ($this->showOrphaned) {
            $query->where(function ($q) {
                $q->whereNull('model_type')
                    ->orWhereNull('model_id')
                    ->orWhereDoesntHave('model');
            });
        }

        return $query;
    }

    private function resetVisibleLimit(): void
    {
        $this->visibleLimit = self::MEDIA_BATCH_SIZE;
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private function isOrphaned(Media $media): bool
    {
        if (blank($media->model_type) || blank($media->model_id)) {
            return true;
        }

        try {
            return ! $media->model()->exists();
        } catch (\Throwable) {
            return true;
        }
    }

    private function usageLabel(Media $media, bool $isOrphaned): string
    {
        if ($isOrphaned) {
            return 'Herhangi bir içerik kaydına bağlı değil; medya kütüphanesinden silinebilir.';
        }

        return sprintf(
            '%s #%s kaydına bağlı; bağlı medya ilgili editör kaydından değiştirilmelidir.',
            $media->model_type ? class_basename($media->model_type) : 'İçerik',
            $media->model_id ?: '-',
        );
    }

    private function canDeleteMedia(): bool
    {
        return AdminPrivileges::canAccessConfiguration(auth()->user());
    }
}
