@php
    $user = auth()->user();
@endphp

@if ($user && \App\Support\AdminPrivileges::canAccessAdminPanel($user))
    <button
        type="button"
        x-data="{}"
        x-on:click="$dispatch('adh-admin-guide:open')"
        class="hidden md:inline-flex items-center gap-2 rounded-xl border border-amber-300/40 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 shadow-sm transition hover:border-amber-400 hover:bg-amber-100 dark:border-amber-400/25 dark:bg-amber-400/10 dark:text-amber-200"
        aria-label="Öğretici modu aç"
    >
        <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-5 w-5" />
        <span class="hidden sm:inline">Yardım</span>
    </button>
@endif
