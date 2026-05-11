@if(!empty($prayerTimes))
<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4">
    <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">
        {{ __('Namaz Vakitleri') }}
    </h3>
    <p class="text-xs text-adh-gray dark:text-gray-400 mb-3">
        {{ __('Adıyaman') }} — {{ now()->locale(app()->getLocale())->translatedFormat('d F Y') }}
    </p>
    <div class="grid grid-cols-3 gap-1">
        @php
        $slots = [
            [__('İmsak'),  $prayerTimes['imsak']  ?? ''],
            [__('Güneş'),  $prayerTimes['gunes']  ?? ''],
            [__('Öğle'),   $prayerTimes['ogle']   ?? ''],
            [__('İkindi'), $prayerTimes['ikindi'] ?? ''],
            [__('Akşam'),  $prayerTimes['aksam']  ?? ''],
            [__('Yatsı'),  $prayerTimes['yatsi']  ?? ''],
        ];
        @endphp
        @foreach ($slots as [$label, $time])
        @if($time)
        <div class="text-center py-2 rounded bg-adh-gray-light dark:bg-adh-navy/60">
            <p class="text-[10px] text-adh-gray dark:text-gray-400">{{ $label }}</p>
            <p class="text-sm font-bold text-adh-text dark:text-gray-100">{{ $time }}</p>
        </div>
        @endif
        @endforeach
    </div>
</section>
@endif
