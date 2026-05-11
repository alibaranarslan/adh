@php
$tags = \App\Models\Tag::withCount('articles')
    ->orderByDesc('articles_count')
    ->take(20)
    ->get();
@endphp

@if($tags->isNotEmpty())
<div class="bg-white dark:bg-gray-800 rounded-lg border border-adh-border dark:border-gray-700 p-4 mb-4">
    <h3 class="font-serif font-bold text-lg border-t-2 border-adh-red pt-2 mb-3">
        {{ __('Popüler Etiketler') }}
    </h3>
    <div class="flex flex-wrap gap-2">
        @foreach($tags as $tag)
        <a href="{{ route('news.tag', ['slug' => $tag->slug]) }}"
           class="inline-block px-3 py-1 text-xs font-medium rounded-full
                  bg-adh-gray-light dark:bg-gray-700 text-adh-text dark:text-gray-300
                  hover:bg-adh-red hover:text-white transition-colors"
           title="{{ $tag->articles_count }} {{ __('haber') }}">
            {{ $tag->name }}
        </a>
        @endforeach
    </div>
</div>
@endif
