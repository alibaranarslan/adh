<section class="h-full rounded-lg border border-adh-border bg-white p-3 dark:border-gray-700 dark:bg-adh-blue">
    <h3 class="mb-2 border-l-4 border-adh-red pl-2.5 font-serif text-sm font-bold dark:text-gray-100">{{ __('Nöbetçi Eczane') }}</h3>

    @if(!empty($pharmacies ?? []))
        <ul class="space-y-2 text-sm">
            @foreach(array_slice($pharmacies, 0, 2) as $pharmacy)
                <li class="border-b border-adh-border pb-2 last:border-b-0 last:pb-0 dark:border-gray-700">
                    <p class="line-clamp-1 text-[13px] font-semibold leading-5 text-adh-text dark:text-gray-100">{{ $pharmacy['name'] ?? '-' }}</p>
                    @if(!empty($pharmacy['address']))
                        <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-adh-gray dark:text-gray-400">{{ $pharmacy['address'] }}</p>
                    @endif
                    @if(!empty($pharmacy['phone']))
                        <p class="mt-0.5 text-[11px] font-medium text-adh-gray dark:text-gray-400">{{ $pharmacy['phone'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <div class="rounded border border-dashed border-adh-border bg-slate-50 px-3 py-3 text-xs text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Nöbetçi eczane verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 leading-5">{{ __('Günlük veri yenilendiğinde Adıyaman için güncel nöbetçi eczaneler burada gösterilecektir.') }}</p>
        </div>
    @endif
</section>
