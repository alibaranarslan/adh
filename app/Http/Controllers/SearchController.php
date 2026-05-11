<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $articles = collect();
        $searchError = false;

        if (mb_strlen($query) >= 3) {
            try {
                $articles = NewsArticle::publiclyAccessible()
                    ->with('category')
                    ->where(function (Builder $builder) use ($query) {
                        $this->applyLocalizedSearch($builder, $query);
                    })
                    ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
                    ->latest('published_at')
                    ->paginate(12)
                    ->withQueryString();
            } catch (\Throwable $exception) {
                $searchError = true;
                $articles = collect();

                Log::warning('Public search failed.', [
                    'query' => $query,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return view('search.results', compact('query', 'articles', 'searchError'))->with([
            'metaTitle' => $query ? '"' . $query . '" ' . __('arama sonuçları') : __('Haber arama'),
            'metaDescription' => __('Adıyaman Dijital Haber içerisinde arama sonuçları.'),
            'noindex' => true,
        ]);
    }

    private function applyLocalizedSearch(Builder $builder, string $query): void
    {
        $driver = $builder->getModel()->getConnection()->getDriverName();
        $lowerLike = '%' . mb_strtolower($query, 'UTF-8') . '%';
        $paths = [
            ['title', 'tr'],
            ['title', 'en'],
            ['title', 'ku'],
            ['summary', 'tr'],
            ['summary', 'en'],
            ['summary', 'ku'],
            ['content', 'tr'],
            ['content', 'en'],
            ['content', 'ku'],
        ];

        foreach ($paths as $index => [$column, $locale]) {
            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';

            if ($driver === 'sqlite') {
                $builder->{$method}(
                    "LOWER(COALESCE(json_extract({$column}, '$.{$locale}'), '')) LIKE ?",
                    [$lowerLike]
                );

                continue;
            }

            $builder->{$method}(
                "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.{$locale}')), '')) LIKE ?",
                [$lowerLike]
            );
        }
    }
}