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
    class="fixed bottom-0 left-0 right-0 z-[100] max-h-[46dvh] overflow-y-auto bg-adh-navy p-3 text-white shadow-2xl md:bottom-4 md:left-4 md:right-auto md:max-h-[min(70dvh,calc(100dvh-2rem))] md:max-w-md md:rounded-xl md:p-4"
    style="display: none;"
>
    <div class="mx-auto max-w-7xl space-y-2 md:space-y-3">
        <div class="sticky -top-3 z-10 flex flex-wrap items-center gap-2 bg-adh-navy py-1 md:static md:bg-transparent md:pt-1">
            <button type="button" class="rounded bg-adh-red px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700" @click="acceptAll">{{ __('Kabul Et') }}</button>
            <button type="button" class="rounded bg-white/10 px-3 py-2 text-sm text-white transition-colors hover:bg-white/15" @click="rejectOptional">{{ __('Reddet') }}</button>
            <button type="button" class="rounded bg-white/10 px-3 py-2 text-sm text-white transition-colors hover:bg-white/15" @click="openSettings = !openSettings">{{ __('Ayarlar') }}</button>
        </div>

        <div x-show="openSettings" class="space-y-2 rounded-lg border border-white/20 p-3 text-sm" style="display: none;">
            <label class="flex items-center justify-between gap-3">
                <div>
                    <span class="font-medium">{{ __('Zorunlu Çerezler') }}</span>
                    <span class="mt-0.5 block text-xs text-gray-400">{{ __('Web sitesinin temel işlevleri için gereklidir.') }}</span>
                </div>
                <input type="checkbox" checked disabled>
            </label>

            <label class="flex items-center justify-between gap-3">
                <div>
                    <span class="font-medium">{{ __('Analitik Çerezler') }}</span>
                    <span class="mt-0.5 block text-xs text-gray-400">{{ __('Site performansını ölçmek ve geliştirmek için kullanılır.') }}</span>
                </div>
                <input type="checkbox" x-model="consent.analytics">
            </label>

            <label class="flex items-center justify-between gap-3">
                <div>
                    <span class="font-medium">{{ __('Fonksiyonel Çerezler') }}</span>
                    <span class="mt-0.5 block text-xs text-gray-400">{{ __('Kullanıcı tercihlerinizi hatırlamak için kullanılır.') }}</span>
                </div>
                <input type="checkbox" x-model="consent.functional">
            </label>

            <label class="flex items-center justify-between gap-3">
                <div>
                    <span class="font-medium">{{ __('Reklam Çerezleri') }}</span>
                    <span class="mt-0.5 block text-xs text-gray-400">{{ __('İlgi alanınıza uygun içerik ve reklam sunmak için kullanılır.') }}</span>
                </div>
                <input type="checkbox" x-model="consent.marketing">
            </label>

            <button type="button" class="w-full rounded bg-adh-red px-3 py-2 text-sm font-medium text-white" @click="saveCustom">
                {{ __('Tercihleri Kaydet') }}
            </button>
        </div>

        <p class="text-xs font-medium leading-relaxed text-white md:text-sm">
            {{ __('“Adıyaman Dijital Haber” web sitesi, kullanıcı deneyimini geliştirmek ve site performansını artırmak amacıyla çerezler (cookie) kullanmaktadır.') }}
        </p>

        <p class="text-xs leading-relaxed text-white/90 md:text-sm">
            {{ __('Web sitemizi kullanmaya devam ederek, çerez kullanımını kabul etmiş olursunuz.') }}
        </p>

        <ul class="list-inside list-disc space-y-1 pl-0.5 text-[11px] leading-relaxed text-white/85 md:text-xs">
            <li><span class="font-semibold text-white">{{ __('Zorunlu çerezler:') }}</span> {{ __('Web sitesinin temel işlevleri için gereklidir.') }}</li>
            <li><span class="font-semibold text-white">{{ __('Analitik çerezler:') }}</span> {{ __('Site performansını ölçmek ve geliştirmek için kullanılır.') }}</li>
            <li><span class="font-semibold text-white">{{ __('Fonksiyonel çerezler:') }}</span> {{ __('Kullanıcı tercihlerinizi hatırlamak için kullanılır.') }}</li>
            <li><span class="font-semibold text-white">{{ __('Reklam çerezleri:') }}</span> {{ __('İlgi alanınıza uygun içerik ve reklam sunmak için kullanılır.') }}</li>
        </ul>

        <p class="text-xs text-white/75">
            {{ __('Çerez ayarlarınızı değiştirmek veya çerezleri reddetmek için tarayıcı ayarlarınızı kullanabilirsiniz.') }}
            <a href="{{ route('page.show', ['slug' => 'cerez-politikasi']) }}" class="font-medium text-white underline hover:text-adh-red-light">{{ __('Detaylı Çerez Politikası') }}</a>
        </p>
    </div>
</div>
