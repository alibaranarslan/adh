@extends('layouts.app')

@section('content')
    <section class="bg-white dark:bg-adh-blue border border-adh-border dark:border-adh-blue rounded p-5">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-6 h-6 text-adh-red flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h1 class="font-serif text-3xl dark:text-gray-100">İller</h1>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach ($allCities as $citySlug => $cityLabel)
                @php $count = $cityCounts[$citySlug] ?? 0; @endphp
                <a href="{{ \App\Support\LocalizedUrl::route('city.show', ['slug' => $citySlug]) }}"
                   class="group relative flex flex-col items-center justify-center p-5 rounded-lg border border-adh-border dark:border-gray-700 hover:border-adh-red hover:shadow-md transition-all duration-200
                          {{ $citySlug === 'adiyaman' ? 'bg-adh-red/5 dark:bg-adh-red/10 border-adh-red/30' : 'bg-white dark:bg-adh-blue' }}">
                    <svg class="w-6 h-6 mb-2 text-adh-gray dark:text-gray-400 group-hover:text-adh-red transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-serif text-base font-medium dark:text-gray-100 group-hover:text-adh-red transition-colors">{{ $cityLabel }}</span>
                    <span class="text-xs text-adh-gray dark:text-gray-400 mt-1">{{ $count }} haber</span>
                </a>
            @endforeach
        </div>
    </section>
@endsection
