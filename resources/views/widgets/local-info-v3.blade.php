@if(($localAnnouncements ?? collect())->isNotEmpty())
<section class="h-full rounded-lg border border-adh-border bg-white p-3 dark:border-gray-700 dark:bg-adh-blue">
    <h3 class="mb-2 border-l-4 border-adh-red pl-2.5 font-serif text-sm font-bold dark:text-gray-100">{{ __('Yerel Duyurular') }}</h3>
    <ul class="space-y-2 text-sm">
        @foreach($localAnnouncements->take(3) as $announcement)
        <li class="flex gap-2 {{ !$loop->first ? 'border-t border-adh-border pt-2 dark:border-gray-700' : '' }}">
            <span class="{{ in_array($announcement->type, ['power_outage', 'water_outage']) ? 'text-adh-red' : 'text-blue-500' }} mt-0.5 flex-shrink-0 text-xs">&bull;</span>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-2 text-xs font-semibold leading-5 text-adh-text dark:text-gray-200">{{ $announcement->title }}</p>
                @if($announcement->content)
                <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-adh-text dark:text-gray-300">{{ $announcement->content }}</p>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
</section>
@endif
