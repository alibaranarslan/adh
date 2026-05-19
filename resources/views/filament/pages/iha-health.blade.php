<x-filament-panels::page class="admin-page-frame">
    @php
        $freshnessState = $stats['freshness_state'] ?? 'unknown';
        $freshnessTone = match ($freshnessState) {
            'healthy' => 'text-emerald-700 dark:text-emerald-300',
            'warning' => 'text-amber-700 dark:text-amber-300',
            'critical' => 'text-rose-700 dark:text-rose-300',
            default => 'text-slate-600 dark:text-slate-300',
        };
    @endphp

    <div class="admin-note">
        İHA akışının tazeliğini, son hata özetini ve çeviri yükünü tek ekranda izleyin. Bu ekran operasyonel izleme içindir; içerik yayınına doğrudan müdahale etmez.
        <span class="ml-2 inline-flex rounded-full {{ $stats['iha_credentials_ready'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }} px-3 py-1 text-xs font-semibold">
            {{ $stats['iha_credentials_ready'] ? 'Hazır' : 'Eksik' }}
        </span>
        <span class="ml-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            {{ $stats['iha_credentials_source'] }}
        </span>
    </div>

    <div class="admin-page-grid admin-page-grid--three" data-tour-anchor="iha.health.summary">
        <div class="admin-section-panel">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Efektif senkron aralığı</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white">{{ $stats['effective_interval'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $stats['schedule_note'] }}</p>
        </div>
        <div class="admin-section-panel">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Son başarılı senkron</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white">{{ $stats['last_successful_sync']?->completed_at?->diffForHumans() ?? 'Henüz yok' }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $stats['last_successful_sync']?->completed_at?->format('d.m.Y H:i:s') ?? 'İlk başarılı kayıt henüz oluşmadı.' }}</p>
        </div>
        <div class="admin-section-panel">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Son koşu durumu</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white">{{ $stats['latest_sync_label'] }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $stats['latest_sync']?->started_at?->format('d.m.Y H:i:s') ?? 'Henüz çalışma kaydı yok.' }}</p>
        </div>
        <div class="admin-section-panel">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Tazelik farkı</p>
            <p class="mt-3 text-2xl font-semibold {{ $freshnessTone }}">
                @if (($stats['freshness_lag_minutes'] ?? null) !== null)
                    {{ number_format($stats['freshness_lag_minutes']) }} dk
                @else
                    Bilinmiyor
                @endif
            </p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                @switch($freshnessState)
                    @case('healthy') Haber akışı beklenen aralıkta. @break
                    @case('warning') Gecikme artıyor; kuyruk ve cron akışını kontrol edin. @break
                    @case('critical') Senkron belirgin biçimde gecikmiş; manuel kontrol önerilir. @break
                    @default Tazelik hesabı için yeterli veri yok.
                @endswitch
            </p>
        </div>
    </div>

    <div class="admin-page-grid admin-page-grid--two">
        <section class="admin-section-panel" data-tour-anchor="iha.health.backlog">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Çeviri ve hata görünümü</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Backlog yükü ve son hata mesajı birlikte okunmalıdır.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Eksik çeviri</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['translation_backlog']) }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Kuyruk: {{ number_format($stats['queued_translation_jobs']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Yeniden deneme notu</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $stats['retry_note'] }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                <p class="text-sm font-medium text-slate-900 dark:text-white">Son hata özeti</p>
                @if ($stats['last_error'])
                    <p class="mt-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:bg-rose-500/10 dark:text-rose-100">{{ $stats['last_error'] }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $stats['last_error_at']?->format('d.m.Y H:i:s') }}</p>
                @else
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Kayıtlı son hata görünmüyor. Bu iyi bir işaret, ancak tazelik kartıyla birlikte değerlendirilmelidir.</p>
                @endif
            </div>
        </section>

        <section class="admin-section-panel" data-tour-anchor="iha.health.credentials">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Entegrasyon hazırlığı</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kimlik bilgileri tam değilse akış stabil görünse bile senkron sürdürülebilir olmaz.</p>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/80 px-4 py-3 dark:border-slate-800">
                    <div>
                        <span class="text-sm text-slate-600 dark:text-slate-300">İHA kimlik bilgileri</span>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $stats['iha_credentials_note'] }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $stats['iha_credentials_ready'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $stats['iha_credentials_ready'] ? $stats['iha_credentials_source'] : 'Eksik' }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/80 px-4 py-3 dark:border-slate-800">
                    <span class="text-sm text-slate-600 dark:text-slate-300">Google Translation anahtarı</span>
                    <span class="text-sm font-semibold {{ $stats['translation_credentials_ready'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $stats['translation_credentials_ready'] ? 'Hazır' : 'Eksik' }}</span>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800"><p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Çekilen</p><p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['summary']['fetched']) }}</p></div>
                <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800"><p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Yeni / güncel</p><p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['summary']['created']) }} / {{ number_format($stats['summary']['updated']) }}</p></div>
                <div class="rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800"><p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Atlanan / hatalı</p><p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['summary']['skipped']) }} / {{ number_format($stats['summary']['failed']) }}</p></div>
            </div>
        </section>
    </div>

    <section class="admin-section-panel" data-tour-anchor="iha.health.logs">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Son senkron kayıtları</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Detaylı geçmiş için senkron kayıtları ekranına geçebilirsiniz.</p>
        <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/70">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Başlangıç</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Çekilen</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Yeni / güncel</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Hata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $log->started_at?->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ match($log->status) { 'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300', 'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300', 'running' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300', default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', } }}">{{ match($log->status) { 'success' => 'Başarılı', 'failed' => 'Hatalı', 'running' => 'Çalışıyor', 'partial' => 'Kısmi', default => $log->status, } }}</span></td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ number_format((int) $log->articles_fetched) }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ number_format((int) $log->articles_created) }} / {{ number_format((int) $log->articles_updated) }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \App\Support\AdminSafeText::limit($log->error_message, 90) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6"><div class="admin-empty-state">Henüz İHA senkron kaydı oluşmadı.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
