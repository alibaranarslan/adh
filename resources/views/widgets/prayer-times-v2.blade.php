<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4">
    <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">
        {{ __('Namaz Vakitleri') }}
    </h3>

    @if(!empty($prayerTimes))
        <p class="mb-3 text-xs text-adh-gray dark:text-gray-400">
            {{ __('Adıyaman') }} — {{ now()->locale(app()->getLocale())->translatedFormat('d F Y') }}
        </p>

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

        <div class="grid grid-cols-3 gap-1">
            @foreach ($slots as [$label, $time])
                @if($time)
                    <div class="rounded bg-adh-gray-light py-2 text-center dark:bg-adh-navy/60">
                        <p class="text-[10px] text-adh-gray dark:text-gray-400">{{ $label }}</p>
                        <p class="text-sm font-bold text-adh-text dark:text-gray-100">{{ $time }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="rounded-lg border border-dashed border-adh-border bg-slate-50 px-4 py-5 text-sm text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Namaz vakti verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 text-xs">{{ __('Bağlantı yenilendiğinde Adıyaman için güncel vakitler burada görünecektir.') }}</p>
        </div>
    @endif
</section>
