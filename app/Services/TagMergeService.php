<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TagMergeService
{
    public function mergeInto(Tag $targetTag, Collection $sourceTags): void
    {
        $sourceTags = $sourceTags
            ->filter(fn ($tag) => $tag instanceof Tag)
            ->reject(fn (Tag $tag) => $tag->is($targetTag))
            ->values();

        if ($sourceTags->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($targetTag, $sourceTags): void {
            foreach ($sourceTags as $sourceTag) {
                $articleIds = $sourceTag->articles()->pluck('news_articles.id')->all();

                if ($articleIds !== []) {
                    $targetTag->articles()->syncWithoutDetaching($articleIds);
                    $sourceTag->articles()->detach($articleIds);
                }

                $sourceTag->delete();
            }
        });
    }
}
