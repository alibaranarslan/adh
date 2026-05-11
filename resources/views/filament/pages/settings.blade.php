<x-filament-panels::page class="admin-page-frame">
    <div class="admin-note">
        Bu ekran ayar odaklıdır. Public etki taşıyan alanları helper text ile, yalnız operasyonel alanları ise açıklama notlarıyla ayırın.
    </div>

    <div class="admin-section-panel">
        <form wire:submit="save" class="admin-page-frame" data-tour-anchor="settings.form">
            {{ $this->form }}

            <div class="admin-action-bar" data-tour-anchor="settings.save">
                <x-filament::button type="submit" color="primary">
                    Kaydet
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
