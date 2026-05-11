<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-adh-blue rounded p-4">
    <h3 class="font-serif text-lg mb-3 border-t-2 border-adh-red pt-2 dark:text-gray-100">{{ __('Nöbetçi Eczane') }}</h3>

    @if(!empty($pharmacies ?? []))
        <ul class="space-y-3 text-sm">
            @foreach(array_slice($pharmacies, 0, 3) as $pharmacy)
                <li class="border-b border-adh-border pb-2 last:border-b-0 last:pb-0 dark:border-gray-700">
                    <p class="font-semibold text-adh-text dark:text-gray-100">{{ $pharmacy['name'] ?? '-' }}</p>
                    @if(!empty($pharmacy['address']))
                        <p class="mt-0.5 text-xs text-adh-gray dark:text-gray-400">{{ $pharmacy['address'] }}</p>
                    @endif
                    @if(!empty($pharmacy['phone']))
                        <p class="mt-0.5 text-xs text-adh-gray dark:text-gray-400">{{ $pharmacy['phone'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <div class="rounded-lg border border-dashed border-adh-border bg-slate-50 px-4 py-5 text-sm text-adh-gray dark:border-gray-700 dark:bg-adh-navy/40 dark:text-gray-300">
            <p class="font-medium text-adh-text dark:text-gray-100">{{ __('Nöbetçi eczane verisi şu anda güncellenemiyor.') }}</p>
            <p class="mt-1 text-xs">{{ __('Günlük veri yenilendiğinde Adıyaman için güncel nöbetçi eczaneler burada gösterilecektir.') }}</p>
        </div>
    @endif
</section>
