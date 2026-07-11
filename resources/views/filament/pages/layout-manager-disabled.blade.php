<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-2">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Legacy Layout Manager devre dışı</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Anasayfa yerleşimi artık Layout Studio üzerinden yönetilir. Bu eski yüzey canlı
                mutasyon metotları taşımadığı için yalnız bilgilendirme amacıyla tutulur.
            </p>
            <a
                href="{{ \App\Filament\Pages\LayoutStudio::getUrl() }}"
                class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500"
            >
                Layout Studio'ya geç
            </a>
        </div>
    </x-filament::section>
</x-filament-panels::page>
