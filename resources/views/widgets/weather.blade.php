@if(!empty($weather) && ($weather['temp'] ?? '--') !== '--')
<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4">
    <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">
        {{ __('Hava Durumu') }}
    </h3>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-adh-gray dark:text-gray-400">{{ __('Adıyaman') }}</p>
            <div class="flex items-end gap-1.5 mt-0.5">
                <span class="text-2xl font-bold text-adh-text dark:text-gray-100">{{ $weather['temp'] ?? '--' }}°</span>
                <span class="text-xs text-adh-gray dark:text-gray-400 mb-0.5">{{ $weather['description'] ?? '' }}</span>
            </div>
        </div>
        <span class="text-4xl" aria-hidden="true">{{ $weather['icon'] ?? '🌤' }}</span>
    </div>
    <div class="grid grid-cols-3 gap-1 mt-3 pt-3 border-t border-adh-border dark:border-gray-700
                text-center text-[10px] text-adh-gray dark:text-gray-400">
        <div>
            <p>{{ __('Nem') }}</p>
            <p class="font-semibold text-adh-text dark:text-gray-200 text-xs">%{{ $weather['humidity'] ?? '--' }}</p>
        </div>
        <div>
            <p>{{ __('Rüzgar') }}</p>
            <p class="font-semibold text-adh-text dark:text-gray-200 text-xs">{{ $weather['wind'] ?? '--' }} km/s</p>
        </div>
        <div>
            <p>{{ __('Hissedilen') }}</p>
            <p class="font-semibold text-adh-text dark:text-gray-200 text-xs">{{ $weather['feels_like'] ?? '--' }}°</p>
        </div>
    </div>
</section>
@endif
