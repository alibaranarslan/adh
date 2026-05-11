@php $backups = $this->getBackupFiles(); @endphp

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header actions --}}
        <x-filament::section heading="Yedek Oluştur">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Şu an <strong>{{ count($backups) }}</strong> yedek dosyası mevcut.
                        <br>Yeni bir veritabanı yedeği oluşturmak için butona tıklayın.
                    </p>
                </div>
                <x-filament::button
                    wire:click="createBackup"
                    wire:confirm="Yeni bir yedek oluşturulacak. Bu işlem birkaç dakika sürebilir. Devam?"
                    icon="heroicon-o-archive-box-arrow-down"
                    color="primary"
                >
                    Yeni Yedek Oluştur
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Backup list --}}
        <x-filament::section heading="Mevcut Yedekler">
            @if(count($backups) === 0)
                <div class="text-center py-8 text-gray-400">
                    <x-heroicon-o-archive-box class="w-10 h-10 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Henüz yedek dosyası bulunmuyor.</p>
                    <p class="text-xs mt-1">spatie/laravel-backup paketi kurulu ve yapılandırılmış olmalıdır.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                            <tr>
                                <th class="px-4 py-3 text-gray-600 dark:text-gray-400 font-medium">Dosya Adı</th>
                                <th class="px-4 py-3 text-gray-600 dark:text-gray-400 font-medium">Boyut</th>
                                <th class="px-4 py-3 text-gray-600 dark:text-gray-400 font-medium">Tarih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($backups as $backup)
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {{ $backup['name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $backup['size'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $backup['last_modified'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>
