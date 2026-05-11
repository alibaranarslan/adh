@assets
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #e0f2fe !important;
    }
    .sortable-chosen {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .sortable-drag-handle {
        cursor: grab;
    }
    .sortable-drag-handle:active {
        cursor: grabbing;
    }
</style>
@endassets

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Mimari Not">
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                Bu projede anasayfa düzeni özel <strong>Layout Manager</strong> ile yönetilir. Filament Fabricator paketi kurulu olsa da
                public routing bilinçli olarak kapalıdır; bu ekran modül sırası, görünürlük ve genel görünüm ayarları için kullanılır.
            </p>
        </x-filament::section>

        {{-- Modules List --}}
        <x-filament::section heading="Anasayfa Modülleri">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Sürükle-bırak ile sırayı değiştirebilirsiniz. Değişiklikler otomatik kaydedilir.
            </p>

            <div
                id="module-sortable-list"
                class="space-y-3"
                x-data="{
                    sortable: null,
                    init() {
                        this.sortable = new Sortable(this.$el, {
                            animation: 150,
                            handle: '.sortable-drag-handle',
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            onEnd: (evt) => {
                                const items = [...this.$el.querySelectorAll('[data-module-id]')]
                                    .map(el => el.dataset.moduleId);
                                $wire.call('updateModuleOrder', items);
                            }
                        });
                    }
                }"
            >
                @foreach($modules as $module)
                    <div
                        data-module-id="{{ $module['id'] }}"
                        class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 select-none"
                    >
                        <div class="flex items-center gap-3">
                            {{-- Drag handle --}}
                            <span class="sortable-drag-handle text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <x-heroicon-o-bars-3 class="w-5 h-5" />
                            </span>

                            {{-- Module info --}}
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $module['name'] }}</p>
                                <p class="text-xs text-gray-500">
                                    <span class="font-mono">{{ $module['key'] }}</span>
                                    · Sıra: {{ $module['sort_order'] }}
                                </p>
                            </div>
                        </div>

                        {{-- Active toggle --}}
                        <div class="flex items-center gap-2">
                            <x-filament::button
                                size="sm"
                                color="{{ $module['is_active'] ? 'success' : 'gray' }}"
                                wire:click="toggleModule({{ $module['id'] }})"
                            >
                                {{ $module['is_active'] ? 'Aktif' : 'Pasif' }}
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Appearance Settings --}}
        <x-filament::section heading="Görünüm Ayarları">
            <form wire:submit="saveSettings">
                {{ $this->form }}
                <div class="mt-4">
                    <x-filament::button type="submit" color="primary">
                        Kaydet
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
