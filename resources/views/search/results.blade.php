@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="bg-white dark:bg-adh-blue border border-adh-border dark:border-adh-blue rounded-lg p-5">
        <form action="{{ \App\Support\LocalizedUrl::route('search') }}" method="GET" class="mb-5">
            <div class="flex gap-2">
                <input name="q"
                       value="{{ $query }}"
                       placeholder="{{ __('Haber ara...') }}"
                       class="flex-1 border border-adh-border dark:border-gray-600 rounded px-4 py-2.5 bg-white dark:bg-adh-navy dark:text-gray-100 focus:outline-none focus:border-adh-red text-sm"
                       aria-label="{{ __('Arama') }}">
                <button type="submit"
                        class="bg-adh-red hover:bg-red-700 text-white px-5 py-2.5 rounded text-sm font-medium transition-colors">
                    {{ __('Ara') }}
                </button>
            </div>
        </form>

        @if (mb_strlen((string) $query) < 3)
            <p class="text-adh-gray dark:text-gray-400 text-sm">{{ __('Arama yapmak için en az 3 karakter girin.') }}</p>

        @elseif (!empty($searchError))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.615 3.892 1.82 18a2.25 2.25 0 0 0 1.91 3.375h16.54A2.25 2.25 0 0 0 22.18 18L13.385 3.892a2.25 2.25 0 0 0-3.77 0Z" />
                    </svg>
                    <div class="space-y-2">
                        <h1 class="font-serif text-lg text-adh-text dark:text-white">{{ __('Arama şu anda tamamlanamadı') }}</h1>
                        <p>{{ __('Sonuçları getirirken geçici bir sorun oluştu. Lütfen aynı aramayı biraz sonra tekrar deneyin.') }}</p>
                        <a href="{{ \App\Support\LocalizedUrl::route('home') }}"
                           class="inline-flex items-center gap-2 rounded bg-adh-navy px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-adh-red dark:bg-adh-blue">
                            {{ __('Anasayfaya dön') }}
                        </a>
                    </div>
                </div>
            </div>

        @elseif ($articles instanceof \Illuminate\Pagination\LengthAwarePaginator && $articles->isNotEmpty())
            <div class="flex flex-col gap-2 mb-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="font-serif text-xl dark:text-gray-100">
                        <span class="text-adh-red">"{{ $query }}"</span> {{ __('için arama sonuçları') }}
                    </h1>
                    <p class="text-xs text-adh-gray dark:text-gray-400 mt-1">{{ __('Yayın ve arşiv içerikleri birlikte taranır.') }}</p>
                </div>
                <span class="text-sm text-adh-gray dark:text-gray-400">{{ $articles->total() }} {{ __('haber') }}</span>
            </div>
            <div class="space-y-3">
                @foreach ($articles as $item)
                    <x-news-card-mini :article="$item" />
                @endforeach
            </div>
            <div class="mt-6">
                {{ $articles->links() }}
            </div>

        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2 class="font-serif text-xl text-adh-text dark:text-gray-200 mb-2">
                    <span class="text-adh-red">"{{ $query }}"</span> {{ __('için sonuç bulunamadı') }}
                </h2>
                <p class="text-sm text-adh-gray dark:text-gray-400 mb-6">
                    {{ __('Arama teriminizi kontrol edin veya farklı kelimeler deneyin.') }}
                </p>
                <div class="mb-6">
                    <p class="text-xs text-adh-gray dark:text-gray-500 mb-3 uppercase tracking-wider">{{ __('Popüler aramalar') }}:</p>
                    <div class="flex flex-wrap gap-2 justify-center">
                        @foreach ([__('Adıyaman'), __('Nemrut'), __('Ekonomi'), __('Spor'), __('Eğitim'), __('Sağlık')] as $suggestion)
                            <a href="{{ \App\Support\LocalizedUrl::route('search', [], null, ['q' => $suggestion]) }}"
                               class="px-3 py-1.5 border border-adh-border dark:border-gray-600 rounded text-sm hover:bg-adh-red hover:text-white hover:border-adh-red transition-colors dark:text-gray-200">
                                {{ $suggestion }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ \App\Support\LocalizedUrl::route('home') }}"
                   class="inline-flex items-center gap-2 bg-adh-navy dark:bg-adh-blue text-white px-5 py-2.5 rounded text-sm font-medium hover:bg-adh-red transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ __('Tüm Haberlere Dön') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
