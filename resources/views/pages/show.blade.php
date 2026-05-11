@extends('layouts.app')

@section('content')
    @php
        $pageContext = match ($page->slug) {
            'hakkimizda' => [
                'eyebrow' => __('Kurumsal Profil'),
                'lead' => __('Adıyaman merkezli yayın odağımızı, editoryal yaklaşımımızı ve okurla kurduğumuz ilişkiyi açık biçimde paylaşırız.'),
            ],
            'gizlilik-politikasi' => [
                'eyebrow' => __('Gizlilik'),
                'lead' => __('Kişisel verilerin hangi amaçlarla işlendiğini ve yayın platformumuzda nasıl korunduğunu şeffaf biçimde açıklarız.'),
            ],
            'kvkk-aydinlatma' => [
                'eyebrow' => __('KVKK'),
                'lead' => __('Veri işleme ve aydınlatma yükümlülüklerimizi, okur güvenini zedelemeyecek açık bir dille sunarız.'),
            ],
            'cerez-politikasi' => [
                'eyebrow' => __('Çerez Politikası'),
                'lead' => __('Site deneyimini etkileyen çerez tercihlerini ve ölçüm bileşenlerini sade şekilde görünür kılıyoruz.'),
            ],
            default => [
                'eyebrow' => __('Kurumsal Bilgi'),
                'lead' => __('Bu sayfa ADH kurumsal yayın yüzeyinin bir parçasıdır.'),
            ],
        };

        $trustPages = [
            ['label' => __('Hakkımızda'), 'slug' => 'hakkimizda'],
            ['label' => __('Gizlilik Politikası'), 'slug' => 'gizlilik-politikasi'],
            ['label' => __('KVKK Aydınlatma Metni'), 'slug' => 'kvkk-aydinlatma'],
            ['label' => __('Çerez Politikası'), 'slug' => 'cerez-politikasi'],
            ['label' => __('İletişim'), 'url' => route('contact')],
        ];
    @endphp

    <section class="space-y-6">
        <div class="rounded-[var(--adh-radius)] border border-adh-border bg-white px-6 py-7 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:px-8 md:py-9">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-adh-red">{{ $pageContext['eyebrow'] }}</p>
            <h1 class="mt-3 max-w-3xl font-serif text-3xl font-bold leading-tight text-adh-text dark:text-gray-100 md:text-4xl">
                {{ $page->title }}
            </h1>
            <p class="mt-4 max-w-3xl text-base leading-7 text-adh-gray dark:text-gray-300">
                {{ $pageContext['lead'] }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <div class="rounded-[var(--adh-radius)] border border-adh-border bg-slate-50/80 p-5 dark:border-gray-700 dark:bg-adh-blue">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-adh-red">{{ __('Kurumsal Navigasyon') }}</p>
                    <div class="mt-4 space-y-2">
                        @foreach ($trustPages as $item)
                            @php
                                $url = $item['url'] ?? route('page.show', ['slug' => $item['slug']]);
                                $isActive = ($item['slug'] ?? null) === $page->slug;
                            @endphp
                            <a
                                href="{{ $url }}"
                                class="block rounded border px-4 py-3 text-sm transition {{ $isActive ? 'border-adh-red bg-adh-red/5 font-semibold text-adh-red dark:border-red-400 dark:bg-red-500/5 dark:text-red-300' : 'border-adh-border text-adh-text hover:border-adh-red hover:text-adh-red dark:border-gray-700 dark:text-gray-200 dark:hover:border-red-400 dark:hover:text-red-300' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-5 rounded border border-adh-border/80 bg-white px-4 py-4 text-sm leading-6 text-adh-gray dark:border-gray-700 dark:bg-adh-blue dark:text-gray-300">
                        {{ __('Kurumsal metinler, ziyaretçinin ilk bakışta güven duyacağı açıklıkta ve kamuya açık dilde sunulur.') }}
                    </div>
                </div>
            </aside>

            <article class="xl:col-span-9 rounded-[var(--adh-radius)] border border-adh-border bg-white p-6 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-8">
                <div class="prose prose-neutral max-w-none dark:prose-invert prose-headings:font-serif prose-headings:tracking-tight prose-h2:text-[1.55rem] prose-h2:mt-8 prose-h2:mb-3 prose-h3:text-xl prose-h3:mt-6 prose-p:leading-8 prose-p:text-[1.02rem] prose-p:text-adh-text dark:prose-p:text-gray-200 prose-ul:my-4 prose-li:my-1 prose-a:text-adh-red hover:prose-a:text-red-700">
                    {!! $page->content !!}
                </div>
            </article>
        </div>
    </section>
@endsection
