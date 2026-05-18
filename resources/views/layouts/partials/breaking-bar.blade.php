@php
    $showBreakingBar = collect(data_get($layoutState ?? [], 'modules', []))
        ->firstWhere('key', 'breaking_bar')['is_active'] ?? true;
@endphp

@if ($showBreakingBar && ($breakingNews ?? collect())->isNotEmpty())
    <section class="overflow-hidden bg-adh-red text-white" aria-label="{{ __('Son dakika haberleri') }}" aria-live="polite">
        <div class="mx-auto flex min-h-11 max-w-7xl items-center gap-2 px-3 py-1.5 sm:px-4 md:gap-4 md:py-2">
            <span class="flex shrink-0 items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-white"></span>
                </span>
                <span class="whitespace-nowrap text-[11px] font-bold uppercase tracking-widest md:text-xs">{{ __('Son Dakika') }}</span>
            </span>

            <div class="h-4 w-px shrink-0 bg-white/30"></div>

            <div class="min-w-0 flex-1 overflow-hidden"
                 x-data="{ paused: false }"
                 @mouseenter="paused = true"
                 @mouseleave="paused = false">
                <div class="flex whitespace-nowrap"
                     :class="paused ? 'animate-none' : 'animate-marquee'">
                    @foreach ($breakingNews as $item)
                        <span class="mx-3 inline-flex min-h-8 items-center gap-1.5 text-xs md:mx-8 md:text-sm">
                            <span class="text-white/60">&bull;</span>
                            <a href="{{ \App\Support\LocalizedUrl::route('news.show', ['slug' => $item->slug]) }}"
                               class="max-w-[14rem] truncate font-semibold hover:underline sm:max-w-[18rem] md:max-w-none md:font-medium">{{ $item->title }}</a>
                        </span>
                    @endforeach
                    @foreach ($breakingNews as $item)
                        <span class="mx-3 inline-flex min-h-8 items-center gap-1.5 text-xs md:mx-8 md:text-sm" aria-hidden="true">
                            <span class="text-white/60">&bull;</span>
                            <a href="{{ \App\Support\LocalizedUrl::route('news.show', ['slug' => $item->slug]) }}"
                               class="max-w-[14rem] truncate font-semibold hover:underline sm:max-w-[18rem] md:max-w-none md:font-medium">{{ $item->title }}</a>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
