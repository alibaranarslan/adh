@extends('layouts.app')

@php $noindex = true; $metaTitle = __('Sunucu Hatası'); @endphp

@section('content')
    <section class="bg-white dark:bg-gray-800 border border-adh-border rounded p-10 text-center">
        <h1 class="font-serif text-5xl mb-3">500</h1>
        <p class="text-lg mb-6">{{ __('Beklenmeyen bir hata oluştu.') }}</p>
        <a href="{{ route('home') }}" class="bg-adh-red text-white px-4 py-2 rounded">{{ __('Anasayfaya Dön') }}</a>
    </section>
@endsection
