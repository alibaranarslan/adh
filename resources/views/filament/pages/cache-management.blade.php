<x-filament-panels::page class="admin-page-frame">
    <div class="admin-note">
        Bu ekran yalnız operasyonel işlemler içindir. Önce parçalı temizlik aksiyonlarını, yalnız gerekliyse tüm önbellek temizliğini kullanın.
    </div>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Parçalı önbellek işlemleri</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Risk düşük olduğu için önce hedefli temizlik önerilir.</p>
            <div class="mt-4 space-y-3">
                @foreach ([
                    ['title' => 'Yapılandırma önbelleği', 'description' => 'Yapılandırma dosyalarının cache içeriğini temizler.', 'action' => 'clearConfig', 'label' => 'Temizle', 'color' => 'warning'],
                    ['title' => 'Görünüm önbelleği', 'description' => 'Derlenmiş Blade çıktısını temizler.', 'action' => 'clearView', 'label' => 'Temizle', 'color' => 'warning'],
                    ['title' => 'Rota önbelleği', 'description' => 'Rota önbelleğini sıfırlar.', 'action' => 'clearRoute', 'label' => 'Temizle', 'color' => 'warning'],
                    ['title' => 'Uygulama optimizasyonu', 'description' => 'Autoloader ve optimize edilmiş uygulama önbelleğini yeniler.', 'action' => 'optimizeApp', 'label' => 'Optimize et', 'color' => 'success'],
                ] as $item)
                    <div class="rounded-2xl border border-slate-200/80 px-4 py-4 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $item['title'] }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                            </div>
                            <x-filament::button wire:click="{{ $item['action'] }}" color="{{ $item['color'] }}" size="sm">{{ $item['label'] }}</x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="admin-section-panel">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tam temizlik</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tüm cache katmanlarını bir seferde sıfırlar. Yoğun trafik anlarında dikkatle kullanılmalıdır.</p>
            <div class="mt-6 flex flex-col items-center rounded-2xl border border-rose-200/80 bg-rose-50/60 px-6 py-8 text-center dark:border-rose-500/20 dark:bg-rose-500/10">
                <x-heroicon-o-trash class="h-12 w-12 text-rose-500" />
                <p class="mt-4 text-base font-semibold text-slate-900 dark:text-white">Tüm önbelleği temizle</p>
                <p class="mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">Uygulama, yapılandırma, görünüm ve rota önbelleği birlikte temizlenir. İlk isteklerde kısa süreli yavaşlama oluşabilir.</p>
                <div class="admin-action-bar mt-5 w-full justify-center border-0 pt-0">
                    <x-filament::button wire:click="clearAll" color="danger" wire:confirm="Tüm önbellek temizlenecek. Devam etmek istiyor musunuz?">Tüm önbelleği temizle</x-filament::button>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
