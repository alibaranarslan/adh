<nav class="sticky top-0 z-20 hidden border-b border-adh-border bg-white shadow-sm dark:border-adh-blue dark:bg-adh-blue md:block"
     x-data="{ illerOpen: false }">
    <div class="max-w-7xl mx-auto px-4">
        <div class="overflow-x-auto scrollbar-hide">
            <ul class="flex items-center gap-0.5 min-w-max py-0">
                <li>
                    <a href="{{ \App\Support\LocalizedUrl::route('home') }}"
                       class="inline-block text-sm font-medium px-3 py-3 border-b-2 transition-colors
                              {{ request()->routeIs('home')
                                  ? 'text-adh-red border-adh-red'
                                  : 'text-adh-text dark:text-gray-100 border-transparent hover:text-adh-red hover:border-adh-red' }}">
                        {{ __('Ana Sayfa') }}
                    </a>
                </li>

                @foreach ($navCategories ?? collect() as $item)
                    @php $catSlug = data_get($item, 'slug'); @endphp
                    <li>
                        <a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => $catSlug]) }}"
                           class="inline-block text-sm font-medium px-3 py-3 border-b-2 transition-colors
                                  {{ request()->is("*/$catSlug*") || (request()->route('slug') === $catSlug)
                                      ? 'text-adh-red border-adh-red'
                                      : 'text-adh-text dark:text-gray-100 border-transparent hover:text-adh-red hover:border-adh-red' }}">
                            {{ data_get($item, 'name') }}
                        </a>
                    </li>
                @endforeach

                <li class="relative">
                    <button @click="illerOpen = !illerOpen"
                            @keydown.escape.window="illerOpen = false"
                            x-ref="illerBtn"
                            aria-haspopup="true"
                            :aria-expanded="illerOpen.toString()"
                            class="inline-flex items-center gap-1 text-sm font-medium px-3 py-3 border-b-2 transition-colors whitespace-nowrap
                                   {{ request()->routeIs('city.*')
                                       ? 'text-adh-red border-adh-red'
                                       : 'text-adh-text dark:text-gray-100 border-transparent hover:text-adh-red hover:border-adh-red' }}">
                        {{ __('İller') }}
                        <svg class="w-3 h-3 transition-transform duration-150"
                             :class="{ 'rotate-180': illerOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="illerOpen"
                         @click="illerOpen = false"
                         class="fixed inset-0 z-30"
                         style="display:none;"></div>

                    <div x-show="illerOpen"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         x-init="
                             $watch('illerOpen', val => {
                                 if (val) {
                                     const btn = $refs.illerBtn;
                                     const rect = btn.getBoundingClientRect();
                                     $el.style.left = Math.min(rect.left, window.innerWidth - 220) + 'px';
                                     $el.style.top = rect.bottom + 'px';
                                 }
                             })
                         "
                         class="fixed w-52 bg-white dark:bg-adh-blue border border-adh-border dark:border-gray-700 rounded-lg shadow-xl z-40 py-1 max-h-72 overflow-y-auto"
                         style="display:none;">
                        <a href="{{ \App\Support\LocalizedUrl::route('city.index') }}"
                           @click="illerOpen = false"
                           class="block px-4 py-2.5 text-xs font-semibold text-adh-red border-b border-adh-border dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            {{ __('Tüm İller') }} &rarr;
                        </a>
                        @foreach (\App\Services\IhaCategoryMapper::getActiveCities() as $citySlug => $cityLabel)
                            <a href="{{ \App\Support\LocalizedUrl::route('city.show', ['slug' => $citySlug]) }}"
                               @click="illerOpen = false"
                               class="block px-4 py-2 text-sm transition-colors
                                      {{ request()->is("*/il/$citySlug*") || (request()->route('slug') === $citySlug && request()->routeIs('city.show'))
                                          ? 'text-adh-red font-medium bg-adh-red/5'
                                          : 'text-adh-text dark:text-gray-300 hover:text-adh-red hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                {{ $cityLabel }}
                            </a>
                        @endforeach
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
