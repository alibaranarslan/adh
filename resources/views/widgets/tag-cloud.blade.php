@php
$tags = \App\Models\Tag::withCount('articles')
    ->orderByDesc('articles_count')
    ->take(16)
    ->get();
@endphp

@if($tags->isNotEmpty())
<div class="mt-2.5 rounded-lg border border-adh-border bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-adh-blue">
    <div class="flex flex-col gap-2 md:flex-row md:items-center">
        <h3 class="shrink-0 border-l-4 border-adh-red pl-2.5 font-serif text-sm font-bold dark:text-gray-100">
            {{ __('Popüler Etiketler') }}
        </h3>
        <div class="flex flex-wrap gap-1.5">
            @foreach($tags as $tag)
            <a href="{{ \App\Support\LocalizedUrl::route('news.tag', ['slug' => $tag->slug]) }}"
               class="inline-block rounded-full bg-adh-gray-light px-2.5 py-1 text-[11px] font-medium text-adh-text transition-colors hover:bg-adh-red hover:text-white dark:bg-gray-700 dark:text-gray-300"
               title="{{ $tag->articles_count }} {{ __('haber') }}">
                {{ $tag->name }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif
