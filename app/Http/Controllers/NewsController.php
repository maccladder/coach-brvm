<?php

namespace App\Http\Controllers;

use App\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $newsList = News::published()
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(10);

        return view('news.index', ['newsList' => $newsList]);
    }

    public function show(News $news)
    {
        abort_unless($news->is_published, 404);

        if ($news->published_at && $news->published_at->isFuture()) {
            abort(404);
        }

        return view('news.show', compact('news'));
    }
}
