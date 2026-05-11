<div class="space-y-5">

    {{-- En Çok Okunan --}}
    @if(($mostRead ?? collect())->isNotEmpty())
    <section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4">
        <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">
            {{ __('En Çok Okunan') }}
        </h3>
        <ol class="space-y-0">
            @foreach ($mostRead as $index => $article)
            <li class="flex gap-3 items-start py-2.5 border-b border-adh-border dark:border-gray-700 last:border-0">
                <span class="font-serif font-bold text-lg text-adh-red/40 leading-none w-6 flex-shrink-0 pt-0.5">
                    {{ $index + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('news.show', ['slug' => $article->slug]) }}"
                       class="text-[13px] font-semibold font-serif leading-snug hover:text-adh-red dark:text-gray-100 dark:hover:text-adh-red-light transition-colors line-clamp-2">
                        {{ $article->title }}
                    </a>
                    <time class="text-[10px] text-adh-gray dark:text-gray-400 mt-0.5 block">
                        {{ optional($article->published_at)?->locale(app()->getLocale())->isoFormat('D MMM, HH:mm') }}
                    </time>
                </div>
            </li>
            @endforeach
        </ol>
    </section>
    @endif

    {{-- Yerel Bilgiler --}}
    @include('widgets.weather-v2')
    @include('widgets.pharmacy-v2')
    @include('widgets.prayer-times-v2')
    @include('widgets.local-info-v3')
    @include('widgets.tag-cloud')

    {{-- Reklam Alanları --}}
    <x-ad-slot position="sidebar-top" />
    <x-ad-slot position="sidebar-bottom" />

</div>
