@php
    $adminThemeCssPath = resource_path('css/admin-theme.css');
    $adminGuideCssPath = resource_path('css/admin-guide.css');
    $adminGuideJsPath = resource_path('js/admin-guide.js');
@endphp

@if (is_file($adminThemeCssPath))
    <style>
        {!! file_get_contents($adminThemeCssPath) !!}
    </style>
@endif

@if (is_file($adminGuideCssPath))
    <style>
        {!! file_get_contents($adminGuideCssPath) !!}
    </style>
@endif

<script>
    (() => {
        const isPhoneWidth = () => window.matchMedia('(max-width: 639.98px)').matches;

        const closePersistedMobileSidebar = () => {
            if (!isPhoneWidth()) {
                return;
            }

            try {
                window.localStorage.setItem('isOpen', 'false');
            } catch (error) {
                return;
            }

            try {
                const sidebarStore = window.Alpine?.store?.('sidebar');

                if (sidebarStore && typeof sidebarStore.isOpen !== 'undefined') {
                    sidebarStore.isOpen = false;
                }
            } catch (error) {
                // Sidebar store is not ready yet; persisted state is already normalized.
            }
        };

        closePersistedMobileSidebar();
        document.addEventListener('alpine:init', closePersistedMobileSidebar, { once: true });
    })();
</script>

@if (is_file($adminGuideJsPath))
    <script>
        {!! file_get_contents($adminGuideJsPath) !!}
    </script>
@endif
