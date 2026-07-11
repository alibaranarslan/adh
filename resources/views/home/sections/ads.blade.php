@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Reklam Verilebilir Alanlar');
    $reservedAdPositions = collect($reservedAdPositions ?? []);
    $modulePositions = collect(['between-news', 'home-top', 'home-feed', 'home-lower', 'sidebar-top', 'sidebar-bottom'])
        ->reject(fn (string $position): bool => $reservedAdPositions->contains($position))
        ->values();
@endphp

<section class="py-4 md:py-5">
    <div class="mb-3 flex items-center gap-3">
        <h2 class="font-serif text-xl font-bold dark:text-gray-100">{{ $title }}</h2>
        <div class="h-px flex-1 bg-adh-border dark:bg-gray-700"></div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        @foreach($modulePositions as $position)
            <x-ad-slot :position="$position" @class(['lg:col-span-2' => ! str_starts_with($position, 'sidebar-')]) />
        @endforeach
    </div>
</section>
