@include('home.sections.editorial-grid', [
    'settings' => $settings,
    'articles' => $politicsEconomyNews,
    'fallbackTitle' => 'Siyaset ve Ekonomi',
    'eyebrow' => 'Karar ve piyasa',
])
