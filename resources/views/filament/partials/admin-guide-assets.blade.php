@php
    $adminGuideCssPath = resource_path('css/admin-guide.css');
    $adminGuideJsPath = resource_path('js/admin-guide.js');
@endphp

@if (is_file($adminGuideCssPath))
    <style>
        {!! file_get_contents($adminGuideCssPath) !!}
    </style>
@endif

@if (is_file($adminGuideJsPath))
    <script>
        {!! file_get_contents($adminGuideJsPath) !!}
    </script>
@endif
