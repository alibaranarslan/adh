@php
    $status = $this->getBackupStatus();
    $latest = $status['latest_backup'];
@endphp

<x-filament-panels::page class="admin-page-frame">
    <section class="admin-section-panel" data-tour-anchor="backup.hero">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600">Operasyonel güvence</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Yedekleme ve geri dönüş hazırlığı</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Bu ekran son alınan dosyaları, aktif klasörü ve komut hazırlığını tek yerde toplar.
                    Yedekleme komutu kayıtlı değilse buton pasif kalır; sahte başarı mesajı üretilmez.
                </p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full {{ $status['command_available'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }} px-3 py-1">{{ $status['readiness_label'] }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">Yalnız DB yedeği</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">Restore yok</span>
                </div>
            </div>

            <div class="admin-page-grid admin-page-grid--three lg:min-w-[38rem]">
                <div class="admin-section-panel admin-section-panel--compact">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Durum</p>
                    <p class="mt-2 text-sm font-semibold {{ $status['command_available'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                        {{ $status['command_available'] ? 'backup:run hazır' : 'Yapılandırma eksik' }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $status['readiness_description'] }}</p>
                </div>

                <div class="admin-section-panel admin-section-panel--compact">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Son yedek</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $latest['last_modified'] ?? 'Kayıt yok' }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $status['latest_backup_age'] }} | {{ $latest['size'] ?? 'Dosya bulunamadı' }}</p>
                </div>

                <div class="admin-section-panel admin-section-panel--compact">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Dosya özeti</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($status['total_files']) }} dosya</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Toplam boyut: {{ $status['total_size'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel" data-tour-anchor="backup.action">
            <div class="admin-section-head">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Yedekleme aksiyonu</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Komut hazırsa yalnız veritabanı yedeğini buradan başlatın. Geri yükleme, indirme ve silme bu fazda yoktur.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($status['directories'] as $directory)
                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/30">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Aranan klasör</p>
                        <p class="mt-2 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $directory }}</p>
                    </div>
                @endforeach
            </div>

            <div class="admin-action-bar">
                <x-filament::button
                    wire:click="createBackup"
                    wire:confirm="backup:run --only-db komutu tetiklenecek. Bu aksiyon yalnız veritabanı yedeği başlatır; restore veya silme yapmaz. Devam etmek istiyor musunuz?"
                    icon="heroicon-o-archive-box-arrow-down"
                    color="primary"
                    :disabled="! $status['command_available']"
                >
                    Yeni yedek oluştur
                </x-filament::button>
            </div>
        </section>

        <section class="admin-section-panel" data-tour-anchor="backup.runbook">
            <div class="admin-section-head">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Runbook özeti</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Teslim ve geri dönüş akışı için üç temel kontrol noktası.</p>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                <div class="rounded-2xl border border-slate-200/80 px-4 py-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">1. Doğrulama</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Son dosyanın tarihini ve boyutunu kontrol edin; beklenen klasör görünmüyorsa fallback klasörü de inceleyin.</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 px-4 py-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">2. Geri dönüş</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Canlı geri yükleme öncesi uygulamayı bakım kipine alın ve ilgili dump dosyasını önce staging ortamında test edin.</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 px-4 py-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">3. Kayıt</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Yedek alma ve geri yükleme işlemlerini tarih, operatör ve dosya adıyla release notlarına ekleyin.</p>
                </div>
            </div>
        </section>
    </div>

    <section class="admin-section-panel" data-tour-anchor="backup.files">
        <div class="admin-section-head">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Mevcut dosyalar</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">En yeni kayıtlar üstte görünür. Bu liste yalnız okunurdur.</p>
            </div>
        </div>

        @if ($status['total_files'] === 0)
            <div class="admin-empty-state mt-4">
                Bu kapsamda yedek dosyası bulunamadı. Komut hazırsa ilk veritabanı yedeğini bu ekrandan başlatabilirsiniz.
            </div>
        @else
            <div class="admin-table-shell mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Dosya</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Klasör</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Boyut</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($status['files'] as $backup)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $backup['name'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $backup['directory'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $backup['size'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $backup['last_modified'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-filament-panels::page>
