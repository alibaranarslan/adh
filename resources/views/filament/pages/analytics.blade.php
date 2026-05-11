<x-filament-panels::page>
    @php
        $data = $this->getViewData();
    @endphp

    {{-- Period Filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach(['today' => 'Bugün', '7days' => 'Son 7 Gün', '30days' => 'Son 30 Gün'] as $key => $label)
            <x-filament::button
                wire:click="setPeriod('{{ $key }}')"
                color="{{ $period === $key ? 'primary' : 'gray' }}"
                size="sm"
            >
                {{ $label }}
            </x-filament::button>
        @endforeach
        <x-filament::button
            tag="a"
            href="#"
            wire:click.prevent="exportCsv"
            color="success"
            size="sm"
        >
            CSV Dışa Aktar
        </x-filament::button>
    </div>

    {{-- Overview Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-filament::card>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($data['totalViews']) }}</p>
                <p class="mt-1 text-sm text-gray-500">Toplam Görüntülenme</p>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($data['uniqueVisitors']) }}</p>
                <p class="mt-1 text-sm text-gray-500">Benzersiz Ziyaretçi</p>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $data['deviceDistribution']['mobile'] ?? 0 }}
                </p>
                <p class="mt-1 text-sm text-gray-500">Mobil Ziyaret</p>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $data['deviceDistribution']['desktop'] ?? 0 }}
                </p>
                <p class="mt-1 text-sm text-gray-500">Masaüstü Ziyaret</p>
            </div>
        </x-filament::card>
    </div>

    {{-- Top Articles --}}
    <x-filament::section heading="En Çok Okunan Haberler">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="pb-2 pr-4 font-medium text-gray-600 dark:text-gray-400">Haber</th>
                        <th class="pb-2 text-right font-medium text-gray-600 dark:text-gray-400">Görüntülenme</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['topArticles'] as $article)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 text-gray-900 dark:text-white">
                                {{ $article->getTranslation('title', 'tr') }}
                            </td>
                            <td class="py-2 text-right text-gray-500">
                                {{ number_format($article->page_views_count) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section heading="Trafik Kaynakları" class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="pb-2 pr-4 font-medium text-gray-600 dark:text-gray-400">Kaynak</th>
                        <th class="pb-2 text-right font-medium text-gray-600 dark:text-gray-400">Oturum</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['trafficSources'] as $source => $count)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 text-gray-900 capitalize dark:text-white">{{ $source }}</td>
                            <td class="py-2 text-right text-gray-500">{{ number_format($count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-4 text-sm text-gray-500">Bu aralıkta trafik kaynağı verisi bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
