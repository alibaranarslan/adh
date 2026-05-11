<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;

class ArchiveController extends Controller
{
    public function index()
    {
        $articles = NewsArticle::query()
            ->where('status', 'archived')
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->with('category')
            ->latest('published_at')
            ->paginate(24);

        return view('news.archive', compact('articles'))->with([
            'metaTitle' => __('Haber Arşivi'),
            'metaDescription' => __('Geçmiş haberlerin arşiv listesi.'),
        ]);
    }
}
