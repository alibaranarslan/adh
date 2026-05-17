<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsArticle;
use App\Models\Tag;
use App\Services\RelatedNewsService;

class NewsController extends Controller
{
    public function __construct(
        private readonly RelatedNewsService $relatedNewsService,
    ) {
    }

    public function show(string $localeOrSlug, ?string $slug = null)
    {
        $slug = $this->resolveSlug($localeOrSlug, $slug);

        $article = NewsArticle::publiclyAccessible()
            ->where('slug', $slug)
            ->with(['category', 'tags', 'images', 'author'])
            ->firstOrFail();

        $article->increment('view_count');

        $related = $this->relatedNewsService->for($article, 4);

        $ads = Advertisement::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        return view('news.show', compact('article', 'related', 'ads'))->with([
            'metaTitle' => $article->title,
            'metaDescription' => $article->summary,
            'ogImage' => $article->featured_image,
            'ogType' => 'article',
        ]);
    }

    public function category(string $localeOrSlug, ?string $slug = null)
    {
        $slug = $this->resolveSlug($localeOrSlug, $slug);

        $category = Category::active()->where('slug', $slug)->firstOrFail();

        $articles = NewsArticle::published()
            ->with('category')
            ->where('category_id', $category->id)
            ->orderByRaw('editorial_score DESC, published_at DESC')
            ->paginate(12);

        return view('news.category-v2', compact('category', 'articles'))->with([
            'metaTitle' => $category->name . ' ' . __('Haberleri'),
            'metaDescription' => $category->name . ' ' . __('kategorisindeki güncel haberler.'),
        ]);
    }

    public function tag(string $localeOrSlug, ?string $slug = null)
    {
        $slug = $this->resolveSlug($localeOrSlug, $slug);

        $tag = Tag::where('slug', $slug)->firstOrFail();

        $articles = $tag->articles()
            ->published()
            ->with('category')
            ->orderByRaw('editorial_score DESC, published_at DESC')
            ->paginate(12);

        return view('news.tag-v2', compact('tag', 'articles'))->with([
            'metaTitle' => '#' . $tag->name . ' ' . __('Etiketli Haberler'),
            'metaDescription' => $tag->name . ' ' . __('etiketi altındaki haberler.'),
        ]);
    }

    private function resolveSlug(string $localeOrSlug, ?string $slug = null): string
    {
        return $slug ?? $localeOrSlug;
    }
}
