@props(['items' => []])

<nav aria-label="Breadcrumb" class="text-sm text-adh-gray mb-4">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $index => $item)
            @php
                $isLast = $index === count($items) - 1;
                $hideLongCurrentOnMobile = $isLast && empty($item['url']);
                $nextItem = $items[$index + 1] ?? null;
                $hideSeparatorOnMobile = $nextItem && ($index + 1 === count($items) - 1) && empty($nextItem['url']);
            @endphp
            <li class="flex items-center gap-2 {{ $hideLongCurrentOnMobile ? 'hidden sm:flex' : '' }}">
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-adh-red">{{ $item['label'] }}</a>
                @else
                    <span class="text-adh-text dark:text-gray-100">{{ $item['label'] }}</span>
                @endif
                @if (! $isLast)
                    <span class="{{ $hideSeparatorOnMobile ? 'hidden sm:inline' : '' }}">/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
