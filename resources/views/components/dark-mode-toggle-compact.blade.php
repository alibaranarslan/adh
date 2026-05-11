<button
    type="button"
    class="inline-flex items-center justify-center gap-1 rounded border border-white/30 px-2 py-1 text-xs"
    @click="$store.darkMode.toggle()"
    :aria-label="$store.darkMode.on ? '{{ __('Açık Mod') }}' : '{{ __('Koyu Mod') }}'"
    :title="$store.darkMode.on ? '{{ __('Açık Mod') }}' : '{{ __('Koyu Mod') }}'"
>
    <svg x-show="!$store.darkMode.on" class="h-3.5 w-3.5 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9 9 0 1012 21a9 9 0 008.354-5.646z"/>
    </svg>
    <svg x-show="$store.darkMode.on" x-cloak class="h-3.5 w-3.5 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.25M12 18.75V21M4.97 4.97l1.59 1.59M17.44 17.44l1.59 1.59M3 12h2.25M18.75 12H21M4.97 19.03l1.59-1.59M17.44 6.56l1.59-1.59M12 7.5A4.5 4.5 0 1112 16.5 4.5 4.5 0 0112 7.5z"/>
    </svg>
    <span class="sr-only md:not-sr-only" x-show="!$store.darkMode.on">{{ __('Koyu Mod') }}</span>
    <span class="sr-only md:not-sr-only" x-show="$store.darkMode.on" x-cloak>{{ __('Açık Mod') }}</span>
</button>
