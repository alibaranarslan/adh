<button
    type="button"
    class="text-xs border border-white/30 rounded px-2 py-1"
    @click="$store.darkMode.toggle()"
    aria-label="{{ __('Koyu Mod') }}"
>
    <span x-show="!$store.darkMode.on">{{ __('Koyu Mod') }}</span>
    <span x-show="$store.darkMode.on">{{ __('Açık Mod') }}</span>
</button>
