@props([
    'article',
    'compact' => false,
    'showSource' => false,
    'showViews' => false,
    'showReadTime' => true,
    'showFreshness' => true,
])

@php
    $story = \App\Support\NewsPresenter::present($article, $compact ? 'thumb' : 'medium');
    $textSize = $compact ? 'text-[10px]' : 'text-xs';
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-x-2 gap-y-1', $textSize, 'text-adh-gray dark:text-gray-400']) }}>
    @if ($showFreshness && $story['freshness_label'])
        <span class="inline-flex items-center rounded-full bg-adh-red/10 px-2 py-0.5 font-semibold text-adh-red dark:bg-red-500/10 dark:text-red-300">
            {{ $story['freshness_label'] }}
        </span>
    @endif

    @if ($story['published_label'])
        <time datetime="{{ $story['published_iso'] }}" class="font-medium">
            {{ $compact ? $story['published_short_label'] : $story['published_label'] }}
        </time>
    @endif

    @if ($showSource && $story['source_label'])
        <span aria-hidden="true">&middot;</span>
        <span>{{ $story['source_label'] }}</span>
    @endif

    @if ($showReadTime && $story['read_time_label'])
        <span aria-hidden="true">&middot;</span>
        <span>{{ $story['read_time_label'] }}</span>
    @endif

    @if ($showViews && $story['views_label'])
        <span aria-hidden="true">&middot;</span>
        <span>{{ $story['views_label'] }}</span>
    @endif
</div>
