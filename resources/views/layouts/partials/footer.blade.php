@php
    $branding = \App\Support\SiteBranding::current();
    $siteName = $branding['site_name'] ?? 'Adıyaman Dijital Haber';
    $siteTagline = $branding['site_tagline'] ?? null;
    $footerDescription = $branding['footer_description'] ?? $siteTagline;
    $socialLinks = $branding['social_links'] ?? [];
    $resolvedAddress = \App\Support\LocalizedSettings::resolveText(
        data_get($siteSettings, 'address', ''),
        'Adıyaman Merkez / Türkiye',
    );
@endphp

<footer class="mt-4 border-t-2 border-adh-red bg-adh-blue text-white dark:bg-adh-navy md:mt-6" data-testid="site-footer">
    <div class="mx-auto max-w-7xl px-4 py-3 md:py-4">
        <div class="grid grid-cols-1 gap-3 border-b border-white/10 pb-3 md:grid-cols-2 md:gap-4 md:pb-4 lg:grid-cols-[1.15fr_0.75fr_0.75fr_1fr]">
            <div>
                <a href="{{ \App\Support\LocalizedUrl::route('home') }}" class="mb-2 block">
                    <img
                        src="{{ $branding['logo_dark_url'] ?? asset('images/branding/adh-logo-dark.svg') }}"
                        alt="{{ $siteName }}"
                        class="h-7 w-auto md:h-8"
                    >
                </a>

                @if ($siteTagline)
                    <p class="mb-1 text-[10px] uppercase tracking-[0.18em] text-gray-400 md:text-[11px]">{{ $siteTagline }}</p>
                @endif

                @if ($footerDescription)
                    <p class="line-clamp-2 max-w-sm text-xs leading-5 text-gray-300">
                        {{ $footerDescription }}
                    </p>
                @endif

                @if (! empty($socialLinks))
                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] md:text-[11px]">
                        @foreach ($socialLinks as $platform => $url)
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded bg-white/10 px-2 py-0.5 transition-colors hover:bg-adh-red">
                                    {{ $platform }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h5 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-300">{{ __('Kurumsal') }}</h5>
                <ul class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs md:block md:space-y-1">
                    <li><a href="{{ \App\Support\LocalizedUrl::route('home') }}" class="transition-colors hover:text-adh-red-light">{{ __('Anasayfa') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.editorial_policy') }}" class="transition-colors hover:text-adh-red-light">{{ __('Yayın İlkeleri') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.about') }}" class="transition-colors hover:text-adh-red-light">{{ __('Hakkımızda') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('contact') }}" class="transition-colors hover:text-adh-red-light">{{ __('İletişim') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.privacy') }}" class="transition-colors hover:text-adh-red-light">{{ __('Gizlilik') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.cookies') }}" class="transition-colors hover:text-adh-red-light">{{ __('Çerez Politikası') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.kvkk') }}" class="transition-colors hover:text-adh-red-light">{{ __('KVKK') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-300">{{ __('Editoryal') }}</h5>
                <ul class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs md:block md:space-y-1">
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'gundem']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Gündem') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('city.show', ['slug' => 'adiyaman']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Adıyaman') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'asayis']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Asayiş') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'yasam']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Yaşam') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'ekonomi']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Ekonomi') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'spor']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Spor') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-300">{{ __('Okur Hattı') }}</h5>
                <ul class="mb-2 space-y-1 text-xs leading-5 text-gray-300">
                    <li>{{ $resolvedAddress }}</li>
                    @if(data_get($siteSettings, 'contact_phone'))
                        <li><a href="tel:{{ data_get($siteSettings, 'contact_phone') }}" class="transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_phone') }}</a></li>
                    @endif
                    @if(data_get($siteSettings, 'contact_email'))
                        <li><a href="mailto:{{ data_get($siteSettings, 'contact_email') }}" class="break-all transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_email') }}</a></li>
                    @endif
                </ul>

                <p class="mb-2 hidden rounded border border-white/10 bg-white/5 p-2 text-xs leading-5 text-gray-300 lg:block">
                    {{ __('Haber ihbarı, düzeltme talepleri ve kurumsal iletişim için redaksiyon ekibine ulaşabilirsiniz.') }}
                </p>

                <h5 class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-300">{{ __('E-Bülten') }}</h5>
                <form
                    class="space-y-1.5"
                    x-data="{
                        email: '',
                        loading: false,
                        message: null,
                        success: false,
                        async submit() {
                            this.loading = true;
                            this.message = null;
                            try {
                                const res = await fetch('{{ route('newsletter.subscribe') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ email: this.email })
                                });
                                const data = await res.json();
                                this.success = data.success;
                                this.message = data.message;
                                if (data.success) this.email = '';
                            } catch {
                                this.message = '{{ __('Bir hata oluştu, lütfen tekrar deneyin.') }}';
                            } finally {
                                this.loading = false;
                            }
                        }
                    }"
                    @submit.prevent="submit"
                >
                    <template x-if="!success">
                        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input type="email" x-model="email"
                                   placeholder="{{ __('E-posta adresiniz') }}"
                                   class="min-h-9 w-full rounded border border-gray-600 bg-white/10 px-3 py-1.5 text-sm text-white placeholder-gray-400 focus:border-adh-red focus:outline-none"
                                   aria-label="{{ __('E-posta adresinizi girin') }}" required>
                            <button type="submit" :disabled="loading"
                                    class="min-h-9 rounded bg-adh-red px-3 py-1.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60">
                                <span x-text="loading ? '{{ __('Kaydediliyor...') }}' : '{{ __('Abone Ol') }}'"></span>
                            </button>
                            <p x-show="message" x-text="message" class="text-xs text-red-300 sm:col-span-2"></p>
                        </div>
                    </template>
                    <template x-if="success">
                        <p class="text-sm text-green-400">{{ __('Teşekkürler! E-bülten aboneliğiniz kaydedildi.') }}</p>
                    </template>
                </form>
            </div>
        </div>

        <div class="flex flex-col items-start justify-between gap-1.5 pt-2 text-[11px] leading-5 text-gray-400 md:flex-row md:items-center md:text-xs">
            <p>© {{ date('Y') }} {{ $siteName }} — {{ __('Tüm hakları saklıdır.') }}</p>
            <p class="flex flex-wrap items-center gap-1">
                <span>{{ __('İHA İş Birliği') }}</span>
                <span class="text-gray-600">|</span>
                <span>{{ __('Kaynak: İHA') }}</span>
            </p>
            <p class="hidden md:block">{{ __('Yerel yayın odağı') }}: {{ __('Adıyaman ve çevresi') }}</p>
        </div>
    </div>
    <div style="border-top: 1px solid rgba(255, 255, 255, 0.10); background: rgba(0, 0, 0, 0.10);">
        <div style="max-width: 80rem; margin: 0 auto; padding: 1rem; text-align: center;">
            <p style="margin: 0; line-height: 1;">
                <span style="font-family: Georgia, 'Times New Roman', serif; font-size: 11px; letter-spacing: 0.18em; color: rgba(209, 213, 219, 0.78);">made by</span>
                <span style="margin-left: 0.5rem; font-family: 'Segoe Script', 'Lucida Handwriting', 'Brush Script MT', cursive; font-size: 1.35rem; font-style: italic; line-height: 1; color: #ffffff;">Ali Baran Arslan</span>
            </p>
            <a href="mailto:alibaranarslann@outlook.com" style="display: inline-block; margin-top: 0.35rem; font-size: 10px; font-weight: 500; letter-spacing: 0.08em; color: rgba(209, 213, 219, 0.72); text-decoration: none;">
                alibaranarslann@outlook.com
            </a>
        </div>
    </div>
</footer>
