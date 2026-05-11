@php
    $user = auth()->user();
@endphp

@if ($user && \App\Support\AdminPrivileges::canAccessAdminPanel($user))
    @php
        $registry = app(\App\Support\AdminGuides\AdminGuideRegistry::class);
        $catalog = $registry->catalogForUser($user);
        $currentGuide = $registry->forRequest(request(), $user);
        $progress = \Illuminate\Support\Facades\Schema::hasTable('admin_guide_progress')
            ? \App\Models\AdminGuideProgress::query()
                ->where('user_id', $user->getKey())
                ->get()
                ->mapWithKeys(fn (\App\Models\AdminGuideProgress $item): array => [
                    $item->guide_key => [
                        'status' => $item->status,
                        'last_step_index' => (int) $item->last_step_index,
                        'completed_at' => $item->completed_at?->toIso8601String(),
                        'dismissed_at' => $item->dismissed_at?->toIso8601String(),
                        'meta' => $item->meta ?? [],
                    ],
                ])
                ->all()
            : [];
    @endphp

    <div
        x-data="adminGuideShell({
            catalog: {{ \Illuminate\Support\Js::from($catalog) }},
            currentGuideKey: @js($currentGuide['guide_key'] ?? null),
            progressMap: {{ \Illuminate\Support\Js::from($progress) }},
            updateUrl: @js(route('admin.guides.progress.store')),
            csrfToken: @js(csrf_token()),
        })"
        x-cloak
        class="adh-admin-guide-root"
    >
        <div
            x-show="panelOpen"
            x-transition.opacity
            class="adh-admin-guide-panel-backdrop fixed inset-0 z-[120] bg-slate-950/50 backdrop-blur-[1px]"
            x-on:click="closePanel()"
        ></div>

        <aside
            x-show="panelOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="adh-admin-guide-panel fixed inset-y-0 right-0 z-[130] flex w-full max-w-xl flex-col overflow-hidden border-l border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-950"
            role="dialog"
            aria-modal="true"
            aria-labelledby="adh-admin-guide-title"
        >
            <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-300">Öğretici Mod</p>
                        <h2 id="adh-admin-guide-title" class="text-lg font-semibold text-slate-900 dark:text-white">Bu ekrani hizlica taniyin</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Rehberler rolünüze göre filtrelenir; yalnız erişebildiğiniz ekranları görürsünüz.</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white"
                        x-on:click="closePanel()"
                        aria-label="Yardım panelini kapat"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto px-5 py-5">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-amber-200/80 bg-amber-50/80 p-5 dark:border-amber-400/15 dark:bg-amber-400/10">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border border-amber-300/40 bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-700 dark:border-amber-400/20 dark:bg-slate-950/40 dark:text-amber-200" x-text="currentGuide ? 'Bu sayfa ne ise yarar?' : 'Genel katalog'"></span>
                            <template x-if="currentGuideStatusLabel()">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200" x-text="currentGuideStatusLabel()"></span>
                            </template>
                        </div>

                        <div class="mt-4 space-y-3" x-show="currentGuide">
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white" x-text="currentGuide?.title"></h3>
                            <p class="text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="currentGuide?.summary"></p>
                            <div class="rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                <span class="font-semibold text-slate-900 dark:text-white">Etkisi:</span>
                                <span x-text="currentGuide?.impact"></span>
                            </div>
                            <div class="rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                <span class="font-semibold text-slate-900 dark:text-white">Neden onemli?</span>
                                <span x-text="currentGuide?.why_it_matters"></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300"
                                    x-on:click="startGuide(currentGuide.guide_key)"
                                >
                                    <x-filament::icon icon="heroicon-o-play" class="h-4 w-4" />
                                    <span>Sayfa turunu baslat</span>
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500 dark:hover:bg-slate-900"
                                    x-on:click="markDismissed(currentGuide.guide_key)"
                                >
                                    <x-filament::icon icon="heroicon-o-eye-slash" class="h-4 w-4" />
                                    <span>Simdilik kapat</span>
                                </button>
                            </div>
                        </div>
                    </section>

                    <section x-show="currentGuide?.quick_actions?.length">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">En sik yapilan isler</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Bu sayfayla baglantili sik kullanilan aksiyonlar.</p>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <template x-for="action in (currentGuide?.quick_actions ?? [])" :key="action.label">
                                <a :href="action.url" class="rounded-2xl border border-slate-200 bg-white px-4 py-4 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-50/50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-amber-400/30 dark:hover:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="action.label"></p>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" x-text="action.description"></p>
                                </a>
                            </template>
                        </div>
                    </section>

                    <section x-show="currentGuide?.steps?.length">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Bu ekranda nasil calisilir?</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kısa görev odaklı özet.</p>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="(step, index) in (currentGuide?.steps ?? [])" :key="`${currentGuide?.guide_key}-${index}`">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="`${index + 1}. ${step.title}`"></p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="step.description"></p>
                                </div>
                            </template>
                        </div>
                    </section>

                    <section>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Diger rehberler</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Rolunuze acik admin yuzeyleri.</p>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <template x-for="guide in catalog" :key="guide.guide_key">
                                <button
                                    type="button"
                                    class="rounded-2xl border px-4 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm"
                                    :class="guide.guide_key === activeGuideKey ? 'border-amber-300 bg-amber-50/70 dark:border-amber-400/30 dark:bg-amber-400/10' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-500'"
                                    x-on:click="selectGuide(guide.guide_key)"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="guide.title"></p>
                                        <template x-if="progressLabel(guide.guide_key)">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300" x-text="progressLabel(guide.guide_key)"></span>
                                        </template>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="guide.summary"></p>
                                </button>
                            </template>
                        </div>
                    </section>
                </div>
            </div>
        </aside>

        <div x-show="tourActive" x-transition.opacity class="adh-admin-guide-tour fixed inset-0 z-[140] flex items-start justify-center bg-slate-950/70 px-4 py-6">
            <div class="absolute inset-0" x-on:click="dismissTour()"></div>

            <div
                x-show="tourActive && activeTargetVisible"
                x-transition.opacity.duration.150ms
                class="pointer-events-none absolute rounded-3xl border-2 border-amber-300 bg-transparent shadow-[0_0_0_9999px_rgba(2,6,23,0.65)] transition-all duration-150"
                :style="highlightStyle"
            ></div>

            <div class="adh-admin-guide-popover relative z-[150] w-full max-w-md rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-950" :style="coachmarkStyle">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-300" x-text="tourStepLabel()"></p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900 dark:text-white" x-text="activeCoachmark()?.title ?? currentGuide?.title"></h3>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white"
                        x-on:click="dismissTour()"
                        aria-label="Turu kapat"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="activeCoachmark()?.body ?? currentGuide?.summary"></p>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500 dark:hover:bg-slate-900"
                            x-on:click="previousStep()"
                            :disabled="currentStepIndex === 0"
                        >
                            Geri
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500 dark:hover:bg-slate-900"
                            x-on:click="dismissTour()"
                        >
                            Simdilik kapat
                        </button>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300"
                        x-on:click="advanceTour()"
                    >
                        <span x-text="isLastStep() ? 'Turu tamamla' : 'Ileri'"></span>
                        <x-filament::icon icon="heroicon-o-arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
