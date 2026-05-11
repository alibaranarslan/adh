@if(($localAnnouncements ?? collect())->isNotEmpty())
<section class="mt-4 rounded-lg border border-adh-border bg-white p-4 dark:border-gray-700 dark:bg-adh-blue">
    <h3 class="mb-3 border-l-4 border-adh-red pl-3 font-serif text-base font-bold dark:text-gray-100">{{ __('Yerel Duyurular') }}</h3>
    <ul class="space-y-2.5 text-sm">
        @foreach($localAnnouncements as $announcement)
        <li class="flex gap-2 {{ !$loop->first ? 'border-t border-adh-border pt-2 dark:border-gray-700' : '' }}">
            <span class="{{ in_array($announcement->type, ['power_outage', 'water_outage']) ? 'text-adh-red' : 'text-blue-500' }} mt-0.5 flex-shrink-0 text-xs">&bull;</span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold leading-relaxed text-adh-text dark:text-gray-200">{{ $announcement->title }}</p>
                @if($announcement->content)
                <p class="mt-0.5 text-xs leading-relaxed text-adh-text dark:text-gray-300">{{ $announcement->content }}</p>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
</section>
@endif
