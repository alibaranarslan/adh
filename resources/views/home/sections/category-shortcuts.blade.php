@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Kategoriler');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

@if (($categories ?? collect())->isNotEmpty())
    <section class="border-b border-adh-border py-6 dark:border-gray-700">
        <x-section-heading :title="$title" :subtitle="$subtitle" eyebrow="{{ __('Hızlı Geçiş') }}" />

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($categories as $category)
                <a
                    href="{{ route('news.category', ['slug' => $category->slug, 'locale' => app()->getLocale()]) }}"
                    class="group flex items-center justify-between rounded-[var(--adh-radius)] border border-adh-border bg-white px-4 py-3 transition-all hover:border-adh-red hover:shadow-sm dark:border-gray-700 dark:bg-adh-blue"
                >
                    <span class="font-serif text-sm font-semibold text-adh-text transition-colors group-hover:text-adh-red dark:text-gray-100">
                        {{ $category->name }}
                    </span>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-adh-gray dark:bg-adh-navy/60 dark:text-gray-400">
                        {{ $category->articles_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif