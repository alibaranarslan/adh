@php
    $data = $this->getViewData();
    $viewsDelta = $data['comparison']['views'];
    $visitorsDelta = $data['comparison']['visitors'];
@endphp

<x-filament-panels::page class="admin-page-frame">
    <section class="admin-section-panel" data-tour-anchor="analytics.hero">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600">Editoryal analiz</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Trafik ve karar destek görünümü</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Bu ekran yalnız toplam sayıları değil, önceki döneme göre hareketi de gösterir. Trafik kaynağı, cihaz dağılımı ve kategori etkisi editoryal önceliklendirmeyi desteklemek için birlikte sunulur.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(['today' => 'Bugün', '7days' => 'Son 7 gün', '30days' => 'Son 30 gün'] as $key => $label)
                    <x-filament::button wire:click="setPeriod('{{ $key }}')" color="{{ $period === $key ? 'primary' : 'gray' }}" size="sm">{{ $label }}</x-filament::button>
                @endforeach
                <x-filament::button tag="a" href="#" wire:click.prevent="exportCsv" color="success" size="sm" data-tour-anchor="analytics.export">CSV dışa aktar</x-filament::button>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">{{ $data['periodLabel'] }}</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">Karşılaştırma: {{ $data['previousPeriodLabel'] }}</span>
        </div>
    </section>

    <div class="admin-page-grid admin-page-grid--three" data-tour-anchor="analytics.comparison">
        <div class="admin-section-panel">
            <p class="text-sm text-slate-500 dark:text-slate-400">Toplam görüntülenme</p>
            <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($data['totalViews']) }}</p>
            <p class="mt-2 text-xs {{ $viewsDelta['direction'] === 'up' ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">{{ $viewsDelta['direction'] === 'up' ? '+' : '' }}{{ number_format($viewsDelta['difference']) }} @if(! is_null($viewsDelta['percentage'])) ({{ $viewsDelta['direction'] === 'up' ? '+' : '' }}{{ $viewsDelta['percentage'] }}%) @endif</p>
        </div>
        <div class="admin-section-panel">
            <p class="text-sm text-slate-500 dark:text-slate-400">Benzersiz ziyaretçi</p>
            <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($data['uniqueVisitors']) }}</p>
            <p class="mt-2 text-xs {{ $visitorsDelta['direction'] === 'up' ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">{{ $visitorsDelta['direction'] === 'up' ? '+' : '' }}{{ number_format($visitorsDelta['difference']) }} @if(! is_null($visitorsDelta['percentage'])) ({{ $visitorsDelta['direction'] === 'up' ? '+' : '' }}{{ $visitorsDelta['percentage'] }}%) @endif</p>
        </div>
        <div class="admin-section-panel">
            <p class="text-sm text-slate-500 dark:text-slate-400">Mobil / masaüstü</p>
            <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($data['deviceDistribution']['mobile'] ?? 0) }} / {{ number_format($data['deviceDistribution']['desktop'] ?? 0) }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Kart ve modül yoğunluğu kararlarında referans alınır.</p>
        </div>
    </div>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">En çok okunan haberler</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-800"><tr><th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Haber</th><th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Kategori</th><th class="px-4 py-3 text-right font-medium text-slate-500 dark:text-slate-400">Görüntülenme</th></tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($data['topArticles'] as $article)
                            <tr><td class="px-4 py-3 text-slate-900 dark:text-white">{{ $article->getTranslation('title', 'tr') }}</td><td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $article->category?->getTranslation('name', 'tr') ?? '—' }}</td><td class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-200">{{ number_format($article->page_views_count) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-5"><div class="admin-empty-state">Bu aralıkta haber bazlı görüntülenme verisi bulunamadı.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section-panel" data-tour-anchor="analytics.sources">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Trafik kaynakları</h3>
            <div class="mt-4 space-y-3">
                @forelse($data['trafficSources'] as $source => $count)
                    @php $maxSourceCount = max((int) ($data['trafficSources']->max() ?? 1), 1); $width = min(100, (int) round(($count / $maxSourceCount) * 100)); @endphp
                    <div><div class="mb-1 flex items-center justify-between text-sm"><span class="font-medium capitalize text-slate-800 dark:text-slate-200">{{ $source }}</span><span class="text-slate-500 dark:text-slate-400">{{ number_format($count) }}</span></div><div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $width }}%"></div></div></div>
                @empty
                    <div class="admin-empty-state">Bu aralıkta trafik kaynağı verisi bulunamadı.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel" data-tour-anchor="analytics.devices">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Cihaz kırılımı</h3>
            <div class="mt-4 space-y-3">
                @forelse($data['deviceDistribution'] as $device => $count)
                    @php $maxDeviceCount = max((int) ($data['deviceDistribution']->max() ?? 1), 1); $width = min(100, (int) round(($count / $maxDeviceCount) * 100)); @endphp
                    <div><div class="mb-1 flex items-center justify-between text-sm"><span class="font-medium capitalize text-slate-800 dark:text-slate-200">{{ $device ?: 'unknown' }}</span><span class="text-slate-500 dark:text-slate-400">{{ number_format($count) }}</span></div><div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-sky-500" style="width: {{ $width }}%"></div></div></div>
                @empty
                    <div class="admin-empty-state">Bu aralıkta cihaz kırılımı verisi bulunamadı.</div>
                @endforelse
            </div>
        </section>

        <section class="admin-section-panel">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Günlük trend</h3>
            <div class="mt-4 space-y-3">
                @forelse($data['dailyViews'] as $date => $count)
                    @php $maxDailyCount = max((int) ($data['dailyViews']->max() ?? 1), 1); $width = min(100, (int) round(($count / $maxDailyCount) * 100)); @endphp
                    <div><div class="mb-1 flex items-center justify-between text-sm"><span class="text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</span><span class="font-medium text-slate-900 dark:text-white">{{ number_format($count) }}</span></div><div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-amber-500" style="width: {{ $width }}%"></div></div></div>
                @empty
                    <div class="admin-empty-state">Günlük trend verisi bulunamadı.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
