@if (! isset($layoutPreviewRevision) || ! $layoutPreviewRevision)
<div
    x-data="{
        storageKey: 'adh_site_cookie_consent',
        openSettings: false,
        consent: { required: true, analytics: false, marketing: false, functional: false },
        visible: false,
        init() {
            const raw = localStorage.getItem(this.storageKey);
            if (!raw) {
                this.visible = true;
                return;
            }

            try {
                const parsed = JSON.parse(raw);
                const isExpired = !parsed.expires_at || Date.now() > parsed.expires_at;

                if (isExpired) {
                    localStorage.removeItem(this.storageKey);
                    this.visible = true;
                    return;
                }

                this.consent.analytics = !!parsed.analytics;
                this.consent.marketing = !!parsed.marketing;
                this.consent.functional = !!parsed.functional;
                window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: parsed }));
            } catch (_) {
                this.visible = true;
            }
        },
        persist(values) {
            const payload = {
                required: true,
                analytics: !!values.analytics,
                marketing: !!values.marketing,
                functional: !!values.functional,
                updated_at: Date.now(),
                expires_at: Date.now() + 365 * 24 * 60 * 60 * 1000,
            };

            localStorage.setItem(this.storageKey, JSON.stringify(payload));
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: payload }));
            this.visible = false;
            this.openSettings = false;
        },
        acceptAll() {
            this.persist({ analytics: true, marketing: true, functional: true });
        },
        rejectOptional() {
            this.persist({ analytics: false, marketing: false, functional: false });
        },
        saveCustom() {
            this.persist(this.consent);
        }
    }"
    x-show="visible"
    role="dialog"
    aria-live="polite"
    aria-label="{{ __('Çerez tercihleri') }}"
    data-cookie-consent
    class="fixed inset-x-3 bottom-3 z-[100] max-h-[38dvh] overflow-y-auto rounded-2xl border border-slate-200/90 bg-white/95 p-3 text-slate-900 shadow-2xl shadow-slate-950/20 backdrop-blur md:inset-x-auto md:right-4 md:bottom-4 md:max-h-[70vh] md:max-w-sm md:p-4 dark:border-white/10 dark:bg-slate-950/95 dark:text-white"
    style="display: none;"
>
    <div class="space-y-2.5">
        <div class="flex items-start gap-2.5">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-adh-red/10 text-sm font-black text-adh-red dark:bg-adh-red/20 dark:text-red-200">
                i
            </div>

            <div class="min-w-0 space-y-1">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-adh-red dark:text-red-200">
                    {{ __('Çerez tercihleri') }}
                </p>
                <p class="break-words text-[12px] font-semibold leading-relaxed text-slate-800 md:text-sm dark:text-slate-100">
                    {{ __('Adıyaman Dijital Haber, siteyi güvenli çalıştırmak ve okuma deneyimini iyileştirmek için çerezler kullanır.') }}
                </p>
            </div>
        </div>

        <p class="text-[11px] leading-relaxed text-slate-600 md:text-xs dark:text-slate-300">
            {{ __('Zorunlu çerezler her zaman aktiftir. Analitik, fonksiyonel ve reklam çerezlerini reddedebilir veya tercihlerinizi düzenleyebilirsiniz.') }}
        </p>

        <div x-ref="settingsPanel" hidden class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50/80 p-2.5 text-[12px] text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
            <label class="flex items-center justify-between gap-3 rounded-lg bg-white px-2.5 py-2 shadow-sm dark:bg-white/10">
                <span class="font-semibold">{{ __('Analitik çerezler') }}</span>
                <input type="checkbox" x-model="consent.analytics">
            </label>
            <label class="flex items-center justify-between gap-3 rounded-lg bg-white px-2.5 py-2 shadow-sm dark:bg-white/10">
                <span class="font-semibold">{{ __('Fonksiyonel çerezler') }}</span>
                <input type="checkbox" x-model="consent.functional">
            </label>
            <label class="flex items-center justify-between gap-3 rounded-lg bg-white px-2.5 py-2 shadow-sm dark:bg-white/10">
                <span class="font-semibold">{{ __('Reklam çerezleri') }}</span>
                <input type="checkbox" x-model="consent.marketing">
            </label>
            <button type="button" class="rounded-lg bg-adh-red px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-red-700" @click="saveCustom(); $el.closest('[data-cookie-consent]')?.remove()">
                {{ __('Tercihleri Kaydet') }}
            </button>
        </div>

        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
            <button type="button" class="min-h-9 rounded-xl bg-adh-red px-2 py-1.5 text-[11px] font-black text-white shadow-sm transition-colors hover:bg-red-700 md:text-xs" @click="acceptAll(); $el.closest('[data-cookie-consent]')?.remove()">{{ __('Kabul Et') }}</button>
            <button type="button" class="min-h-9 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-bold text-slate-700 transition-colors hover:bg-slate-50 md:text-xs dark:border-white/10 dark:bg-white/10 dark:text-slate-100 dark:hover:bg-white/15" @click="rejectOptional(); $el.closest('[data-cookie-consent]')?.remove()">{{ __('Reddet') }}</button>
            <button type="button" class="min-h-9 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-bold text-slate-700 transition-colors hover:bg-slate-50 md:text-xs dark:border-white/10 dark:bg-white/10 dark:text-slate-100 dark:hover:bg-white/15" @click="$refs.settingsPanel.hidden = ! $refs.settingsPanel.hidden">{{ __('Ayarlar') }}</button>
        </div>

        <a href="{{ \App\Support\LocalizedUrl::route('page.cookies') }}" class="inline-flex text-[11px] font-bold text-adh-red underline decoration-adh-red/40 underline-offset-2 hover:text-red-700 dark:text-red-200 dark:hover:text-white">{{ __('Detaylı Çerez Politikası') }}</a>
    </div>
</div>
@endif
