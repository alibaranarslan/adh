@php
    $data = $this->getViewData();
    $alerts = $data['alerts'] ?? [];
@endphp

@if(! empty($alerts))
    <x-filament::card>
        <div class="space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                Sistem Uyarilari
            </h3>

            @foreach($alerts as $alert)
                @php
                    $classes = match ($alert['tone'] ?? 'info') {
                        'danger' => 'bg-danger-50 dark:bg-danger-900/20 border-danger-200 dark:border-danger-800 text-danger-700 dark:text-danger-300',
                        'warning' => 'bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-800 text-warning-700 dark:text-warning-300',
                        default => 'bg-info-50 dark:bg-info-900/20 border-info-200 dark:border-info-800 text-info-700 dark:text-info-300',
                    };
                @endphp

                <div class="p-3 rounded-lg border {{ $classes }}">
                    <p class="text-sm font-semibold">{{ $alert['title'] }}</p>
                    <p class="mt-1 text-sm">{{ $alert['message'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::card>
@endif
