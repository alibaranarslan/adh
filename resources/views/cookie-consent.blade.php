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
    x-transition
    class="fixed inset-x-3 bottom-3 z-[100] max-h-[32vh] overflow-y-auto rounded-2xl border border-white/10 bg-adh-navy/96 p-2.5 text-white shadow-2xl backdrop-blur md:inset-x-auto md:right-4 md:bottom-4 md:max-h-[70vh] md:max-w-sm md:p-3"
    style="display: none;"
>
    <div class="space-y-1.5">
        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
            <button type="button" class="min-h-8 rounded bg-adh-red px-1.5 py-1 text-[11px] font-bold text-white transition-colors hover:bg-red-700 md:text-xs" @click="acceptAll">{{ __('Kabul Et') }}</button>
            <button type="button" class="min-h-8 rounded bg-white/10 px-1.5 py-1 text-[11px] font-semibold text-white transition-colors hover:bg-white/15 md:text-xs" @click="rejectOptional">{{ __('Reddet') }}</button>
            <button type="button" class="min-h-8 rounded bg-white/10 px-1.5 py-1 text-[11px] font-semibold text-white transition-colors hover:bg-white/15 md:text-xs" @click="openSettings = !openSettings">{{ __('Ayarlar') }}</button>
        </div>

        <p class="break-words text-[10px] font-semibold leading-snug text-white md:text-xs">
            {{ __('Adıyaman Dijital Haber, deneyimi geliştirmek ve site performansını ölçmek için çerezler kullanır.') }}
        </p>

        <p class="hidden text-[11px] leading-snug text-white/80 md:block">
            {{ __('İsteğe bağlı çerezleri reddedebilir, tercihlerinizi düzenleyebilir veya tümünü kabul edebilirsiniz.') }}
        </p>

        <div x-show="openSettings" class="grid gap-2 rounded-lg border border-white/15 p-2 text-[11px]" style="display: none;">
            <label class="flex items-center justify-between gap-3">
                <span>{{ __('Analitik çerezler') }}</span>
                <input type="checkbox" x-model="consent.analytics">
            </label>
            <label class="flex items-center justify-between gap-3">
                <span>{{ __('Fonksiyonel çerezler') }}</span>
                <input type="checkbox" x-model="consent.functional">
            </label>
            <label class="flex items-center justify-between gap-3">
                <span>{{ __('Reklam çerezleri') }}</span>
                <input type="checkbox" x-model="consent.marketing">
            </label>
            <button type="button" class="rounded bg-adh-red px-3 py-2 text-xs font-semibold text-white" @click="saveCustom">
                {{ __('Tercihleri Kaydet') }}
            </button>
        </div>

        <a href="{{ \App\Support\LocalizedUrl::route('page.cookies') }}" class="text-[11px] font-semibold text-white underline hover:text-adh-red-light">{{ __('Detaylı Çerez Politikası') }}</a>
    </div>
</div>
@endif
