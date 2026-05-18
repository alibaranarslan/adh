@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Sponsorlu Alanlar');
@endphp

<section class="py-4 md:py-5">
    <div class="mb-3 flex items-center gap-3">
        <h2 class="font-serif text-xl font-bold dark:text-gray-100">{{ $title }}</h2>
        <div class="h-px flex-1 bg-adh-border dark:bg-gray-700"></div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        <x-ad-slot position="between-news" class="lg:col-span-2" />
        <x-ad-slot position="sidebar-top" />
        <x-ad-slot position="sidebar-bottom" />
    </div>
</section>
