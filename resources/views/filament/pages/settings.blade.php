@php
    $settingsSummary = method_exists($this, 'settingsSummary')
        ? $this->settingsSummary()
        : 'Bu ekran ayar odaklıdır. Public etki taşıyan alanlar, operasyonel alanlar ve gizli bilgiler ayrı açıklamalarla gösterilir.';
    $settingsImpactBadges = method_exists($this, 'settingsImpactBadges')
        ? $this->settingsImpactBadges()
        : ['Public etki', 'Operasyonel etki', 'Gizli bilgi yok'];
@endphp

<x-filament-panels::page class="admin-page-frame">
    <div class="admin-note space-y-3">
        <p>{{ $settingsSummary }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($settingsImpactBadges as $badge)
                <span class="rounded-full border border-amber-300/30 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-slate-950/30 dark:text-amber-200">
                    {{ $badge }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="admin-section-panel">
        <form wire:submit="save" class="admin-page-frame" data-tour-anchor="settings.form">
            {{ $this->form }}

            <div class="admin-action-bar" data-tour-anchor="settings.save">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    Kaydetmeden önce public etki taşıyan alanları ve gizli bilgi notlarını kontrol edin.
                </span>
                <x-filament::button type="submit" color="primary">
                    Kaydet
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
