<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::query()
            ->where('is_published', true)
            ->latest()
            ->get();

        return view('books.index', compact('books'));
    }

    public function show(Book $book, Request $request)
    {
        abort_unless($book->is_published, 404);

        $pages = $book->pages()->get();
        abort_if($pages->isEmpty(), 404);

        $page = (int) $request->query('page', 1);
        $page = max(1, min($page, $pages->count()));

        $current = $pages->firstWhere('page_no', $page) ?? $pages->first();

        return view('books.show', [
            'book' => $book,
            'pagesCount' => $pages->count(),
            'currentPage' => $current->page_no,
            'currentTitle' => $current->title,
            'currentContent' => $current->content,
        ]);
    }
}
