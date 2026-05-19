@php
    $status = $this->getCacheStatus();
@endphp

<x-filament-panels::page class="admin-page-frame">
    <div class="admin-note">
        Bu ekran yalnız operasyonel önbellek işlemleri içindir. {{ $status['safe_first'] }}
        <span class="ml-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Hazır</span>
        <span class="ml-2 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Public etki</span>
    </div>

    <section class="admin-section-panel">
        <div class="admin-page-grid admin-page-grid--three">
            <div class="admin-section-panel admin-section-panel--compact">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cache store</p>
                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $status['default_store'] }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Laravel cache.default kaynağı.</p>
            </div>
            <div class="admin-section-panel admin-section-panel--compact">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Public page cache</p>
                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Aktif rota katmanı</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $status['page_cache'] }}</p>
            </div>
            <div class="admin-section-panel admin-section-panel--compact">
                <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Operasyonel etki</p>
                <p class="mt-2 text-sm font-semibold text-amber-700 dark:text-amber-300">İlk istekler yavaşlayabilir</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $status['public_effect'] }}</p>
            </div>
        </div>
    </section>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Hedefli önbellek işlemleri</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Düşük riskli işlemlerdir; önce ilgili katmanı temizleyin.</p>
            <div class="mt-4 space-y-3">
                @foreach ([
                    ['title' => 'Yapılandırma önbelleği', 'description' => 'Config cache içeriğini temizler. Env/config değişikliklerinden sonra kullanılır.', 'action' => 'clearConfig', 'label' => 'Temizle', 'color' => 'warning', 'confirm' => 'Yapılandırma önbelleği temizlenecek. Devam etmek istiyor musunuz?'],
                    ['title' => 'Görünüm önbelleği', 'description' => 'Derlenmiş Blade çıktılarını temizler.', 'action' => 'clearView', 'label' => 'Temizle', 'color' => 'warning', 'confirm' => 'Görünüm önbelleği temizlenecek. Devam etmek istiyor musunuz?'],
                    ['title' => 'Rota önbelleği', 'description' => 'Route cache katmanını sıfırlar.', 'action' => 'clearRoute', 'label' => 'Temizle', 'color' => 'warning', 'confirm' => 'Rota önbelleği temizlenecek. Devam etmek istiyor musunuz?'],
                    ['title' => 'Uygulama optimizasyonu', 'description' => 'Optimize edilmiş uygulama önbelleğini yeniden üretir.', 'action' => 'optimizeApp', 'label' => 'Optimize et', 'color' => 'success', 'confirm' => 'Uygulama optimizasyonu cache dosyalarını yeniden üretecek. Devam etmek istiyor musunuz?'],
                ] as $item)
                    <div class="rounded-2xl border border-slate-200/80 px-4 py-4 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $item['title'] }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                            </div>
                            <x-filament::button wire:click="{{ $item['action'] }}" wire:confirm="{{ $item['confirm'] }}" color="{{ $item['color'] }}" size="sm">
                                {{ $item['label'] }}
                            </x-filament::button>
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
                <p class="mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">Uygulama, yapılandırma, görünüm ve rota önbelleği birlikte temizlenir. Public sayfalarda ilk isteklerde kısa süreli yavaşlama oluşabilir.</p>
                <div class="admin-action-bar mt-5 w-full justify-center border-0 pt-0">
                    <x-filament::button wire:click="clearAll" color="danger" wire:confirm="Tüm önbellek katmanları temizlenecek. Yoğun trafik anında kısa süreli yavaşlama olabilir. Devam etmek istiyor musunuz?">
                        Tüm önbelleği temizle
                    </x-filament::button>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
