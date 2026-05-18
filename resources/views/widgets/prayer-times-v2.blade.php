<section class="h-full rounded-lg border border-adh-border bg-white p-3 dark:border-gray-700 dark:bg-adh-blue">
    <h3 class="mb-2 border-l-4 border-adh-red pl-2.5 font-serif text-sm font-bold dark:text-gray-100">
        {{ __('Namaz Vakitleri') }}
    </h3>

    @if(!empty($prayerTimes))
        <p class="mb-2 text-[11px] text-adh-gray dark:text-gray-400">
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
                    <div class="rounded bg-adh-gray-light py-1.5 text-center dark:bg-adh-navy/60">
                        <p class="text-[9px] text-adh-gray dark:text-gray-400">{{ $label }}</p>
                        <p class="text-xs font-bold text-adh-text dark:text-gray-100">{{ $time }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="rounded border border-dashed border-adh-border bg-slate-50 px-3 py-3 text-xs text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Namaz vakti verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 leading-5">{{ __('Bağlantı yenilendiğinde Adıyaman için güncel vakitler burada görünecektir.') }}</p>
        </div>
    @endif
</section>
