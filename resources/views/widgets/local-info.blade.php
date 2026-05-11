{{-- Yerel Duyurular --}}
@if(($localAnnouncements ?? collect())->isNotEmpty())
<section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg p-4 mt-4">
    <h3 class="font-serif text-base font-bold border-l-4 border-adh-red pl-3 mb-3 dark:text-gray-100">{{ __('Yerel Duyurular') }}</h3>
    <ul class="text-sm space-y-2.5">
        @foreach($localAnnouncements as $announcement)
        <li class="flex gap-2 {{ !$loop->first ? 'border-t border-adh-border dark:border-gray-700 pt-2' : '' }}">
            <span class="{{ in_array($announcement->type, ['power_outage', 'water_outage']) ? 'text-adh-red' : 'text-blue-500' }} mt-0.5 flex-shrink-0 text-xs">●</span>
            <div class="flex-1 min-w-0">
                <p class="text-adh-text dark:text-gray-200 text-xs leading-relaxed font-semibold">{{ $announcement->title }}</p>
                @if($announcement->content)
                <p class="text-adh-text dark:text-gray-300 text-xs leading-relaxed mt-0.5">{{ $announcement->content }}</p>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
</section>
@endif
