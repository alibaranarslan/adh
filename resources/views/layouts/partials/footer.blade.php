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

<footer class="mt-12 border-t-4 border-adh-red bg-adh-blue text-white dark:bg-adh-navy">
    <div class="max-w-7xl mx-auto px-4 pt-10 pb-6">
        <div class="grid grid-cols-1 gap-8 border-b border-white/10 pb-8 md:grid-cols-2 lg:grid-cols-[1.2fr_0.9fr_0.9fr_1.1fr]">
            <div>
                <a href="{{ route('home') }}" class="mb-4 block">
                    <img
                        src="{{ $branding['logo_dark_url'] ?? asset('images/branding/adh-logo-dark.svg') }}"
                        alt="{{ $siteName }}"
                        class="h-12 w-auto"
                    >
                </a>
                @if ($siteTagline)
                    <p class="mb-2 text-xs uppercase tracking-[0.22em] text-gray-400">{{ $siteTagline }}</p>
                @endif
                <p class="mb-4 max-w-sm text-sm leading-7 text-gray-300">
                    {{ $footerDescription }}
                </p>
                <div class="flex gap-3">
                    <a href="{{ data_get($socialLinks, 'twitter', 'https://twitter.com/adiyamanhaber') }}" target="_blank" rel="noopener"
                       aria-label="X (Twitter)"
                       class="flex h-8 w-8 items-center justify-center rounded bg-white/10 transition-colors hover:bg-adh-red">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.736-8.843L2.139 2.25H8.48l4.265 5.634 5.499-5.634zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="{{ data_get($socialLinks, 'facebook', 'https://facebook.com/adiyamanhaber') }}" target="_blank" rel="noopener"
                       aria-label="Facebook"
                       class="flex h-8 w-8 items-center justify-center rounded bg-white/10 transition-colors hover:bg-adh-red">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="{{ data_get($socialLinks, 'instagram', 'https://instagram.com/adiyamanhaber') }}" target="_blank" rel="noopener"
                       aria-label="Instagram"
                       class="flex h-8 w-8 items-center justify-center rounded bg-white/10 transition-colors hover:bg-adh-red">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="{{ data_get($socialLinks, 'youtube', 'https://youtube.com/@adiyamanhaber') }}" target="_blank" rel="noopener"
                       aria-label="YouTube"
                       class="flex h-8 w-8 items-center justify-center rounded bg-white/10 transition-colors hover:bg-adh-red">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Kurumsal Sayfalar') }}</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-adh-red-light">{{ __('Anasayfa') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'hakkimizda']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Hakkımızda') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-adh-red-light">{{ __('İletişim') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'gizlilik-politikasi']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Gizlilik Politikası') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'cerez-politikasi']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Çerez Politikası') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'kvkk-aydinlatma']) }}" class="transition-colors hover:text-adh-red-light">{{ __('KVKK Aydınlatma Metni') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Editoryal Akış') }}</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('news.category', ['slug' => 'gundem']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Gündem') }}</a></li>
                    <li><a href="{{ route('city.show', ['slug' => 'adiyaman']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Adıyaman') }}</a></li>
                    <li><a href="{{ route('news.category', ['slug' => 'asayis']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Asayiş') }}</a></li>
                    <li><a href="{{ route('news.category', ['slug' => 'yasam']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Yaşam') }}</a></li>
                    <li><a href="{{ route('news.category', ['slug' => 'ekonomi']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Ekonomi') }}</a></li>
                    <li><a href="{{ route('news.category', ['slug' => 'spor']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Spor') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Okur Hattı') }}</h5>
                <ul class="mb-5 space-y-2 text-sm text-gray-300">
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $resolvedAddress }}</span>
                    </li>
                    @if(data_get($siteSettings, 'contact_phone'))
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ data_get($siteSettings, 'contact_phone') }}" class="transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_phone') }}</a>
                        </li>
                    @endif
                    @if(data_get($siteSettings, 'contact_email'))
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:{{ data_get($siteSettings, 'contact_email') }}" class="break-all transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_email') }}</a>
                        </li>
                    @endif
                </ul>

                <div class="mb-4 rounded border border-white/10 bg-white/5 p-3 text-xs leading-6 text-gray-300">
                    {{ __('Haber ihbarı, düzeltme talepleri ve kurumsal iletişim başlıkları için redaksiyon ekibine doğrudan ulaşabilirsiniz.') }}
                </div>

                <h5 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('E-Bülten') }}</h5>
                <form
                    class="space-y-2"
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
                        <div class="space-y-2">
                            <input type="email" x-model="email"
                                   placeholder="{{ __('E-posta adresinizi girin') }}"
                                   class="w-full rounded border border-gray-600 bg-white/10 px-3 py-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:border-adh-red"
                                   aria-label="{{ __('E-posta adresinizi girin') }}" required>
                            <button type="submit" :disabled="loading"
                                    class="w-full rounded bg-adh-red px-4 py-2 text-sm text-white transition-colors hover:bg-red-700 disabled:opacity-60">
                                <span x-text="loading ? '{{ __('Kaydediliyor...') }}' : '{{ __('Abone Ol') }}'"></span>
                            </button>
                            <p x-show="message" x-text="message" class="mt-1 text-xs text-red-300"></p>
                        </div>
                    </template>
                    <template x-if="success">
                        <p class="text-sm text-green-400">{{ __('Teşekkürler! E-bülten aboneliğiniz kaydedildi.') }}</p>
                    </template>
                </form>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-5 text-xs text-gray-400 md:flex-row">
            <p>© {{ date('Y') }} {{ $siteName }} — {{ __('Tüm hakları saklıdır.') }}</p>
            <p class="flex items-center gap-1">
                <span>{{ __('İHA İş Birliği') }}</span>
                <span class="mx-1 text-gray-600">|</span>
                <span>{{ __('Haber kaynağı: İHA') }}</span>
            </p>
            <p>{{ __('Yerel yayın odağı') }}: {{ __('Adıyaman ve çevresi') }}</p>
        </div>
    </div>
</footer>
