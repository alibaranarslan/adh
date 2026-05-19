@extends('layouts.app')

@push('head')
    <x-schema-page
        type="ContactPage"
        :name="$contactTitle ?? __('İletişim')"
        :description="$contactDescription ?? __('Adıyaman Dijital Haber iletişim, haber ihbarı ve reklam iş birliği kanalları.')"
        :url="\App\Support\SeoUrls::absolute(\App\Support\LocalizedUrl::route('contact'))"
    />
@endpush

@section('content')
    @php
        $resolvedAddress = \App\Support\LocalizedSettings::resolveText(
            data_get($siteSettings, 'address', ''),
            'Adıyaman Merkez / Türkiye',
        );
        $resolvedPhone = data_get($siteSettings, 'contact_phone', '+90 (416) 000 00 00');
        $resolvedEmail = data_get($siteSettings, 'contact_email', 'iletisim@adiyamandijitalhaber.com.tr');
        $whatsAppPhone = preg_replace('/[^0-9]/', '', $resolvedPhone);
        $contactPageBody = isset($page)
            ? trim((string) $page->getTranslation('content', app()->getLocale(), false))
            : '';
    @endphp

    <div class="space-y-6 overflow-hidden">
        <div class="rounded-[var(--adh-radius)] border border-adh-border bg-white px-4 py-6 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:px-8 md:py-9">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ __('İletişim') }}</p>
            <h1 class="mt-3 break-words font-serif text-2xl font-bold leading-tight text-adh-text dark:text-gray-100 sm:text-3xl md:text-4xl">{{ $contactTitle ?? __('Redaksiyon ve Okur Hattı') }}</h1>
            @if ($contactPageBody !== '')
                <div class="prose prose-sm mt-4 max-w-3xl break-words text-adh-gray dark:prose-invert dark:text-gray-300 md:prose-base">
                    {!! $contactPageBody !!}
                </div>
            @else
                <p class="mt-4 max-w-3xl break-words text-sm leading-6 text-adh-gray dark:text-gray-300 md:text-base md:leading-7">
                    {{ __('Haber ihbarı, düzeltme talepleri, reklam iş birlikleri ve genel sorular için ADH ekibine bu sayfa üzerinden ulaşabilirsiniz.') }}
                </p>
            @endif
        </div>

        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="min-w-0 rounded-[var(--adh-radius)] border border-adh-border bg-white p-4 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue sm:p-6 xl:col-span-7">
                <div class="mb-5 border-b border-adh-border pb-4 dark:border-gray-700">
                    <h2 class="font-serif text-2xl font-bold text-adh-text dark:text-gray-100">{{ __('Mesaj Gönderin') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-adh-gray dark:text-gray-400">{{ __('Okur geri bildirimleri, haber önerileri ve kurumsal iletişim talepleri doğrudan ilgili ekibe yönlendirilir.') }}</p>
                </div>

                @if(session('success'))
                    <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->has('contact'))
                    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('contact') }}
                    </div>
                @endif

                <form method="POST" action="{{ \App\Support\LocalizedUrl::route('contact.submit') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-adh-text dark:text-gray-300" for="name">{{ __('Adınız Soyadınız') }}</label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name') }}"
                                   class="w-full rounded border border-adh-border bg-white px-3 py-2.5 text-sm focus:border-adh-red focus:outline-none dark:border-gray-600 dark:bg-adh-navy dark:text-gray-200"
                                   placeholder="{{ __('Adınız Soyadınız') }}" required>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-adh-text dark:text-gray-300" for="email">{{ __('E-posta Adresiniz') }}</label>
                            <input type="email" name="email" id="email"
                                   value="{{ old('email') }}"
                                   class="w-full rounded border border-adh-border bg-white px-3 py-2.5 text-sm focus:border-adh-red focus:outline-none dark:border-gray-600 dark:bg-adh-navy dark:text-gray-200"
                                   placeholder="ornek@email.com" required>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-adh-text dark:text-gray-300" for="subject">{{ __('Konu') }}</label>
                        <select name="subject" id="subject"
                                class="w-full rounded border border-adh-border bg-white px-3 py-2.5 text-sm focus:border-adh-red focus:outline-none dark:border-gray-600 dark:bg-adh-navy dark:text-gray-200">
                            <option value="">{{ __('Konu seçin...') }}</option>
                            <option value="haber-ihbar">{{ __('Haber İhbarı') }}</option>
                            <option value="duzeltme">{{ __('Düzeltme Talebi') }}</option>
                            <option value="reklam">{{ __('Reklam & İşbirliği') }}</option>
                            <option value="teknik">{{ __('Teknik Sorun') }}</option>
                            <option value="diger">{{ __('Diğer') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-adh-text dark:text-gray-300" for="message">{{ __('Mesajınız') }}</label>
                        <textarea name="message" id="message" rows="7"
                                  class="w-full resize-none rounded border border-adh-border bg-white px-3 py-2.5 text-sm focus:border-adh-red focus:outline-none dark:border-gray-600 dark:bg-adh-navy dark:text-gray-200"
                                  placeholder="{{ __('Mesajınızı buraya yazın...') }}" required>{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded bg-adh-red px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-red-700">
                        {{ __('Mesajı Gönder') }}
                    </button>
                </form>
            </div>

            <div class="min-w-0 space-y-5 xl:col-span-5">
                <div class="rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('İletişim Bilgileri') }}</p>
                    <ul class="mt-4 space-y-4 text-sm text-adh-text dark:text-gray-200">
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ $resolvedAddress }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ $resolvedPhone }}" class="transition-colors hover:text-adh-red">{{ $resolvedPhone }}</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-adh-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:{{ $resolvedEmail }}" class="break-all transition-colors hover:text-adh-red">{{ $resolvedEmail }}</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <a href="https://wa.me/{{ $whatsAppPhone }}" target="_blank" rel="noopener" class="transition-colors hover:text-green-600">{{ __('WhatsApp Haber Hattı') }}</a>
                        </li>
                    </ul>
                </div>

                <div class="rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Yanıt Akışı') }}</p>
                    <table class="mt-4 w-full text-sm text-adh-text dark:text-gray-200">
                        <tbody class="divide-y divide-adh-border dark:divide-gray-700">
                            <tr><td class="py-2 text-adh-gray dark:text-gray-400">{{ __('Haber ihbarı') }}</td><td class="py-2 text-right font-medium">{{ __('Gün içinde değerlendirilir') }}</td></tr>
                            <tr><td class="py-2 text-adh-gray dark:text-gray-400">{{ __('Düzeltme talepleri') }}</td><td class="py-2 text-right font-medium">{{ __('Öncelikli olarak incelenir') }}</td></tr>
                            <tr><td class="py-2 text-adh-gray dark:text-gray-400">{{ __('Kurumsal iletişim') }}</td><td class="py-2 text-right font-medium">{{ __('İş günlerinde yanıtlanır') }}</td></tr>
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs leading-6 text-adh-gray dark:text-gray-400">{{ __('Acil haber bildirimleri için telefon ve WhatsApp hattı öncelikli iletişim kanalı olarak kullanılır.') }}</p>
                </div>

                @php
                    $adhMapQuery = rawurlencode('Yeni Sanayi Mahallesi 2819 Sokak No 70 Adıyaman Merkez');
                @endphp
                <div class="overflow-hidden rounded-[var(--adh-radius)] border border-adh-border bg-white shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue">
                    <div class="aspect-[16/10] min-h-[220px]">
                        <iframe
                            title="{{ __('Konum haritası') }}"
                            src="https://maps.google.com/maps?q={{ $adhMapQuery }}&hl=tr&z=16&output=embed"
                            class="h-full w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen></iframe>
                    </div>
                    <div class="border-t border-adh-border p-3 text-center dark:border-gray-700">
                        <a href="https://maps.app.goo.gl/NGPknVNMNcqYVmDc8" target="_blank" rel="noopener"
                           class="text-sm font-medium text-adh-red transition hover:underline">{{ __('Google Haritalarda aç') }} →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
