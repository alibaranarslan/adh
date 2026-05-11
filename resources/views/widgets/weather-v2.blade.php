<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4">
    <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">
        {{ __('Hava Durumu') }}
    </h3>

    @if(!empty($weather) && ($weather['temp'] ?? '--') !== '--')
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-adh-gray dark:text-gray-400">{{ __('Adıyaman') }}</p>
                <div class="mt-0.5 flex items-end gap-1.5">
                    <span class="text-2xl font-bold text-adh-text dark:text-gray-100">{{ $weather['temp'] ?? '--' }}&deg;</span>
                    <span class="mb-0.5 text-xs text-adh-gray dark:text-gray-400">{{ $weather['description'] ?? '' }}</span>
                </div>
            </div>
            <span class="text-4xl" aria-hidden="true">{{ $weather['icon'] ?? '🌤️' }}</span>
        </div>

        <div class="mt-3 grid grid-cols-3 gap-1 border-t border-adh-border pt-3 text-center text-[10px] text-adh-gray dark:border-gray-700 dark:text-gray-400">
            <div>
                <p>{{ __('Nem') }}</p>
                <p class="text-xs font-semibold text-adh-text dark:text-gray-200">%{{ $weather['humidity'] ?? '--' }}</p>
            </div>
            <div>
                <p>{{ __('Rüzgar') }}</p>
                <p class="text-xs font-semibold text-adh-text dark:text-gray-200">{{ $weather['wind'] ?? '--' }} km/s</p>
            </div>
            <div>
                <p>{{ __('Hissedilen') }}</p>
                <p class="text-xs font-semibold text-adh-text dark:text-gray-200">{{ $weather['feels_like'] ?? '--' }}&deg;</p>
            </div>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-adh-border bg-slate-50 px-4 py-5 text-sm text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Adıyaman hava durumu verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 text-xs">{{ __('Bağlantı yenilendiğinde sıcaklık ve rüzgar bilgileri burada otomatik gösterilecektir.') }}</p>
        </div>
    @endif
</section>
