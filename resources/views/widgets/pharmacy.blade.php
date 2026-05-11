<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-adh-blue rounded p-4">
    <h3 class="font-serif text-lg mb-3 border-t-2 border-adh-red pt-2 dark:text-gray-100">{{ __('Nöbetçi Eczane') }}</h3>
    @if(!empty($pharmacies ?? []))
        <ul class="text-sm space-y-3">
            @foreach(array_slice($pharmacies, 0, 3) as $pharmacy)
                <li class="border-b border-adh-border dark:border-gray-700 pb-2 last:border-b-0 last:pb-0">
                    <p class="font-semibold text-adh-text dark:text-gray-100">{{ $pharmacy['name'] ?? '-' }}</p>
                    @if(!empty($pharmacy['address']))
                        <p class="text-xs text-adh-gray dark:text-gray-400 mt-0.5">{{ $pharmacy['address'] }}</p>
                    @endif
                    @if(!empty($pharmacy['phone']))
                        <p class="text-xs text-adh-gray dark:text-gray-400 mt-0.5">{{ $pharmacy['phone'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm leading-6 text-adh-gray dark:text-gray-400">
            {{ __('Nöbetçi eczane bilgisi şu anda gösterilemiyor. API entegrasyonu veya günlük veri güncellemesi bekleniyor.') }}
        </p>
    @endif
</section>
