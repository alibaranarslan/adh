@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => __('Anasayfa'), 'url' => \App\Support\LocalizedUrl::route('home')],
            ['label' => __('Haber Arşivi')],
        ]" />

        <section class="rounded-[var(--adh-radius)] border border-adh-border bg-white p-5 shadow-[var(--adh-shadow)] dark:border-gray-700 dark:bg-adh-blue md:p-6">
            <x-section-heading
                :title="__('Haber Arşivi')"
                :subtitle="__('Arşive alınmış haberler burada listelenir; içerikler saklanır ve doğrudan bağlantıları çalışmaya devam eder.')"
                eyebrow="{{ __('Saklanan Yayınlar') }}"
            />

            @if ($articles->isEmpty())
                <div class="rounded-2xl border border-dashed border-adh-border bg-slate-50 px-6 py-10 text-center text-sm text-adh-gray dark:border-gray-700 dark:bg-adh-navy/30 dark:text-gray-300">
                    {{ __('Henüz arşive alınmış haber görünmüyor.') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($articles as $item)
                        <x-news-card-mini :article="$item" />
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $articles->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
