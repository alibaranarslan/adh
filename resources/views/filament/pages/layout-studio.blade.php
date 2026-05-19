@php
    $selectedModule = $this->getSelectedModuleState();
    $selectedModuleIndex = $this->getSelectedModuleIndex();
    $publishedRevision = $this->getPublishedRevision();
    $draftRevision = $this->getDraftRevision();
    $previewUrls = $this->getPreviewUrls();
    $revisionOptions = $this->getRevisionOptions();
    $layoutReadiness = $this->getLayoutReadiness();
    $previewDevice = $this->previewDevice;
    $previewFrameClass = $previewDevice === 'mobile'
        ? 'mx-auto h-[720px] w-[390px] max-w-full'
        : 'h-[720px] w-full';
    $moduleCount = count($modules);
    $activeModuleCount = collect($modules)->where('is_active', true)->count();
    $selectedSettings = $selectedModule['settings'] ?? [];
    $draftSyncLabel = $this->draftSyncLabel();
    $publishAuthorityLabel = $this->publishAuthorityLabel();
    $readinessStatusLabel = $this->readinessStatusLabel();
    $previewFreshnessLabel = $this->previewFreshnessLabel();
    $variantLabels = [
        'default' => 'Varsayılan',
        'editorial' => 'Editoryal',
        'cards' => 'Kartlar',
        'lead-with-list' => 'Öne çıkan liste',
        'feature-list' => 'Özellik listesi',
        'ranked-list' => 'Sıralı liste',
        'shortcut-grid' => 'Kısayol ızgarası',
        'stack' => 'Yığın',
        'slots' => 'Alanlar',
    ];
    $backgroundToneLabels = [
        'surface' => 'Yüzey',
        'muted' => 'Sade',
        'contrast' => 'Kontrast',
    ];
@endphp

