@php
    $locale = app()->getLocale();
    $title = data_get($settings, "title_override.$locale") ?: __('Bilgi Panosu');
    $subtitle = data_get($settings, "subtitle_override.$locale");
@endphp

<section class="border-b border-adh-border py-4 dark:border-gray-700 md:py-5">
    <div class="rounded-[var(--adh-radius)] border border-adh-border/80 bg-slate-50/65 p-3.5 shadow-sm dark:border-gray-700 dark:bg-adh-blue/30 md:p-4">
        <div class="mb-4 flex flex-col gap-2 border-b border-adh-border/80 pb-3 dark:border-gray-700 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('Yerel Servisler') }}</p>
                <h2 class="mt-1 font-serif text-xl font-bold text-adh-text dark:text-gray-100">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-1 text-sm text-adh-gray dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>

            <p class="max-w-md text-xs leading-5 text-adh-gray dark:text-gray-400">
                {{ __('Hava durumu, nöbetçi eczane ve günlük yerel bilgiler tek akışta sunulur.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:[grid-template-columns:repeat(auto-fit,minmax(18rem,1fr))]">
            @include('widgets.weather-v2')
            @include('widgets.pharmacy-v2')
            @include('widgets.prayer-times-v2')
            @include('widgets.local-info-v3')
            @include('widgets.tag-cloud')
        </div>
    </div>
</section>
