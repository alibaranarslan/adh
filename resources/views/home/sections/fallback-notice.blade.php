<section class="rounded-[var(--adh-radius)] border border-amber-300 bg-amber-50 px-5 py-5 text-amber-950 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100" role="status" aria-live="polite">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-700 dark:text-amber-200">
                {{ __('Public Durum Uyarisi') }}
            </p>
            <h2 class="mt-1 font-serif text-2xl font-bold">
                {{ __('Haber akisi gecici olarak eksik.') }}
            </h2>
            <p class="mt-3 text-sm leading-6 text-amber-900/90 dark:text-amber-100/90">
                {{ __('Guncel haber modulleri su anda yuklenemiyor. Sayfa sinirli modda gosteriliyor; Bilgi Panosu ve yardimci servisler calismaya devam ediyor.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-amber-300/80 bg-white/80 px-4 py-3 text-sm text-amber-900 shadow-sm dark:border-amber-400/30 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="font-semibold">{{ __('Beklenen durum') }}</p>
            <p class="mt-1">{{ __('Hero, yerel gundem, bolge haberleri ve son haberler geri dondugunde bu uyari otomatik olarak kaybolur.') }}</p>
        </div>
    </div>
</section>
