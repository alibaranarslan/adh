@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Bilgi Panosu');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

<section class="border-b border-adh-border py-2.5 dark:border-gray-700 md:py-3">
    <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50/70 p-3 shadow-sm dark:border-gray-700 dark:bg-adh-blue/30 md:p-3.5">
        <div class="mb-3 flex flex-col gap-2 border-b border-adh-border/80 pb-2.5 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Yerel Servisler') }}</p>
                <h2 class="mt-0.5 font-serif text-lg font-bold text-adh-text dark:text-gray-100 md:text-xl">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-adh-gray dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>

            <p class="max-w-md text-[11px] font-medium leading-5 text-adh-gray dark:text-gray-400">
                {{ __('Hava durumu, nöbetçi eczane ve günlük yerel bilgiler tek akışta sunulur.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-4">
            @include('widgets.weather-v2')
            @include('widgets.pharmacy-v2')
            @include('widgets.prayer-times-v2')
            @include('widgets.local-info-v3')
        </div>

        @include('widgets.tag-cloud')
    </div>
</section>
