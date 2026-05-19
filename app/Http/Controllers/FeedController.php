<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\AiVisibility\AiVisibilityContent;

class FeedController extends Controller
{
    public function rss(AiVisibilityContent $content)
    {
        return $this->rssResponse($content->rssFor());
    }

    public function news(AiVisibilityContent $content)
    {
        return $this->rssResponse($content->rssFor());
    }

    public function adiyaman(AiVisibilityContent $content)
    {
        return $this->rssResponse($content->rssFor(citySlug: 'adiyaman'));
    }

    public function category(string $slug, AiVisibilityContent $content)
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        return $this->rssResponse($content->rssFor(category: $category));
    }

    private function rssResponse(string $xml)
    {
        return response($xml, 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
