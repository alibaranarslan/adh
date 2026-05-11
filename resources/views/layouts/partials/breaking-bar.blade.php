@php
    $showBreakingBar = collect(data_get($layoutState ?? [], 'modules', []))
        ->firstWhere('key', 'breaking_bar')['is_active'] ?? true;
@endphp

@if ($showBreakingBar && ($breakingNews ?? collect())->isNotEmpty())
    <section class="bg-adh-red text-white overflow-hidden" aria-label="{{ __('Son dakika haberleri') }}" aria-live="polite">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-4">
            {{-- "Son Dakika" etiketi — pulse animasyonlu --}}
            <span class="flex items-center gap-2 shrink-0">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                </span>
                <span class="font-bold uppercase tracking-widest text-xs whitespace-nowrap">{{ __('Son Dakika') }}</span>
            </span>

            <div class="h-4 w-px bg-white/40 shrink-0"></div>

            {{-- Marquee — hover'da duruyor --}}
            <div class="overflow-hidden flex-1"
                 x-data="{ paused: false }"
                 @mouseenter="paused = true"
                 @mouseleave="paused = false">
                <div class="flex gap-0 whitespace-nowrap"
                     :class="paused ? 'animate-none' : 'animate-marquee'">
                    @foreach ($breakingNews as $item)
                        <span class="mx-8 text-sm inline-flex items-center gap-1.5">
                            <span class="text-white/60">●</span>
                            <a href="{{ route('news.show', ['slug' => $item->slug]) }}"
                               class="hover:underline font-medium">{{ $item->title }}</a>
                        </span>
                    @endforeach
                    {{-- Duplicate for seamless loop --}}
                    @foreach ($breakingNews as $item)
                        <span class="mx-8 text-sm inline-flex items-center gap-1.5" aria-hidden="true">
                            <span class="text-white/60">●</span>
                            <a href="{{ route('news.show', ['slug' => $item->slug]) }}"
                               class="hover:underline font-medium">{{ $item->title }}</a>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
