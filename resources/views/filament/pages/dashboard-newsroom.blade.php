<x-filament-panels::page class="fi-dashboard-page">
    @php
        $tones = [
            'danger' => 'admin-tone admin-tone--danger',
            'warning' => 'admin-tone admin-tone--warning',
            'success' => 'admin-tone admin-tone--success',
            'neutral' => 'admin-tone admin-tone--neutral',
        ];
    @endphp

    <div class="admin-workspace adh-workspace space-y-5">
        <section class="admin-panel-surface admin-masthead admin-masthead--adh" data-tour-anchor="dashboard.header">
            <div class="admin-masthead__content">
                <div>
                    <div class="admin-masthead__chips">
                        <span class="admin-chip">{{ $hero['window_label'] }}</span>
                        <span class="admin-chip admin-chip--accent">{{ $hero['source_label'] }}</span>
                    </div>
                    <h2 class="admin-masthead__title">{{ $hero['title'] }}</h2>
                    <p class="admin-masthead__summary">{{ $hero['summary'] }}</p>
                    <div class="admin-masthead__meta">
                        <span>Son yenilenme {{ $hero['last_refreshed_at']->format('d.m.Y H:i') }}</span>
                        <span>{{ count($attention['items']) > 0 ? count($attention['items']).' öncelikli sinyal' : 'Akış dengede' }}</span>
                    </div>
                </div>

                <div class="admin-masthead__actions" data-tour-anchor="dashboard.guide-entry">
                    <a href="{{ $hero['primary_action']['url'] }}" class="admin-btn admin-btn--primary">{{ $hero['primary_action']['label'] }}</a>
                    <a href="{{ $hero['secondary_action']['url'] }}" class="admin-btn admin-btn--ghost">{{ $hero['secondary_action']['label'] }}</a>
                    <button type="button" x-data="{}" x-on:click="$dispatch('adh-admin-guide:start', { key: 'dashboard-overview' })" class="admin-btn admin-btn--subtle">{{ $hero['guide_label'] }}</button>
                </div>
            </div>

            <form method="GET" action="{{ \App\Filament\Pages\Dashboard::getUrl(panel: 'admin') }}" class="admin-filter-bar" data-tour-anchor="dashboard.filters" aria-label="Haber Masası görünüm filtresi">
                <label class="admin-filter-field">
                    <span>Zaman aralığı</span>
                    <select name="filters[window]">
                        @foreach ($filter_state['window_options'] as $value => $label)
                            <option value="{{ $value }}" @selected($filter_state['window'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="admin-filter-field">
                    <span>İçerik kaynağı</span>
                    <select name="filters[source]">
                        @foreach ($filter_state['source_options'] as $value => $label)
                            <option value="{{ $value }}" @selected($filter_state['source'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="admin-filter-actions">
                    <button type="submit" class="admin-btn admin-btn--primary">Uygula</button>
                    <a href="{{ \App\Filament\Pages\Dashboard::getUrl(panel: 'admin') }}" class="admin-btn admin-btn--ghost">Sıfırla</a>
                </div>
            </form>
        </section>

        <section class="admin-signal-strip">
            @foreach ($signals as $signal)
                <article class="admin-signal {{ $tones[$signal['tone']] ?? $tones['neutral'] }}">
                    <div>
                        <p class="admin-signal__label">{{ $signal['label'] }}</p>
                        <p class="admin-signal__value">{{ $signal['value'] }}</p>
                        <p class="admin-signal__meta">{{ $signal['meta'] }}</p>
                    </div>
                    <div class="admin-mini-bars" aria-hidden="true">
                        @foreach ($signal['bars'] as $bar)
                            <span style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>

        <section class="admin-dashboard-grid admin-dashboard-grid--primary">
            <div class="admin-panel-surface" data-tour-anchor="dashboard.queue">
                <div class="admin-section-head">
                    <div>
                        <h3>{{ $primary_queue['title'] }}</h3>
                        <p>{{ $primary_queue['summary'] }}</p>
                    </div>
                    <span class="admin-counter">{{ count($primary_queue['rows']) }} kayıt</span>
                </div>

                @if (count($primary_queue['rows']) > 0)
                    <div class="admin-table-shell">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Kova</th>
                                    <th>Başlık</th>
                                    <th>Kategori</th>
                                    <th>Skor</th>
                                    <th>Durum</th>
                                    <th class="text-right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($primary_queue['rows'] as $row)
                                    <tr>
                                        <td><span class="admin-inline-pill {{ $tones[$row['tone']] ?? $tones['neutral'] }}">{{ $row['bucket'] }}</span></td>
                                        <td>
                                            <div class="admin-table__title">{{ $row['title'] }}</div>
                                            <div class="admin-table__meta">{{ $row['meta'] }}</div>
                                    </td>
                                    <td>{{ $row['category'] }}</td>
                                    <td>{{ $row['score'] }}</td>
                                        <td>{{ $row['status'] }}</td>
                                        <td class="text-right"><a href="{{ $row['url'] }}" class="admin-link">Aç</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="admin-empty-state admin-empty-state--queue">
                        <p class="admin-empty-state__title">{{ $primary_queue['empty_state']['title'] }}</p>
                        <p>{{ $primary_queue['empty_state']['body'] }}</p>
                        <div class="admin-empty-state__actions">
                            <a href="{{ $primary_queue['empty_state']['primary_url'] }}" class="admin-btn admin-btn--primary">{{ $primary_queue['empty_state']['primary_label'] }}</a>
                            <a href="{{ $primary_queue['empty_state']['secondary_url'] }}" class="admin-btn admin-btn--ghost">{{ $primary_queue['empty_state']['secondary_label'] }}</a>
                        </div>
                    </div>
                @endif
            </div>

            <div class="admin-panel-surface" data-tour-anchor="dashboard.attention">
                <div class="admin-section-head">
                    <div>
                        <h3>{{ $attention['title'] }}</h3>
                        <p>{{ $attention['summary'] }}</p>
                    </div>
                </div>

                <div class="admin-rail-list">
                    @forelse ($attention['items'] as $item)
                        <article class="admin-rail-item {{ $tones[$item['tone']] ?? $tones['neutral'] }}">
                            <div>
                                <div class="admin-rail-item__meta-row">
                                    <p class="admin-rail-item__title">{{ $item['title'] }}</p>
                                    <span class="admin-inline-kicker">{{ $item['meta'] }}</span>
                                </div>
                                <p class="admin-rail-item__body">{{ $item['body'] }}</p>
                            </div>
                            <a href="{{ $item['url'] }}" class="admin-link">{{ $item['action_label'] }}</a>
                        </article>
                    @empty
                        <div class="admin-empty-state admin-empty-state--calm">Şu anda müdahale gerektiren kritik sinyal görünmüyor.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="admin-dashboard-grid admin-dashboard-grid--secondary">
            <section class="admin-panel-surface" data-tour-anchor="dashboard.health">
                <div class="admin-section-head">
                    <div>
                        <h3>{{ $iha_flow['title'] }}</h3>
                        <p>{{ $iha_flow['summary'] }}</p>
                    </div>
                    <a href="{{ $hero['secondary_action']['url'] }}" class="admin-link">İHA Sağlığı</a>
                </div>

                <div class="admin-kpi-grid admin-kpi-grid--triple">
                    @foreach ($iha_flow['cards'] as $card)
                        <article class="admin-kpi {{ $tones[$card['tone']] ?? $tones['neutral'] }}">
                            <p class="admin-kpi__label">{{ $card['label'] }}</p>
                            <p class="admin-kpi__value">{{ $card['value'] }}</p>
                            <p class="admin-kpi__meta">{{ $card['meta'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="admin-chart-row">
                    <div>
                        <p class="admin-subhead">{{ $iha_flow['chart']['title'] }}</p>
                        <div class="admin-comparison-bars">
                            @foreach ($iha_flow['chart']['bars'] as $bar)
                                <div class="admin-comparison-bars__item">
                                    <div class="admin-comparison-bars__label-row">
                                        <span>{{ $bar['label'] }}</span>
                                        <strong>{{ $bar['value'] }}</strong>
                                    </div>
                                    <div class="admin-comparison-bars__track">
                                        <span class="admin-comparison-bars__fill admin-comparison-bars__fill--{{ $bar['tone'] }}" style="width: {{ max(8, min(100, $bar['value'] * 14)) }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel-surface" data-tour-anchor="dashboard.publish">
                <div class="admin-section-head">
                    <div>
                        <h3>{{ $homepage['title'] }}</h3>
                        <p>{{ $homepage['summary'] }}</p>
                    </div>
                    <a href="{{ $homepage['url'] }}" class="admin-link">Yerleşim Stüdyosu</a>
                </div>

                <article class="admin-inline-status {{ $tones[$homepage['tone']] ?? $tones['neutral'] }}">
                    <div>
                        <p class="admin-inline-status__title">{{ $homepage['state'] }}</p>
                        <p class="admin-inline-status__meta">Aktif modül sayısı {{ $homepage['active_modules'] }}</p>
                    </div>
                </article>

                <div class="admin-kpi-grid admin-kpi-grid--double">
                    <article class="admin-kpi admin-tone admin-tone--neutral">
                        <p class="admin-kpi__label">Taslak güncelleme</p>
                        <p class="admin-kpi__value">{{ $homepage['draft_updated_at'] }}</p>
                        <p class="admin-kpi__meta">Taslak akışının son izi</p>
                    </article>
                    <article class="admin-kpi admin-tone admin-tone--neutral">
                        <p class="admin-kpi__label">Son yayın</p>
                        <p class="admin-kpi__value">{{ $homepage['published_at'] }}</p>
                        <p class="admin-kpi__meta">Canlıya alınan son düzen</p>
                    </article>
                    <article class="admin-kpi admin-tone admin-tone--neutral admin-kpi--full">
                        <p class="admin-kpi__label">Son rollback izi</p>
                        <p class="admin-kpi__value">{{ $homepage['rollback_at'] }}</p>
                        <p class="admin-kpi__meta">Arşivden geri dönülen son revizyon</p>
                    </article>
                </div>
            </section>
        </div>

        <section class="admin-panel-surface" data-tour-anchor="dashboard.seo">
            <div class="admin-section-head">
                <div>
                    <h3>{{ $seo_health['title'] }}</h3>
                    <p>{{ $seo_health['summary'] }}</p>
                </div>
                <a href="{{ \App\Filament\Pages\SeoSettings::getUrl(panel: 'admin') }}" class="admin-link">SEO Ayarları</a>
            </div>

            <div class="admin-kpi-grid admin-kpi-grid--triple">
                @foreach ($seo_health['cards'] as $card)
                    <article class="admin-kpi {{ $tones[$card['tone']] ?? $tones['neutral'] }}">
                        <p class="admin-kpi__label">{{ $card['label'] }}</p>
                        <p class="admin-kpi__value">{{ $card['value'] }}</p>
                        <p class="admin-kpi__meta">{{ $card['meta'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="admin-panel-surface" data-tour-anchor="dashboard.traffic">
            <div class="admin-section-head">
                <div>
                    <h3>{{ $traffic['title'] }}</h3>
                    <p>{{ $traffic['summary'] }}</p>
                </div>
                <a href="{{ $traffic['url'] }}" class="admin-link">Analitik</a>
            </div>

            <div class="admin-dashboard-grid admin-dashboard-grid--secondary">
                <div class="admin-inset-surface">
                    <p class="admin-subhead">{{ $traffic['top_articles_heading'] }}</p>
                    <div class="admin-list-cards">
                        @forelse ($traffic['top_articles'] as $article)
                            <a href="{{ $article['url'] }}" class="admin-list-card">
                                <div>
                                    <p class="admin-list-card__title">{{ $article['title'] }}</p>
                                    <p class="admin-list-card__meta">{{ $article['category'] }}</p>
                                </div>
                                <div class="admin-list-card__side">
                                    <strong>{{ number_format($article['views']) }}</strong>
                                    <div class="admin-progress"><span style="width: {{ $article['percentage'] }}%"></span></div>
                                </div>
                            </a>
                        @empty
                            <div class="admin-empty-state">Bu aralıkta öne çıkan trafik kaydı yok.</div>
                        @endforelse
                    </div>
                </div>

                <div class="admin-inset-surface">
                    <p class="admin-subhead">Yükselen içerik</p>
                    <div class="admin-list-cards">
                        @forelse ($traffic['rising_articles'] as $article)
                            <a href="{{ $article['url'] }}" class="admin-list-card">
                                <div>
                                    <p class="admin-list-card__title">{{ $article['title'] }}</p>
                                    <p class="admin-list-card__meta">Skor {{ $article['score'] }} / 100</p>
                                </div>
                                <div class="admin-list-card__side">
                                    <strong>{{ number_format($article['views']) }}</strong>
                                    <div class="admin-progress admin-progress--cool"><span style="width: {{ $article['percentage'] }}%"></span></div>
                                </div>
                            </a>
                        @empty
                            <div class="admin-empty-state">Hızlı yükselen içerik görünmüyor.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-dashboard-grid admin-dashboard-grid--secondary">
            <section class="admin-panel-surface" data-tour-anchor="dashboard.quick-actions">
                <div class="admin-section-head">
                    <div>
                        <h3>Hızlı Müdahale</h3>
                        <p>Derin sayfalara inmeden en sık kullanılan editoryal aksiyonlar.</p>
                    </div>
                </div>
                <div class="admin-action-grid">
                    @foreach ($quick_actions as $action)
                        <a href="{{ $action['url'] }}" class="admin-action-card {{ $tones[$action['tone']] ?? $tones['neutral'] }}">
                            <p class="admin-action-card__title">{{ $action['label'] }}</p>
                            <p class="admin-action-card__meta">{{ $action['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            @if ($is_ops)
                <section class="admin-panel-surface">
                    <div class="admin-section-head">
                        <div>
                            <h3>{{ $ops_health['title'] }}</h3>
                            <p>{{ $ops_health['summary'] }}</p>
                        </div>
                    </div>
                    <div class="admin-kpi-grid admin-kpi-grid--double">
                        @foreach ($ops_health['cards'] as $card)
                            <article class="admin-kpi {{ $tones[$card['tone']] ?? $tones['neutral'] }}">
                                <p class="admin-kpi__label">{{ $card['label'] }}</p>
                                <p class="admin-kpi__value">{{ $card['value'] }}</p>
                                <p class="admin-kpi__meta">{{ $card['meta'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
