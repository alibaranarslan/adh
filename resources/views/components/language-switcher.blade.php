<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="inline-flex h-11 min-w-11 items-center justify-center rounded border border-white/30 px-2 text-xs font-semibold md:h-auto md:min-w-0 md:py-1"
        aria-label="{{ strtoupper(app()->getLocale()) }} {{ __('Dil seçenekleri') }}"
    >
        {{ strtoupper(app()->getLocale()) }}
    </button>
    <div x-show="open" @click.outside="open = false" class="absolute right-0 z-50 mt-2 w-24 rounded bg-white text-adh-text shadow">
        @php $query = request()->except('locale'); @endphp
        @foreach (['tr', 'en', 'ku'] as $lang)
            <a
                href="{{ \App\Support\LocalizedUrl::current($lang, $query) }}"
                class="block px-3 py-3 text-xs hover:bg-adh-gray-light {{ app()->getLocale() === $lang ? 'font-semibold text-adh-red' : '' }}"
            >
                {{ strtoupper($lang) }}
            </a>
        @endforeach
    </div>
</div>
