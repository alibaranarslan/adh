<section class="h-full rounded-lg border border-adh-border bg-white p-3 dark:border-gray-700 dark:bg-adh-blue">
    <h3 class="mb-2 border-l-4 border-adh-red pl-2.5 font-serif text-sm font-bold dark:text-gray-100">
        {{ __('Hava Durumu') }}
    </h3>

    @if(!empty($weather) && ($weather['temp'] ?? '--') !== '--')
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] text-adh-gray dark:text-gray-400">{{ __('Adıyaman') }}</p>
                <div class="mt-0.5 flex items-end gap-1.5">
                    <span class="text-2xl font-bold leading-none text-adh-text dark:text-gray-100">{{ $weather['temp'] ?? '--' }}&deg;</span>
                    <span class="mb-0.5 line-clamp-1 text-[11px] text-adh-gray dark:text-gray-400">{{ $weather['description'] ?? '' }}</span>
                </div>
            </div>
            <span class="text-3xl leading-none" aria-hidden="true">{{ $weather['icon'] ?? '🌤️' }}</span>
        </div>

        <div class="mt-2 grid grid-cols-3 gap-1 border-t border-adh-border pt-2 text-center text-[10px] text-adh-gray dark:border-gray-700 dark:text-gray-400">
            <div>
                <p>{{ __('Nem') }}</p>
                <p class="text-[11px] font-semibold text-adh-text dark:text-gray-200">%{{ $weather['humidity'] ?? '--' }}</p>
            </div>
            <div>
                <p>{{ __('Rüzgar') }}</p>
                <p class="text-[11px] font-semibold text-adh-text dark:text-gray-200">{{ $weather['wind'] ?? '--' }} km/s</p>
            </div>
            <div>
                <p>{{ __('Hissedilen') }}</p>
                <p class="text-[11px] font-semibold text-adh-text dark:text-gray-200">{{ $weather['feels_like'] ?? '--' }}&deg;</p>
            </div>
        </div>
    @else
        <div class="rounded border border-dashed border-adh-border bg-slate-50 px-3 py-3 text-xs text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Adıyaman hava durumu verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 leading-5">{{ __('Bağlantı yenilendiğinde sıcaklık ve rüzgar bilgileri burada otomatik gösterilecektir.') }}</p>
        </div>
    @endif
</section>
