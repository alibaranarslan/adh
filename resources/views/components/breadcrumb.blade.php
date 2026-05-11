@props(['items' => []])

<nav aria-label="Breadcrumb" class="text-sm text-adh-gray mb-4">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-2">
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-adh-red">{{ $item['label'] }}</a>
                @else
                    <span class="text-adh-text dark:text-gray-100">{{ $item['label'] }}</span>
                @endif
                @if ($index !== count($items) - 1)
                    <span>/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