<x-filament-panels::page>
    @once
        <style>
            .adh-layout-shell {
                --studio-bg: #0f172a;
                --studio-surface: #111c30;
                --studio-line: rgba(148, 163, 184, 0.18);
                --studio-accent: #f59e0b;
                --studio-accent-soft: rgba(245, 158, 11, 0.14);
            }

            .adh-layout-shell .studio-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1.75rem;
                border: 1px solid var(--studio-line);
                background:
                    radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 32%),
                    linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(17, 24, 39, 0.94));
                color: #e2e8f0;
                box-shadow: 0 28px 60px rgba(15, 23, 42, 0.2);
            }

            .adh-layout-shell .studio-hero::after {
                content: "";
                position: absolute;
                inset: auto -12% -24% auto;
                width: 18rem;
                height: 18rem;
                border-radius: 9999px;
                background: rgba(245, 158, 11, 0.1);
                filter: blur(8px);
            }

            .adh-layout-shell .studio-stat,
            .adh-layout-shell .studio-panel,
            .adh-layout-shell .studio-module {
                border-radius: 1.5rem;
            }

            .adh-layout-shell .studio-stat {
                border: 1px solid rgba(148, 163, 184, 0.14);
                background: rgba(15, 23, 42, 0.36);
                backdrop-filter: blur(12px);
            }

            .adh-layout-shell .studio-chip {
                border-radius: 9999px;
                border: 1px solid rgba(245, 158, 11, 0.2);
                background: rgba(245, 158, 11, 0.08);
                color: #fbbf24;
            }

            .adh-layout-shell .studio-panel {
                border: 1px solid rgba(15, 23, 42, 0.08);
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
                box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
            }

            .dark .adh-layout-shell .studio-panel {
                border-color: rgba(148, 163, 184, 0.14);
                background: linear-gradient(180deg, rgba(15, 23, 42, 0.86), rgba(15, 23, 42, 0.68));
                box-shadow: none;
            }

            .adh-layout-shell .studio-module {
                position: relative;
                overflow: hidden;
            }

            .adh-layout-shell .studio-module[draggable="true"] {
                cursor: grab;
            }

            .adh-layout-shell .studio-module.is-dragging {
                opacity: 0.6;
                transform: scale(0.99);
            }

            .adh-layout-shell .studio-module::before {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                width: 0.35rem;
                background: transparent;
                transition: background-color 160ms ease;
            }

            .adh-layout-shell .studio-module.is-selected::before {
                background: linear-gradient(180deg, #f59e0b, #fb923c);
            }

            .adh-layout-shell .studio-section-note {
                border-radius: 1rem;
                border: 1px dashed rgba(245, 158, 11, 0.28);
                background: rgba(245, 158, 11, 0.07);
                color: #92400e;
            }

            .dark .adh-layout-shell .studio-section-note {
                color: #fcd34d;
            }

            .adh-layout-shell .studio-preview-frame {
                overflow: hidden;
                border-radius: 1.35rem;
                border: 1px solid rgba(15, 23, 42, 0.12);
                background: #ffffff;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            }

            .dark .adh-layout-shell .studio-preview-frame {
                border-color: rgba(148, 163, 184, 0.18);
                background: #0f172a;
                box-shadow: none;
            }

            .adh-layout-shell .studio-readiness-item {
                border-radius: 0.9rem;
                border: 1px solid rgba(148, 163, 184, 0.18);
                padding: 0.65rem 0.85rem;
            }
        </style>
    @endonce

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let draggedItem = null;

                document.addEventListener('dragstart', (event) => {
                    const item = event.target.closest('[data-sort-item]');

                    if (!item) {
                        return;
                    }

                    draggedItem = item;
                    item.classList.add('is-dragging');

                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', item.dataset.moduleId || '');
                    }
                });

                document.addEventListener('dragover', (event) => {
                    const container = event.target.closest('[data-layout-sortable]');

                    if (!container || !draggedItem) {
                        return;
                    }

                    event.preventDefault();

                    const siblings = [...container.querySelectorAll('[data-sort-item]:not(.is-dragging)')];
                    const nextItem = siblings.find((element) => {
                        const rect = element.getBoundingClientRect();

                        return event.clientY <= rect.top + (rect.height / 2);
                    });

                    if (nextItem) {
                        container.insertBefore(draggedItem, nextItem);
                    } else {
                        container.appendChild(draggedItem);
                    }
                });

                document.addEventListener('drop', (event) => {
                    const container = event.target.closest('[data-layout-sortable]');

                    if (!container || !draggedItem) {
                        return;
                    }

                    event.preventDefault();

                    const root = container.closest('[wire\\:id]');
                    const componentId = root?.getAttribute('wire:id');
                    const order = [...container.querySelectorAll('[data-sort-item]')].map((element) => element.dataset.moduleId);

                    if (componentId && window.Livewire) {
                        window.Livewire.find(componentId)?.call('updateModuleOrder', order);
                    }
                });

                document.addEventListener('dragend', (event) => {
                    const item = event.target.closest('[data-sort-item]');

                    if (item) {
                        item.classList.remove('is-dragging');
                    }

                    draggedItem = null;
                });
            });
        </script>
    @endonce

    <div class="adh-layout-shell space-y-6">
        <x-filament::section>
            <div data-tour-anchor="layout.hero">
            <div class="studio-hero p-6 lg:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex flex-wrap gap-2">
                            <span class="studio-chip inline-flex items-center px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em]">Taslak -> Önizleme -> Yayın</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em]">{{ $activeModuleCount }}/{{ $moduleCount }} aktif modül</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em]">{{ $draftSyncLabel }}</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em]">{{ $publishAuthorityLabel }}</span>
                        </div>

                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-[2rem]">Anasayfa Yerleşim Stüdyosu</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                            Editoryal blok akışını, görünüm belirteçlerini ve yayın kontrolünü tek bir çalışma yüzeyinde yönetin. Taslaklar burada
                            şekillenir, önizleme ile doğrulanır ve onaydan sonra güvenli biçimde canlıya aktarılır.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2 text-xs text-slate-200/90">
                            <span class="studio-chip inline-flex items-center px-3 py-1">Manset akışı korunur</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1">TR / EN / KU önizleme hazır</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1">Geri alma destekli yayın</span>
                            <span class="studio-chip inline-flex items-center px-3 py-1">{{ $readinessStatusLabel }}</span>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 lg:min-w-[39rem]">
                        <div class="studio-stat p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Taslak</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ $draftRevision->updated_at?->format('d.m.Y H:i') ?? 'Hazır' }}</p>
                            <p class="mt-2 text-xs text-slate-300">{{ $draftSyncLabel }}</p>
                        </div>

                        <div class="studio-stat p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Canlı Sürüm</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ $publishedRevision?->published_at?->format('d.m.Y H:i') ?? 'Henüz yok' }}</p>
                            <p class="mt-2 text-xs text-slate-300">{{ $publishedRevision?->name ?? 'İlk yayın bekleniyor' }}</p>
                        </div>

                        <div class="studio-stat p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Yayın Yetkisi</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ $publishAuthorityLabel }}</p>
                            <p class="mt-2 text-xs text-slate-300">Editör taslak hazırlayabilir; canlıya alma süper admin yetkisindedir.</p>
                        </div>

                        <div class="studio-stat p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Modül Durumu</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ $moduleCount }} blok / {{ $activeModuleCount }} aktif</p>
                            <p class="mt-2 text-xs text-slate-300">Blok sırası taslakta güncellenir, yayın anında tek seferde uygulanır.</p>
                        </div>

                        <div class="studio-stat p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">Önizleme</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($previewUrls as $locale => $url)
                                    <a href="{{ $url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[11px] font-semibold text-white transition hover:border-amber-300/70 hover:bg-amber-300/10">
                                        {{ strtoupper($locale) }} önizleme
                                    </a>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-300">{{ $previewFreshnessLabel }}.</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Canlı Önizleme ve Yayın Hazırlığı" description="Kayıtlı taslak aynı ekranda imzalı public önizleme ile gösterilir. Kaydedilmemiş değişiklikler önce taslağa yazılmalıdır.">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,0.55fr)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <div class="studio-panel px-5 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600 dark:text-amber-300">Yayına hazır mı?</p>
                                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                                    {{ $layoutReadiness['status'] === 'ready' ? 'Taslak yayınlanabilir' : 'Yayın kalite kapısı blokluyor' }}
                                </h3>
                            </div>

                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $layoutReadiness['status'] === 'ready' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' }}">
                                {{ strtoupper($layoutReadiness['status']) }}
                            </span>
                        </div>

                        @if ($this->hasUnsavedChanges)
                            <div class="studio-readiness-item mt-4 bg-amber-50 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">
                                Önizleme son kaydedilen taslağı gösteriyor. Bu ekrandaki değişiklikleri görmek için önce taslağı kaydedin.
                            </div>
                        @endif

                        @if ($layoutReadiness['errors'])
                            <div class="mt-4 space-y-2">
                                @foreach ($layoutReadiness['errors'] as $error)
                                    <div class="studio-readiness-item bg-red-50 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-200">{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if ($layoutReadiness['warnings'])
                            <div class="mt-4 space-y-2">
                                @foreach ($layoutReadiness['warnings'] as $warning)
                                    <div class="studio-readiness-item bg-amber-50 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">{{ $warning }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="studio-panel px-5 py-5">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Önizleme cihazı</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Desktop ve 390px mobil kırılım aynı imzalı taslak URL'sinden kontrol edilir.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-filament::button type="button" size="sm" color="{{ $previewDevice === 'desktop' ? 'primary' : 'gray' }}" wire:click="setPreviewDevice('desktop')">
                                Desktop
                            </x-filament::button>
                            <x-filament::button type="button" size="sm" color="{{ $previewDevice === 'mobile' ? 'primary' : 'gray' }}" wire:click="setPreviewDevice('mobile')">
                                Mobil 390px
                            </x-filament::button>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($previewUrls as $locale => $url)
                                <a href="{{ $url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full border border-amber-200 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:border-amber-400 hover:bg-amber-50 dark:border-amber-400/20 dark:text-amber-300 dark:hover:bg-amber-400/10">
                                    {{ strtoupper($locale) }} harici önizleme
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="studio-panel px-4 py-4">
                    <div class="{{ $previewFrameClass }} studio-preview-frame">
                        <iframe
                            src="{{ $previewUrls['tr'] ?? '#' }}"
                            title="Layout Studio canlı önizleme"
                            class="h-full w-full bg-white"
                            loading="lazy"
                        ></iframe>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.35fr)]">
            <x-filament::section heading="Modül Akışı" description="Modülleri seçin, yerel sıra kontrolleriyle taslakta yukarı-aşağı taşıyın ve kaydettikten sonra önizleme alın.">
                <div data-tour-anchor="layout.modules">
                <div class="studio-section-note mb-4 px-4 py-3 text-sm leading-6">
                    Manşet düzeni ve haber akışı burada yeniden kurgulanır. Bir modül seçin, önce taslakta yerini ve yoğunluğunu düzenleyin,
                    sonra önizleme ile yayına çıkacak akışı kontrol edin.
                </div>

                <div class="space-y-3" data-layout-sortable>
                    @foreach ($modules as $module)
                        @php
                            $moduleSettings = $module['settings'] ?? [];
                        @endphp
                        <div
                            data-module-id="{{ $module['id'] }}"
                            data-sort-item
                            draggable="true"
                            class="studio-module {{ $selectedModule && $selectedModule['key'] === $module['key'] ? 'is-selected border-amber-300 bg-amber-50/70 shadow-sm dark:border-amber-400/40 dark:bg-amber-500/10' : 'border-gray-200 bg-white hover:border-amber-200 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-amber-400/30 dark:hover:bg-slate-900/70' }} flex w-full items-start justify-between gap-3 border px-4 py-4 text-left transition"
                        >
                            <button
                                type="button"
                                wire:click="selectModule('{{ $module['key'] }}')"
                                class="flex min-w-0 flex-1 items-start gap-3 text-left"
                            >
                                <span class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200/70 bg-amber-50 text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300">
                                    {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-gray-950 dark:text-white">{{ $module['name'] }}</p>
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-300">{{ $module['key'] }}</span>
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">{{ $variantLabels[data_get($moduleSettings, 'variant', 'default')] ?? 'Varsayılan' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400">{{ $module['description'] }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                        <span class="rounded-full border border-gray-200 px-2.5 py-1 dark:border-gray-700">Masaüstü {{ data_get($moduleSettings, 'columns_desktop', 1) }} kolon</span>
                                        <span class="rounded-full border border-gray-200 px-2.5 py-1 dark:border-gray-700">İçerik {{ data_get($moduleSettings, 'content_limit', 6) }}</span>
                                        <span class="rounded-full border border-gray-200 px-2.5 py-1 dark:border-gray-700">{{ $backgroundToneLabels[data_get($moduleSettings, 'background_tone', 'surface')] ?? 'Yüzey' }}</span>
                                    </div>
                                </div>
                            </button>

                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                <x-filament::button type="button" size="sm" color="gray" wire:click="moveModuleUp({{ $module['id'] }})">
                                    Yukarı
                                </x-filament::button>
                                <x-filament::button type="button" size="sm" color="gray" wire:click="moveModuleDown({{ $module['id'] }})">
                                    Aşağı
                                </x-filament::button>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $module['is_active'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ $module['is_active'] ? 'Aktif' : 'Pasif' }}
                                </span>
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="{{ $module['is_active'] ? 'gray' : 'success' }}"
                                    wire:click.stop="toggleModule({{ $module['id'] }})"
                                >
                                    {{ $module['is_active'] ? 'Kapat' : 'Aç' }}
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
                </div>
            </x-filament::section>

            <div class="space-y-6">
                <x-filament::section heading="Seçili Modül Ayarları" description="Her blok için görünüm, yoğunluk, buton ve cihaz kırılımlarını bu panelden yönetin.">
                    <div data-tour-anchor="layout.settings">
                    @if ($selectedModule && $selectedModuleIndex !== null)
                        @php
                            $fieldBase = 'modules.'.$selectedModuleIndex.'.settings';
                        @endphp

                        <div class="space-y-6">
                            <div class="studio-panel grid gap-4 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto]">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600 dark:text-amber-300">Seçili blok</p>
                                    <h3 class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $selectedModule['name'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        Bu panelde blok stili, cihaz kırılımları ve çok dilli metin özelleştirmeleri birlikte yönetilir.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2 self-start text-[11px]">
                                    <span class="rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $variantLabels[data_get($selectedSettings, 'variant', 'default')] ?? 'Varsayılan' }}</span>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ $selectedModule['is_active'] ? 'Aktif' : 'Pasif' }}</span>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">Masaüstü {{ data_get($selectedSettings, 'columns_desktop', 1) }}</span>
                                </div>
                            </div>

                            @php
                                $selectedWarnings = $this->getSelectedModuleWarnings();
                            @endphp

                            <div class="studio-panel space-y-4 px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Hızlı Ön Ayarlar</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tek tek alan doldurmadan modülü dengeli, öne çıkan veya kompakt bir kurguya taşıyın.</p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <x-filament::button type="button" size="sm" color="gray" wire:click="applyModulePreset('balanced')">Dengeli</x-filament::button>
                                        <x-filament::button type="button" size="sm" color="warning" wire:click="applyModulePreset('feature')">Öne Çıkan</x-filament::button>
                                        <x-filament::button type="button" size="sm" color="gray" wire:click="applyModulePreset('compact')">Kompakt</x-filament::button>
                                        <x-filament::button type="button" size="sm" color="danger" wire:click="resetSelectedModuleSettings">Varsayılana Dön</x-filament::button>
                                    </div>
                                </div>

                                @if ($selectedWarnings !== [])
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                                        <p class="font-semibold">Dikkat edilmesi gereken noktalar</p>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($selectedWarnings as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Canlıda etkin olan temel kararlar: sıra, görünürlük, başlık, alt başlık, buton, içerik limiti, kapsayıcı genişliği, arka plan tonu ve iç boşluk.
                                    Kolon ve görsel oranı gibi alanlar ise ızgara veya görsel kullanan bloklarda etkisini gösterir.
                                </p>
                            </div>

                            <details open class="studio-panel px-5 py-4">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">Temel yerleşim ayarları</summary>
                                <div class="mt-4 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Yerleşim türü</span>
                                    <select wire:model.defer="{{ $fieldBase }}.variant" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="default">Varsayılan</option>
                                        <option value="editorial">Editoryal</option>
                                        <option value="cards">Kartlar</option>
                                        <option value="lead-with-list">Öne çıkan liste</option>
                                        <option value="feature-list">Özellik listesi</option>
                                        <option value="ranked-list">Sıralı liste</option>
                                        <option value="shortcut-grid">Kısayol ızgarası</option>
                                        <option value="stack">Yığın</option>
                                        <option value="slots">Alanlar</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Arka plan tonu</span>
                                    <select wire:model.defer="{{ $fieldBase }}.background_tone" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="surface">Yüzey</option>
                                        <option value="muted">Sade</option>
                                        <option value="contrast">Kontrast</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Vurgu modu</span>
                                    <select wire:model.defer="{{ $fieldBase }}.accent_mode" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="brand">Marka</option>
                                        <option value="neutral">Nötr</option>
                                        <option value="alert">Uyarı</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">İç boşluk ölçeği</span>
                                    <select wire:model.defer="{{ $fieldBase }}.padding_scale" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="compact">Sıkı</option>
                                        <option value="regular">Standart</option>
                                        <option value="relaxed">Ferah</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Görsel oranı</span>
                                    <select wire:model.defer="{{ $fieldBase }}.image_ratio" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="16:9">16:9</option>
                                        <option value="16:10">16:10</option>
                                        <option value="4:3">4:3</option>
                                        <option value="1:1">1:1</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Kart yoğunluğu</span>
                                    <select wire:model.defer="{{ $fieldBase }}.card_density" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="compact">Sıkı</option>
                                        <option value="comfortable">Rahat</option>
                                        <option value="airy">Havadar</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Kapsayıcı</span>
                                    <select wire:model.defer="{{ $fieldBase }}.container_width" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="content">İçerik</option>
                                        <option value="wide">Geniş</option>
                                        <option value="full">Tam genişlik</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">İçerik limiti</span>
                                    <input type="number" min="1" max="24" wire:model.defer="{{ $fieldBase }}.content_limit" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mobil kolon</span>
                                    <input type="number" min="1" max="4" wire:model.defer="{{ $fieldBase }}.columns_mobile" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tablet kolon</span>
                                    <input type="number" min="1" max="6" wire:model.defer="{{ $fieldBase }}.columns_tablet" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Masaüstü kolonu</span>
                                    <input type="number" min="1" max="12" wire:model.defer="{{ $fieldBase }}.columns_desktop" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <input type="checkbox" wire:model.defer="{{ $fieldBase }}.show_on_mobile" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">Mobilde göster</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <input type="checkbox" wire:model.defer="{{ $fieldBase }}.show_on_tablet" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">Tablette göster</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <input type="checkbox" wire:model.defer="{{ $fieldBase }}.show_on_desktop" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">Masaüstünde göster</span>
                                </label>
                            </div>
                                </div>
                            </details>

                            <details class="studio-panel px-5 py-4">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">Metinler ve butonlar</summary>
                                <div class="mt-4 space-y-4">
                            <div class="grid gap-4 lg:grid-cols-3">
                                @foreach (['tr' => 'TR', 'en' => 'EN', 'ku' => 'KU'] as $locale => $label)
                                    <div class="studio-panel rounded-2xl p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">{{ $label }} metinleri</p>
                                        <div class="mt-4 space-y-3">
                                            <label class="space-y-2">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Başlık özelleştirmesi</span>
                                                <input type="text" wire:model.defer="{{ $fieldBase }}.title_override.{{ $locale }}" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </label>
                                            <label class="space-y-2">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Alt başlık</span>
                                                <textarea rows="3" wire:model.defer="{{ $fieldBase }}.subtitle_override.{{ $locale }}" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                                            </label>
                                            <label class="space-y-2">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Buton etiketi</span>
                                                <input type="text" wire:model.defer="{{ $fieldBase }}.cta_label.{{ $locale }}" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid gap-4 md:grid-cols-[auto_minmax(0,1fr)]">
                                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <input type="checkbox" wire:model.defer="{{ $fieldBase }}.cta_enabled" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">Butonu göster</span>
                                </label>
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Buton bağlantısı</span>
                                    <input type="text" wire:model.defer="{{ $fieldBase }}.cta_url" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </label>
                            </div>
                                </div>
                            </details>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Düzenlemek için soldan bir modül seçin.</p>
                    @endif
                    </div>
                </x-filament::section>

                <x-filament::section heading="Genel Görünüm" description="Marka belirteçleri tüm sayfa hissini etkiler; renk ve kenar yumuşaklığı kararlarını kontrollü ön ayarlarla yönetin.">
                    <div data-tour-anchor="layout.appearance">
                    <div class="studio-section-note mb-4 px-4 py-3 text-sm leading-6">
                        Serbest CSS yerine kontrollü belirteçler kullanıyoruz. Boyut, renk ve kenar yumuşaklığı kararlarını buradan değiştirirken sayfanın
                        editoryal hissi korunur.
                    </div>

                    <div class="studio-panel mb-4 space-y-4 px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Görünüm Ön Ayarları</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Haber sitesi hissini tek tıkla daha gazetemsi, temiz veya gece moduna yakın bir çizgiye çekin.</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-filament::button type="button" size="sm" color="warning" wire:click="applyAppearancePreset('gazete')">Gazete</x-filament::button>
                                <x-filament::button type="button" size="sm" color="gray" wire:click="applyAppearancePreset('temiz')">Temiz</x-filament::button>
                                <x-filament::button type="button" size="sm" color="gray" wire:click="applyAppearancePreset('gece')">Gece</x-filament::button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ana renk</span>
                            <input type="color" wire:model.defer="appearance.primary_color" class="h-11 w-full rounded-xl border border-gray-300 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Vurgu rengi</span>
                            <input type="color" wire:model.defer="appearance.accent_color" class="h-11 w-full rounded-xl border border-gray-300 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Arka plan rengi</span>
                            <input type="color" wire:model.defer="appearance.background_color" class="h-11 w-full rounded-xl border border-gray-300 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Yazı tipi ön ayarı</span>
                            <select wire:model.defer="appearance.font_family" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="inter">Inter</option>
                                <option value="lora">Lora</option>
                                <option value="poppins">Poppins</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Yan panel konumu</span>
                            <select wire:model.defer="appearance.sidebar_position" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="right">Sağ</option>
                                <option value="left">Sol</option>
                                <option value="none">Gizle</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Kenar yumuşaklığı</span>
                            <select wire:model.defer="appearance.radius_preset" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="sharp">Keskin</option>
                                <option value="soft">Yumuşak</option>
                                <option value="rounded">Yuvarlak</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Gölgelendirme</span>
                            <select wire:model.defer="appearance.shadow_preset" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="none">Yok</option>
                                <option value="subtle">Hafif</option>
                                <option value="elevated">Belirgin</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Kapsayıcı genişliği</span>
                            <input type="text" wire:model.defer="appearance.container_width" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tema modu</span>
                            <select wire:model.defer="appearance.default_theme_mode" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="system">Sistem</option>
                                <option value="light">Açık</option>
                                <option value="dark">Koyu</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Şerit davranışı</span>
                            <select wire:model.defer="appearance.rail_behavior" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="sticky">Sabitlenmiş</option>
                                <option value="static">Durağan</option>
                            </select>
                        </label>
                    </div>

                    <label class="mt-4 flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                        <input type="checkbox" wire:model.defer="appearance.dark_mode_default" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                        <span class="text-sm text-gray-700 dark:text-gray-200">Koyu modu varsayılan yap</span>
                    </label>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <x-filament::section heading="Yayınlama ve Geri Alma" description="Taslağı kaydedin, önizleme bağlantısından kontrol edin ve onaydan sonra canlıya alın. Gerektiğinde önceki sürümü taslağa geri yükleyebilirsiniz.">
            <div data-tour-anchor="layout.publish">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
                <div class="studio-panel space-y-4 px-5 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600 dark:text-amber-300">Yayın kontrolü</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Taslağı güvenle yayına alın</h3>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @foreach ($previewUrls as $locale => $url)
                                <a href="{{ $url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full border border-amber-200 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:border-amber-400 hover:bg-amber-50 dark:border-amber-400/20 dark:text-amber-300 dark:hover:bg-amber-400/10">
                                    {{ strtoupper($locale) }} önizleme
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-filament::button wire:click="saveDraft" color="gray" icon="heroicon-o-pencil-square">
                            Taslağı Kaydet
                        </x-filament::button>
                        <x-filament::button wire:click="publishDraft" color="primary" icon="heroicon-o-rocket-launch" :disabled="$this->isPublishRestricted() || $layoutReadiness['status'] === 'blocked'">
                            Canlıya Al
                        </x-filament::button>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if ($this->hasUnsavedChanges)
                            Kaydedilmemiş değişiklikler var. Önizleme bağlantısı en son kaydedilen taslağı gösterir.
                        @else
                            Taslak veritabanı ile senkron. Önizleme bağlantıları güncel durumla çalışır.
                        @endif
                    </p>

                    @if ($this->isPublishRestricted())
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            Bu hesap taslağı düzenleyebilir; ancak canlıya alma ve geri alma işlemleri için süper yönetici yetkisi gerekir.
                        </p>
                    @endif
                </div>

                <div class="studio-panel rounded-2xl p-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Geri Alma</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Yayındaki veya arşivdeki bir revizyonu taslağa geri yükleyin.</p>
                    <div class="mt-4 space-y-3">
                        <select wire:model.defer="restoreRevisionId" class="w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="">Revizyon seçin</option>
                            @foreach ($revisionOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-filament::button wire:click="restoreRevision" color="warning" icon="heroicon-o-arrow-uturn-left" class="w-full" :disabled="$this->isPublishRestricted()">
                            Seçili Revizyonu Taslağa Al
                        </x-filament::button>
                    </div>
                </div>
            </div>
            </div>
        </x-filament::section>

        <div class="sticky bottom-4 z-20">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white/95 px-4 py-4 shadow-[0_20px_45px_-30px_rgba(15,23,42,0.45)] backdrop-blur dark:border-slate-700 dark:bg-slate-950/90 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Yerleşim özeti</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $selectedModule['name'] ?? 'Bir blok seçin' }}
                        @if ($selectedModule)
                            • {{ $selectedModule['is_active'] ? 'aktif' : 'pasif' }}
                            • masaüstü {{ data_get($selectedSettings, 'columns_desktop', 1) }} kolon
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $this->hasUnsavedChanges ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' }}">
                        {{ $this->hasUnsavedChanges ? 'Kaydedilmemiş değişiklik var' : 'Taslak güncel' }}
                    </span>

                    <a href="{{ $previewUrls['tr'] ?? '#' }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-amber-400/30 dark:hover:bg-amber-400/10">
                        Önizleme
                    </a>

                    <x-filament::button wire:click="saveDraft" color="gray" size="sm" icon="heroicon-o-pencil-square">
                        Taslağı Kaydet
                    </x-filament::button>

                    <x-filament::button wire:click="publishDraft" color="primary" size="sm" icon="heroicon-o-rocket-launch" :disabled="$this->isPublishRestricted() || $layoutReadiness['status'] === 'blocked'">
                        Canlıya Al
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
