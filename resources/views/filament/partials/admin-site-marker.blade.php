@php
    $context = $context ?? 'topbar';
    $loginExtra = $context === 'login' ? 'justify-center' : '';
@endphp

<span
    class="inline-flex max-w-full items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold leading-tight ring-1 ring-inset bg-red-950/90 text-red-50 ring-red-400/30 dark:bg-red-950/50 dark:text-red-100 dark:ring-red-500/25 {{ $loginExtra }}"
    title="Adıyaman Dijital Haber yönetim paneli"
>
    <span class="opacity-80" aria-hidden="true">ADH</span>
    <span class="hidden md:inline">·</span>
    <span class="sm:hidden">Yönetim</span>
    <span class="hidden sm:inline truncate">Adıyaman Dijital Haber — Haber yönetimi</span>
</span>
