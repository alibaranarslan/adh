<x-filament-panels::page class="fi-dashboard-page">
    @php
        $toneClasses = [
            'amber' => 'border-amber-200/70 bg-amber-50/80 text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100',
            'slate' => 'border-slate-200/80 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
            'emerald' => 'border-emerald-200/70 bg-emerald-50/80 text-emerald-900 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100',
            'rose' => 'border-rose-200/70 bg-rose-50/80 text-rose-900 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100',
            'sky' => 'border-sky-200/70 bg-sky-50/80 text-sky-900 dark:border-sky-400/20 dark:bg-sky-400/10 dark:text-sky-100',
        ];
    @endphp

    <div class="space-y-6">
        <section data-tour-anchor="dashboard.hero" class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-6 py-6 text-white shadow-[0_28px_60px_-30px_rgba(15,23,42,0.75)] lg:px-8">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,0.9fr)]">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-amber-300/25 bg-amber-300/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-100">Canli yayin kontrol yuzeyi</span>
                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-200">Editoryal ve operasyonel rehber</span>
                    </div>

                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-[2rem]">Gunun islerini tek ekrandan gorun</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                        Bu ekran; haber akisina, anasayfa duzenine ve sistem sagligina dagilmadan hakim olmaniz icin hazirlandi.
                        En sik kullanilan alanlara dogrudan gidin, ardindan detay ekranlarinda islemi tamamlayin.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($statusCards as $card)
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">{{ $card['label'] }}</p>
                            <p class="mt-3 text-lg font-semibold text-white">{{ $card['value'] }}</p>
                            <p class="mt-2 text-xs text-slate-300">{{ $card['meta'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,0.95fr)]">
            <div data-tour-anchor="dashboard.quick-actions" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Hızlı erişim</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">En sik kullanilan yonetim alanlarini one cikariyorum.</p>
                    </div>
                </div>

                <div data-tour-anchor="dashboard.guide-entry" class="mt-5 rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4 dark:border-amber-400/20 dark:bg-amber-400/10">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Yönetim Panelini Tanı</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Yeni kullanıcılar için önerilen başlangıç noktası budur. Bu yardım, panelde nereden başlamanız gerektiğini kısa bir turla gösterir.</p>
                        </div>

                        <button
                            type="button"
                            x-data="{}"
                            x-on:click="$dispatch('adh-admin-guide:start', { key: 'dashboard-overview' })"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300"
                        >
                            <x-filament::icon icon="heroicon-o-academic-cap" class="h-4 w-4" />
                            <span>Öğretici modu başlat</span>
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="group rounded-2xl border px-4 py-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $toneClasses[$action['tone']] ?? $toneClasses['slate'] }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-2">
                                    <p class="text-sm font-semibold">{{ $action['label'] }}</p>
                                    <p class="text-sm opacity-80">{{ $action['description'] }}</p>
                                </div>
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5 shrink-0 opacity-80" />
                            </div>

                            @if (filled($action['secondary_url'] ?? null))
                                <div class="mt-4">
                                    <span class="inline-flex items-center rounded-full border border-current/15 px-3 py-1 text-xs font-medium opacity-80">
                                        {{ $action['secondary_label'] }}
                                    </span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div data-tour-anchor="dashboard.workflow" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Onerilen calisma akisi</h3>
                <div class="mt-5 space-y-4">
                    @foreach ($workflowSteps as $step)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $step['title'] }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
            <div data-tour-anchor="dashboard.system" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Sistem gorunurlugu</h3>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($systemCards as $card)
                        <div class="rounded-2xl border px-4 py-4 {{ $toneClasses[$card['tone']] ?? $toneClasses['slate'] }}">
                            <p class="text-[11px] uppercase tracking-[0.22em] opacity-70">{{ $card['label'] }}</p>
                            <p class="mt-3 text-lg font-semibold">{{ $card['value'] }}</p>
                            <p class="mt-2 text-xs opacity-80">{{ $card['meta'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-tour-anchor="dashboard.alerts" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Uyarilar</h3>
                <div class="mt-5 space-y-3">
                    @forelse ($alerts as $alert)
                        <div class="rounded-2xl border px-4 py-4 {{ $toneClasses[$alert['tone']] ?? $toneClasses['slate'] }}">
                            <p class="text-sm font-semibold">{{ $alert['title'] }}</p>
                            <p class="mt-2 text-sm opacity-85">{{ $alert['text'] }}</p>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-4 text-emerald-900 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100">
                            <p class="text-sm font-semibold">Kritik uyari gorunmuyor</p>
                            <p class="mt-2 text-sm opacity-85">Temel sistem sinyalleri bu gorunumde saglikli gorunuyor.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getVisibleWidgets()"
        />
    </div>
</x-filament-panels::page>
