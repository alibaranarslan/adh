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
                <a href="{{ \App\Support\LocalizedUrl::route('home') }}" class="mb-4 block">
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
                <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.14em]">
                    @foreach ($socialLinks as $platform => $url)
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded bg-white/10 px-3 py-2 transition-colors hover:bg-adh-red">
                                {{ $platform }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Kurumsal Sayfalar') }}</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ \App\Support\LocalizedUrl::route('home') }}" class="transition-colors hover:text-adh-red-light">{{ __('Anasayfa') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.about') }}" class="transition-colors hover:text-adh-red-light">{{ __('Hakkımızda') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('contact') }}" class="transition-colors hover:text-adh-red-light">{{ __('İletişim') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.privacy') }}" class="transition-colors hover:text-adh-red-light">{{ __('Gizlilik Politikası') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.cookies') }}" class="transition-colors hover:text-adh-red-light">{{ __('Çerez Politikası') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('page.kvkk') }}" class="transition-colors hover:text-adh-red-light">{{ __('KVKK Aydınlatma Metni') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Editoryal Akış') }}</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'gundem']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Gündem') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('city.show', ['slug' => 'adiyaman']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Adıyaman') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'asayis']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Asayiş') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'yasam']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Yaşam') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'ekonomi']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Ekonomi') }}</a></li>
                    <li><a href="{{ \App\Support\LocalizedUrl::route('news.category', ['slug' => 'spor']) }}" class="transition-colors hover:text-adh-red-light">{{ __('Spor') }}</a></li>
                </ul>
            </div>

            <div>
                <h5 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-300">{{ __('Okur Hattı') }}</h5>
                <ul class="mb-5 space-y-2 text-sm text-gray-300">
                    <li>{{ $resolvedAddress }}</li>
                    @if(data_get($siteSettings, 'contact_phone'))
                        <li><a href="tel:{{ data_get($siteSettings, 'contact_phone') }}" class="transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_phone') }}</a></li>
                    @endif
                    @if(data_get($siteSettings, 'contact_email'))
                        <li><a href="mailto:{{ data_get($siteSettings, 'contact_email') }}" class="break-all transition-colors hover:text-adh-red-light">{{ data_get($siteSettings, 'contact_email') }}</a></li>
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
