<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="text-xs border border-white/30 rounded px-1.5 sm:px-2 py-1"
        aria-label="{{ strtoupper(app()->getLocale()) }} {{ __('Dil seçenekleri') }}"
    >
        {{ strtoupper(app()->getLocale()) }}
    </button>
    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-20 bg-white text-adh-text rounded shadow z-30">
        @php
            $currentPath = request()->path();
            $strippedPath = preg_replace('/^(tr|en|ku)(\/|$)/', '', $currentPath);
            $strippedPath = ltrim((string) $strippedPath, '/');
            $pathSuffix = $strippedPath === '' ? '' : '/'.$strippedPath;
            $query = request()->except('locale');
            $querySuffix = $query !== [] ? '?'.http_build_query($query) : '';
        @endphp
        @foreach (['tr', 'en', 'ku'] as $lang)
            <a
                href="{{ url($lang.$pathSuffix).$querySuffix }}"
                class="block px-3 py-2 text-xs hover:bg-adh-gray-light {{ app()->getLocale() === $lang ? 'font-semibold text-adh-red' : '' }}"
            >
                {{ strtoupper($lang) }}
            </a>
        @endforeach
    </div>
</div>
