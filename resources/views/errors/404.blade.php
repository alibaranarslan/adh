@extends('layouts.app')

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@php $metaTitle = __('Sayfa Bulunamadı'); $noindex = true; @endphp

@section('content')
    <section class="bg-white dark:bg-gray-800 border border-adh-border rounded p-10 text-center">
        <h1 class="font-serif text-5xl mb-3">404</h1>
        <p class="text-lg mb-6">{{ __('Bu sayfa bulunamadı.') }}</p>
        <a href="{{ route('home') }}" class="bg-adh-red text-white px-4 py-2 rounded">{{ __('Anasayfaya Dön') }}</a>
    </section>
@endsection