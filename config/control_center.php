<?php

return [
    'attention' => [
        'iha_stale_minutes' => 45,
        'translation_backlog_warning' => 3,
        'critical_draft_score' => 80,
        'featured_candidate_score' => 72,
        'max_items' => 5,
    ],
    'queue' => [
        'max_rows' => 8,
    ],
    'traffic' => [
        'top_limit' => 5,
        'rising_limit' => 4,
    ],
];
